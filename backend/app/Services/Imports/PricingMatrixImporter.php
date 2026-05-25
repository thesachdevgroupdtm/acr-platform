<?php

namespace App\Services\Imports;

use App\Imports\BaseImport;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceColumnMapping;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Phase 4.3 — Step 2 of the matrix import flow (D-4.3-6 commit).
 *
 * The preview service already validated the file; this class
 * re-reads it (cheaper than caching the analyser's transient
 * state) and performs the actual UPSERT against service_prices.
 *
 * Performance contract (D-4.3-5):
 *   - Pre-load brands / models / fuels / services / existing
 *     prices into in-memory hashes BEFORE the row loop.
 *   - Per-row processing is O(1) lookups, no queries.
 *   - INSERT batches of 500 via DB::table()->insert.
 *   - UPDATE via DB::table()->where(...)->update in a loop —
 *     each update touches one row, indexed by the composite
 *     unique key, so it's fast.
 *   - Entire flow wrapped in DB::transaction.
 *
 * Target: 15,000 records < 30s on the dev MySQL.
 */
class PricingMatrixImporter
{
    public const INSERT_CHUNK = 500;

    /** @var array<string, CarBrand> */
    protected array $brandsByName = [];

    /** @var array<string, CarModel> */
    protected array $modelsByKey = [];

    /** @var array<string, FuelType> */
    protected array $fuelsByName = [];

    /** @var array<string, int>  composite-key → service_prices.id */
    protected array $existingPriceIdByKey = [];

    /** @var array<string, ?int>  lowercase excel column → service_id (or null=ignore) */
    protected array $effectiveMappings = [];

    public int $inserted = 0;
    public int $updated  = 0;
    public int $skipped  = 0;
    public int $invalid  = 0;

    /** @var array<int, array{row:int, errors:array<string>}> */
    public array $errorLog = [];

    public function __construct(
        protected PricingMatrixPreviewService $preview,
    ) {
        foreach (CarBrand::all() as $b) {
            $this->brandsByName[$preview->norm($b->name)] = $b;
        }
        foreach (CarModel::all() as $m) {
            $this->modelsByKey[$m->brand_id . '|' . $preview->norm($m->name)] = $m;
        }
        foreach (FuelType::all() as $f) {
            $this->fuelsByName[$preview->norm($f->name)] = $f;
        }
        foreach (DB::table('service_prices')->select('id', 'service_id', 'brand_id', 'model_id', 'fuel_type_id')->cursor() as $row) {
            $key = "{$row->service_id}|{$row->brand_id}|{$row->model_id}|{$row->fuel_type_id}";
            $this->existingPriceIdByKey[$key] = $row->id;
        }
    }

    /**
     * Run the commit. `$overrides` lets the Filament UI pass
     * operator-edited column mappings that override the resolver's
     * choices.
     *
     * Phase 4.3.2 — `$persistMappings` controls whether those
     * overrides are also written to `service_column_mappings` for
     * future imports. The operator-facing UI exposes this as a
     * "Save these matches for next time" checkbox; default stays
     * `true` so existing call sites (and tests) behave identically.
     *
     * @param  array<string, ?int>  $overrides  excel-column → service_id (or null=ignore)
     */
    public function commit(
        string $absolutePath,
        array $overrides = [],
        ?int $userId = null,
        bool $persistMappings = true,
    ): void {
        // ── Build the effective column → service_id table ──
        $headings = (new \Maatwebsite\Excel\HeadingRowImport())->toArray($absolutePath)[0][0] ?? [];
        $serviceColumns = collect($headings)
            ->map(fn ($h) => trim((string) $h))
            ->filter(fn ($h) => $h !== '' && ! in_array($this->preview->norm($h), PricingMatrixPreviewService::VEHICLE_COLUMNS, true))
            ->values()
            ->all();

        $resolvedByExcel = [];
        foreach ($serviceColumns as $col) {
            $norm = $this->preview->norm($col);
            // Override wins.
            if (array_key_exists($col, $overrides) || array_key_exists($norm, $overrides)) {
                $sid = $overrides[$col] ?? $overrides[$norm];
                $resolvedByExcel[$col] = $sid;
                continue;
            }
            $resolution = $this->preview->resolveColumn($col);
            $resolvedByExcel[$col] = $resolution['service_id'];
        }

        // Persist overrides as future-import aliases — gated by the
        // Phase 4.3.2 `$persistMappings` flag so operators can apply
        // a one-off mapping without polluting the saved table.
        if ($persistMappings) {
            foreach ($overrides as $excelOrNorm => $sid) {
                ServiceColumnMapping::updateOrCreate(
                    ['excel_column' => $excelOrNorm],
                    [
                        'service_id' => $sid,
                        'is_active'  => true,
                        'created_by' => $userId,
                    ]
                );
            }
        }

        $this->effectiveMappings = $resolvedByExcel;

        // ── Walk rows, build insert + update batches ──
        $rows = $this->preview->loadRows($absolutePath);

        // Composite-key → insert payload index. Within a single
        // file, two rows can target the same (service, brand, model,
        // fuel) tuple — without dedupe the INSERT trips the
        // svcprice_full_unique constraint. Last cell wins.
        $insertsByKey = [];
        $updates      = [];  // [id => price]

        foreach ($rows as $rowIndex => $row) {
            $brand = $this->preview->fuzzyVehicleField((array) $row, 'make') ?? null;
            $model = $this->preview->fuzzyVehicleField((array) $row, 'model') ?? null;
            $fuel  = $this->preview->fuzzyVehicleField((array) $row, 'fuel_type') ?? null;

            $brandRow = $brand !== null ? ($this->brandsByName[$this->preview->norm($brand)] ?? null) : null;
            $modelRow = ($brandRow && $model !== null)
                ? ($this->modelsByKey[$brandRow->id . '|' . $this->preview->norm($model)] ?? null)
                : null;
            $fuelRow = $fuel !== null ? ($this->fuelsByName[$this->preview->norm($fuel)] ?? null) : null;

            if (! $brandRow || ! $modelRow || ! $fuelRow) {
                $this->invalid++;
                continue;
            }

            foreach ($this->effectiveMappings as $excelCol => $serviceId) {
                if ($serviceId === null) {
                    $this->skipped++;
                    continue;
                }
                $cell = $row[$excelCol] ?? $row[strtolower($excelCol)] ?? null;
                if (BaseImport::isSkipToken($cell)) {
                    $this->skipped++;
                    continue;
                }
                if (! is_numeric($cell)) {
                    $this->invalid++;
                    continue;
                }
                // Round to whole rupees. Excel sheets are routinely
                // authored with display-format `0` (integer) on numeric
                // cells whose stored value carries a stale `.5` decimal
                // — the user sees "5249" but the cell actually holds
                // 5248.5. Honouring the displayed integer matches
                // operator expectations and avoids sub-rupee noise
                // propagating into customer-facing prices.
                $price = (float) round((float) $cell);
                if ($price < 0) {
                    $this->invalid++;
                    continue;
                }

                $key = "{$serviceId}|{$brandRow->id}|{$modelRow->id}|{$fuelRow->id}";
                if (isset($this->existingPriceIdByKey[$key])) {
                    $updates[$this->existingPriceIdByKey[$key]] = $price;
                } else {
                    // Dedupe within the same import: later cell wins.
                    $insertsByKey[$key] = [
                        'service_id'   => $serviceId,
                        'brand_id'     => $brandRow->id,
                        'model_id'     => $modelRow->id,
                        'fuel_type_id' => $fuelRow->id,
                        'price'        => $price,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }
        }

        $inserts = array_values($insertsByKey);

        // ── Execute batches in a single transaction ──
        DB::transaction(function () use ($inserts, $updates) {
            foreach (array_chunk($inserts, self::INSERT_CHUNK) as $chunk) {
                DB::table('service_prices')->insert($chunk);
                $this->inserted += count($chunk);
            }
            foreach ($updates as $id => $price) {
                DB::table('service_prices')->where('id', $id)->update([
                    'price'      => $price,
                    'updated_at' => now(),
                ]);
                $this->updated++;
            }
        });
    }
}
