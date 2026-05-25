<?php

namespace App\Console\Commands;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Console\Command;

/**
 * FILEUPLOAD-RECOVERY (D-FU-5) — normalize entity `image` column values to
 * a clean relative path, repairing records left in a malformed state by the
 * broken FileUpload (e.g. a JSON-encoded Filament state array, or a stray
 * livewire-tmp path) so the preview can hydrate.
 *
 * Read-only-safe: a clean relative path is left untouched; only malformed
 * values are rewritten (to the embedded entity-images path, or null). Uses
 * saveQuietly() so it never triggers CleansOldImage (no file deletion during
 * a column-value repair). --dry-run reports without writing.
 */
class NormalizeImagePaths extends Command
{
    protected $signature = 'acr:normalize-image-paths {--dry-run : Report what would change without writing}';

    protected $description = 'Normalize entity image column values to clean relative paths (repair malformed FileUpload state).';

    public function handle(): int
    {
        $map = [
            'brands'     => CarBrand::class,
            'models'     => CarModel::class,
            'fuel-types' => FuelType::class,
            'services'   => Service::class,
            'categories' => ServiceCategory::class,
        ];

        $dry = (bool) $this->option('dry-run');
        $checked = 0;
        $fixed = 0;

        foreach ($map as $type => $class) {
            foreach ($class::query()->whereNotNull('image')->get() as $record) {
                $checked++;
                $raw = $record->getRawOriginal('image');
                $clean = $this->cleanValue(is_string($raw) ? $raw : null);

                if ($clean === $raw) {
                    continue; // already a clean string
                }

                $this->line(sprintf(
                    '  [%s #%d] %s → %s',
                    $type,
                    $record->getKey(),
                    $this->short($raw),
                    $clean ?? 'null',
                ));

                if (! $dry) {
                    $record->image = $clean;
                    $record->saveQuietly(); // skip CleansOldImage — value repair, not a re-upload
                }
                $fixed++;
            }
        }

        $this->info(sprintf(
            '%s — checked %d image value(s), %s %d malformed.',
            $dry ? 'DRY RUN' : 'Done',
            $checked,
            $dry ? 'would fix' : 'fixed',
            $fixed,
        ));

        return self::SUCCESS;
    }

    /** Clean string → unchanged; JSON array/object → embedded path or null; temp → null. */
    private function cleanValue(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        // Filament/livewire state leaked into the column as JSON.
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $value) {
                if (is_string($value) && str_contains($value, 'entity-images/')) {
                    return $value;
                }
            }
            return null;
        }

        // Stray temporary-upload path.
        if (str_contains($raw, 'livewire-tmp')) {
            return null;
        }

        return $raw; // already clean
    }

    private function short(?string $s): string
    {
        $s = (string) $s;

        return strlen($s) > 60 ? substr($s, 0, 57) . '…' : $s;
    }
}
