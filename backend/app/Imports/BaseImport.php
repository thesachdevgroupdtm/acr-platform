<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Phase 4.3 — abstract base for Family A row-per-record imports.
 *
 * Sub-classes:
 *   - BrandsImport
 *   - ModelsImport
 *   - FuelTypesImport
 *   - ServicesImport
 *
 * Provides:
 *   - Heading-row consumption (column names become array keys).
 *   - Chunked reading (100 rows per chunk) so memory stays flat
 *     on a 10k-row brands file.
 *   - Soft validation: rows that fail validateRow() append to
 *     `$errors[]` and continue; processRow() never runs for them.
 *   - UPSERT semantics: sub-class's processRow() resolves an
 *     existing record by slug (or composite key) and updates,
 *     else inserts.
 *
 * Counters (`rowsTotal`, `rowsValid`, `rowsInvalid`, `rowsSkipped`)
 * mirror the imports table columns, so the calling controller
 * can persist them after the run.
 */
abstract class BaseImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    public int $rowsTotal    = 0;
    public int $rowsValid    = 0;
    public int $rowsInvalid  = 0;
    public int $rowsSkipped  = 0;

    /** @var array<int, array{row:int, errors:array<string>}> */
    public array $errorLog = [];

    public bool $commit = true;

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $idx => $row) {
            $this->rowsTotal++;

            // Skip totally empty rows (Excel tail padding).
            if ($this->isRowEmpty($row)) {
                $this->rowsSkipped++;
                continue;
            }

            $errors = $this->validateRow($row);
            if (! empty($errors)) {
                $this->rowsInvalid++;
                if (count($this->errorLog) < 100) {
                    $this->errorLog[] = [
                        'row'    => $this->rowsTotal,
                        'errors' => $errors,
                    ];
                }
                continue;
            }

            if ($this->commit) {
                try {
                    $this->processRow($row);
                } catch (\Throwable $e) {
                    $this->rowsInvalid++;
                    if (count($this->errorLog) < 100) {
                        $this->errorLog[] = [
                            'row'    => $this->rowsTotal,
                            'errors' => ['exception: ' . $e->getMessage()],
                        ];
                    }
                    continue;
                }
            }
            $this->rowsValid++;
        }
    }

    /**
     * Sub-classes return [] for valid, or a list of error strings
     * for invalid rows.
     *
     * @return array<int, string>
     */
    abstract protected function validateRow(Collection $row): array;

    /**
     * Sub-classes do the UPSERT here. Only called for valid rows.
     */
    abstract protected function processRow(Collection $row): void;

    protected function isRowEmpty(Collection $row): bool
    {
        return $row->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty();
    }

    /**
     * Phase 4.3 — Skip-token normaliser used by both Family A and
     * Family B. "NA" / "N/A" / "-" / "none" / empty are treated as
     * "no value", per D-4.3-4.
     */
    public static function isSkipToken(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return true;
        }
        $lc = strtolower($s);
        return in_array($lc, ['na', 'n/a', '-', '—', 'none'], true);
    }
}
