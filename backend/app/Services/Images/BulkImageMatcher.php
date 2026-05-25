<?php

namespace App\Services\Images;

use App\Exceptions\BulkImageException;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Imports\Strategies\FuzzyMatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * L2 — bulk image upload matcher.
 *
 * One operator-uploaded ZIP contains per-type folders:
 *   brands/Audi.png · models/Audi_Q5.png · services/Battery Replacement.png
 *   categories/Battery.png
 *
 * Each image is matched to an entity by filename (D-L2-2), validated for
 * format + size (D-L2-3), then — on commit only — stored to the public
 * disk at entity-images/{type}/{slug}.{ext} and written to entity.image.
 *
 * Dry-run + commit pattern (D-L2-5), mirroring the pricing-matrix import:
 *   analyze() → match + report, ZERO storage / ZERO DB writes
 *   commit()  → re-match, then store + update inside one DB transaction
 *
 * Re-upload overwrites (D-L2-7): same ZIP twice = same result (idempotent).
 *
 * Uses PHP-native ZipArchive — no new packages.
 */
class BulkImageMatcher
{
    public const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'webp'];

    public const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    /** ZIP folder name === entity-type key (folder mode) / tab key (per-tab mode). */
    public const TYPES = ['brands', 'models', 'services', 'categories', 'fuel-types'];

    /** Public disk subfolder root for stored images. */
    public const STORAGE_ROOT = 'entity-images';

    /** Dry-run: match + report only. No storage, no DB writes. */
    public function analyze(string $zipPath): ImageMatchReport
    {
        return $this->process($zipPath, commit: false);
    }

    /** Commit: store images + update entity.image, atomically. */
    public function commit(string $zipPath): ImageMatchReport
    {
        $report = DB::transaction(fn () => $this->process($zipPath, commit: true));
        $report->committed = true;

        return $report;
    }

    // ─── Per-tab auto-process (D-FIX-3) ─────────────────────────────

    /**
     * Per-tab flat-list processing: the tab IS the entity type, so there
     * is no folder prefix. Each file is an image OR a .zip to extract.
     * Matched images are stored + entity.image set immediately (one
     * transaction); unmatched / oversize / bad-format / ambiguous are
     * reported. No analyze→import 2-step.
     *
     * @param array<int, array{name:string, contents:string, size?:int}> $files
     */
    public function processForType(array $files, string $type): ImageMatchReport
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new BulkImageException("Unknown entity type: {$type}");
        }

        return DB::transaction(function () use ($files, $type) {
            $report = new ImageMatchReport();

            foreach ($files as $file) {
                $name  = (string) ($file['name'] ?? '');
                $bytes = (string) ($file['contents'] ?? '');
                $base  = basename($name);

                if ($base === '' || str_starts_with($base, '.')) {
                    continue;
                }

                if (strtolower(pathinfo($base, PATHINFO_EXTENSION)) === 'zip') {
                    $this->ingestZipBytes($report, $type, $bytes);
                } else {
                    $this->ingestImage($report, $type, $base, $bytes, (int) ($file['size'] ?? strlen($bytes)));
                }
            }

            $report->committed = true;

            return $report;
        });
    }

    /** Validate + match + store ONE image (auto-commit path). */
    private function ingestImage(ImageMatchReport $report, string $type, string $base, string $bytes, int $size): void
    {
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            $report->addSkipped($base, "unsupported format ." . ($ext ?: '?') . " (allowed: " . implode(', ', self::ALLOWED_EXT) . ")");
            return;
        }
        if ($size > self::MAX_BYTES) {
            $report->addSkipped($base, sprintf('too large (%.1f MB, max 5 MB)', $size / 1048576));
            return;
        }

        // Strip a trailing Unix timestamp (D-FIX2-2/3) — harmless on clean
        // names; rescues old-website BRAND+MODEL+timestamp filenames.
        $stem     = pathinfo($base, PATHINFO_FILENAME);
        $stemNoTs = $this->stripTimestamp($stem);

        $entity = $this->matchForType($type, $stemNoTs);

        // Models: smart fallback — split a glued BRAND+MODEL (e.g. VOLVOXC60
        // → Volvo + xc60) using the known brands, then exact/fuzzy match.
        if ($entity === null && $type === 'models') {
            $entity = $this->matchModelSmart($base);
        }

        if ($entity === null) {
            if ($type === 'models') {
                $report->addSkipped($base, "couldn't parse brand+model from filename '{$stem}'");
                return;
            }
            $report->addUnmatched($type, $base);
            return;
        }

        $storedPath = self::STORAGE_ROOT . "/{$type}/{$entity->slug}.{$ext}";
        Storage::disk('public')->put($storedPath, $bytes); // overwrites (idempotent)
        $entity->update(['image' => $storedPath]);
        $report->addMatched($type, $base, $entity->name, $entity->slug, $storedPath);
    }

    /** Extract a ZIP (from raw bytes) and ingest every image entry. */
    private function ingestZipBytes(ImageMatchReport $report, string $type, string $bytes): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bimzip');
        file_put_contents($tmp, $bytes);

        try {
            $zip = new ZipArchive();
            if ($zip->open($tmp) !== true) {
                throw BulkImageException::cannotOpenZip($tmp);
            }
            try {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if ($entry === false || str_ends_with($entry, '/') || str_starts_with($entry, '__MACOSX')) {
                        continue;
                    }
                    $base = basename($entry);
                    if ($base === '' || str_starts_with($base, '.')) {
                        continue;
                    }
                    $content = $zip->getFromIndex($i);
                    if ($content === false) {
                        $report->addSkipped($base, 'could not read image bytes from ZIP');
                        continue;
                    }
                    // Tab IS the type — accept the image regardless of any
                    // folder nesting inside the ZIP.
                    $this->ingestImage($report, $type, $base, $content, (int) ($zip->statIndex($i)['size'] ?? strlen($content)));
                }
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($tmp);
        }
    }

    // ─── Core walk ──────────────────────────────────────────────────

    private function process(string $zipPath, bool $commit): ImageMatchReport
    {
        if (! is_file($zipPath)) {
            throw BulkImageException::zipNotFound($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw BulkImageException::cannotOpenZip($zipPath);
        }

        $report = new ImageMatchReport();

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName === false || str_ends_with($entryName, '/')) {
                    continue; // directory entry
                }

                // Normalize separators, classify by top-level folder.
                $segments = preg_split('#[/\\\\]#', $entryName);
                $folder   = strtolower($segments[0] ?? '');
                if (! in_array($folder, self::TYPES, true)) {
                    continue; // ignore __MACOSX/, stray root files, etc.
                }

                $base = basename($entryName);
                if ($base === '' || str_starts_with($base, '.')) {
                    continue; // .DS_Store and other dotfiles
                }

                $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                if (! in_array($ext, self::ALLOWED_EXT, true)) {
                    $report->addSkipped($entryName, "unsupported format ." . ($ext ?: '?') . " (allowed: " . implode(', ', self::ALLOWED_EXT) . ")");
                    continue;
                }

                $size = (int) ($zip->statIndex($i)['size'] ?? 0);
                if ($size > self::MAX_BYTES) {
                    $report->addSkipped($entryName, sprintf('too large (%.1f MB, max 5 MB)', $size / 1048576));
                    continue;
                }

                $this->matchAndRecord($report, $folder, $base, $ext, $zip, $i, $commit);
            }
        } finally {
            $zip->close();
        }

        return $report;
    }

    private function matchAndRecord(
        ImageMatchReport $report,
        string $type,
        string $base,
        string $ext,
        ZipArchive $zip,
        int $index,
        bool $commit
    ): void {
        $entity = $this->matchForType($type, $base);

        if ($entity === null) {
            // Models: a no-underscore name that hits multiple brands is
            // ambiguous — surface it as a skip with a fix hint rather than
            // a silent unmatched (D-L2-2 "warn if ambiguous").
            if ($type === 'models') {
                $stem = pathinfo($base, PATHINFO_FILENAME);
                if (! str_contains($stem, '_')) {
                    $count = CarModel::whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeFilename($base)])->count();
                    if ($count > 1) {
                        $report->addSkipped(
                            "models/{$base}",
                            "ambiguous model name '{$stem}' matches {$count} models across brands — rename to Brand_Model.{$ext}"
                        );
                        return;
                    }
                }
            }
            $report->addUnmatched($type, $base);
            return;
        }

        $storedPath = null;
        if ($commit) {
            $storedPath = self::STORAGE_ROOT . "/{$type}/{$entity->slug}.{$ext}";
            $bytes = $zip->getFromIndex($index);
            if ($bytes === false) {
                $report->addSkipped("{$type}/{$base}", 'could not read image bytes from ZIP');
                return;
            }
            Storage::disk('public')->put($storedPath, $bytes); // overwrites (D-L2-7)
            $entity->update(['image' => $storedPath]);
        }

        $report->addMatched($type, $base, $entity->name, $entity->slug, $storedPath);
    }

    // ─── Matching helpers (D-L2-2) ──────────────────────────────────

    /** Strip extension, trim, lowercase. */
    public function normalizeFilename(string $filename): string
    {
        return mb_strtolower(trim(pathinfo($filename, PATHINFO_FILENAME)));
    }

    public function matchBrand(string $filename): ?CarBrand
    {
        return CarBrand::whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeFilename($filename)])->first();
    }

    public function matchCategory(string $filename): ?ServiceCategory
    {
        return ServiceCategory::whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeFilename($filename)])->first();
    }

    public function matchService(string $filename): ?Service
    {
        return Service::whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeFilename($filename)])->first();
    }

    public function matchFuel(string $filename): ?FuelType
    {
        return FuelType::whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeFilename($filename)])->first();
    }

    /** Dispatch to the right matcher for a tab/folder type. */
    public function matchForType(string $type, string $filename): ?Model
    {
        return match ($type) {
            'brands'     => $this->matchBrand($filename),
            'categories' => $this->matchCategory($filename),
            'services'   => $this->matchService($filename),
            'fuel-types' => $this->matchFuel($filename),
            'models'     => $this->matchModel($filename),
            default      => null,
        };
    }

    /**
     * Brand_Model format (D-L2-2): split on the FIRST underscore →
     * [brand, model]. Fallback: no underscore → match model name globally
     * only when it's unique (ambiguity handled by the caller).
     */
    public function matchModel(string $filename): ?CarModel
    {
        $stem = pathinfo($filename, PATHINFO_FILENAME);

        if (str_contains($stem, '_')) {
            [$brandPart, $modelPart] = explode('_', $stem, 2);
            $brand = CarBrand::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($brandPart))])->first();
            if ($brand === null) {
                return null;
            }

            return CarModel::where('brand_id', $brand->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($modelPart))])
                ->first();
        }

        // No underscore — global match, unique only.
        $candidates = CarModel::whereRaw('LOWER(TRIM(name)) = ?', [$this->normalizeFilename($filename)])->get();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    // ─── Smart "messy filename" matching (D-FIX2-2) ─────────────────

    /** Remove a trailing 10-digit Unix timestamp (old-website export glue). */
    public function stripTimestamp(string $name): string
    {
        return preg_replace('/\d{10}$/', '', $name) ?? $name;
    }

    /** Lowercase + strip everything non-alphanumeric (glue-safe compare). */
    public function normalizeString(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', trim($value)) ?? '');
    }

    /**
     * Split a glued BRAND+MODEL stem by longest known-brand prefix.
     * "VOLVOXC60" → { brand: Volvo, model_norm: "xc60" }. Longest prefix
     * wins so a brand "Mini Cooper" beats "Mini" when both could match.
     *
     * @return array{brand: CarBrand, model_norm: string}|null
     */
    public function splitBrandModel(string $cleaned): ?array
    {
        $norm = $this->normalizeString($cleaned);
        if ($norm === '') {
            return null;
        }

        $bestBrand = null;
        $bestLen   = 0;
        foreach (CarBrand::all() as $brand) {
            $bn = $this->normalizeString($brand->name);
            if ($bn !== '' && str_starts_with($norm, $bn) && strlen($bn) > $bestLen) {
                $bestBrand = $brand;
                $bestLen   = strlen($bn);
            }
        }

        if ($bestBrand === null) {
            return null;
        }

        return ['brand' => $bestBrand, 'model_norm' => substr($norm, $bestLen)];
    }

    /**
     * Smart model match: strip timestamp, split BRAND+MODEL by brand prefix,
     * then match the model within that brand (exact-normalized, then fuzzy
     * ≥0.85 via the existing FuzzyMatcher). Null if unparseable/unmatched.
     */
    public function matchModelSmart(string $filename): ?CarModel
    {
        $stem    = pathinfo($filename, PATHINFO_FILENAME);
        $cleaned = $this->stripTimestamp($stem);
        $split   = $this->splitBrandModel($cleaned);
        if ($split === null || $split['model_norm'] === '') {
            return null;
        }

        $models = CarModel::where('brand_id', $split['brand']->id)->get();

        foreach ($models as $model) {
            if ($this->normalizeString($model->name) === $split['model_norm']) {
                return $model;
            }
        }

        $hit = app(FuzzyMatcher::class)->findBest($split['model_norm'], $models, 'name', 0.85);

        return $hit['entity'] ?? null;
    }
}
