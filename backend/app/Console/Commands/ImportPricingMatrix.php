<?php

namespace App\Console\Commands;

use App\Models\Import;
use App\Services\Imports\AutoBootstrapResolver;
use App\Services\Imports\PricingMatrixImporter;
use App\Services\Imports\PricingMatrixPreviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * CLI wrapper around the same flow Filament's PricingMatrixImportPage
 * runs: AutoBootstrapResolver::resolveAndPersist + PricingMatrixImporter::commit
 * inside a single DB::transaction. Default mode is dry-run so the
 * operator can preview counts before committing.
 */
class ImportPricingMatrix extends Command
{
    protected $signature = 'pricing:import
                            {file : Absolute path or storage-relative path to the .xlsx}
                            {--commit : Actually write to the database (default is dry-run)}';

    protected $description = 'Auto-bootstrap + import a pricing matrix Excel file.';

    public function handle(
        AutoBootstrapResolver $resolver,
    ): int {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $candidate = storage_path('app/' . ltrim($file, '/\\'));
            if (is_file($candidate)) {
                $file = $candidate;
            } else {
                $this->error("File not found: {$file}");
                return self::FAILURE;
            }
        }

        $this->info("Reading: {$file}");

        $dry = $resolver->resolveDryRun($file);
        $this->table(['Entity', 'Matched (will reuse)', 'Would create'], [
            ['Categories', $dry->categories->matchedExisting, $dry->categories->wouldCreate],
            ['Brands',     $dry->brands->matchedExisting,     $dry->brands->wouldCreate],
            ['Models',     $dry->models->matchedExisting,     $dry->models->wouldCreate],
            ['Fuel types', $dry->fuelTypes->matchedExisting,  $dry->fuelTypes->wouldCreate],
            ['Services',   $dry->services->matchedExisting,   $dry->services->wouldCreate],
        ]);

        if (! $this->option('commit')) {
            $this->warn('Dry-run only — re-run with --commit to persist.');
            return self::SUCCESS;
        }

        $audit = Import::create([
            'user_id'     => null,
            'import_type' => Import::TYPE_PRICING_MATRIX,
            'file_name'   => basename($file),
            'file_size'   => @filesize($file) ?: 0,
            'file_path'   => $file,
            'status'      => Import::STATUS_COMMITTING,
            'rows_total'  => 0,
            'rows_valid'  => 0,
            'rows_invalid'=> 0,
            'rows_skipped'=> 0,
        ]);

        // Pre-process: if the file has section banner rows above the
        // real column-header row, write a stripped temp xlsx that the
        // importer's HeadingRow reader can consume correctly. Bootstrap
        // still reads the ORIGINAL file because it needs the banners
        // to assign services → categories.
        $importerFile = $this->stripBannerRows($file);

        $bootstrap = null;
        $importer  = null;

        try {
            DB::transaction(function () use ($file, $importerFile, $audit, $resolver, &$bootstrap, &$importer) {
                $bootstrap = $resolver->resolveAndPersist($file, $audit->id);
                // PreviewService preloads master-data hashes in its
                // constructor — instantiate it AFTER bootstrap so the
                // freshly-created brands/models/fuels/services are in
                // scope for resolveColumn / fuzzyVehicleField lookups.
                $preview  = new PricingMatrixPreviewService();
                $importer = new PricingMatrixImporter($preview);
                $importer->commit(
                    absolutePath:    $importerFile,
                    overrides:       [],
                    userId:          null,
                    persistMappings: false,
                );
            });
        } catch (\Throwable $e) {
            if ($importerFile !== $file && is_file($importerFile)) {
                @unlink($importerFile);
            }
            $audit->update([
                'status'        => Import::STATUS_FAILED,
                'error_summary' => ['message' => $e->getMessage()],
            ]);
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $audit->update([
            'status'       => Import::STATUS_COMPLETED,
            'committed_at' => now(),
            'rows_valid'   => $importer->inserted + $importer->updated,
            'rows_invalid' => $importer->invalid,
            'rows_skipped' => $importer->skipped,
        ]);

        if ($importerFile !== $file && is_file($importerFile)) {
            @unlink($importerFile);
        }

        $this->info('Persisted.');
        $this->table(['Entity', 'Matched (reused)', 'Created'], [
            ['Categories', $bootstrap->categories->matchedExisting, $bootstrap->categories->created],
            ['Brands',     $bootstrap->brands->matchedExisting,     $bootstrap->brands->created],
            ['Models',     $bootstrap->models->matchedExisting,     $bootstrap->models->created],
            ['Fuel types', $bootstrap->fuelTypes->matchedExisting,  $bootstrap->fuelTypes->created],
            ['Services',   $bootstrap->services->matchedExisting,   $bootstrap->services->created],
        ]);
        $this->table(['Prices', 'Count'], [
            ['Inserted', $importer->inserted],
            ['Updated',  $importer->updated],
            ['Skipped',  $importer->skipped],
            ['Invalid',  $importer->invalid],
        ]);

        return self::SUCCESS;
    }

    /**
     * If the Excel file has banner rows above the real column-header
     * row, write a stripped temp file with just [headerRow, ...data].
     * Returns the path the importer should read from — the original
     * path if no stripping is needed, else the temp file path.
     */
    private function stripBannerRows(string $absolutePath): string
    {
        $rawReader = new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        };
        $sheets = Excel::toArray($rawReader, $absolutePath);
        $rows   = $sheets[0] ?? [];
        if (empty($rows)) {
            return $absolutePath;
        }

        $headerKeywords = ['make', 'brand', 'model', 'fueltype', 'fuel_type', 'fuel'];
        $headerIdx = null;
        foreach ($rows as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $cell) {
                $norm = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '', (string) $cell) ?? ''));
                if (in_array($norm, $headerKeywords, true)) {
                    $headerIdx = (int) $idx;
                    break 2;
                }
            }
        }

        if ($headerIdx === null || $headerIdx === 0) {
            // Either no detectable header (let the importer fail loudly)
            // or already at row 0 (no stripping needed).
            return $absolutePath;
        }

        $stripped = array_slice($rows, $headerIdx);
        $tempPath = storage_path('app/pricing_import_' . uniqid() . '.xlsx');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($stripped as $rIdx => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $cIdx => $cell) {
                $sheet->setCellValueByColumnAndRow($cIdx + 1, $rIdx + 1, $cell);
            }
        }
        (new XlsxWriter($spreadsheet))->save($tempPath);
        $spreadsheet->disconnectWorksheets();

        $this->line("Wrote stripped file (header at row " . ($headerIdx + 1) . "): {$tempPath}");
        return $tempPath;
    }
}
