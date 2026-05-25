<?php

namespace App\Services\Imports;

use App\Imports\BaseImport;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceColumnMapping;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

/**
 * Phase 4.3 — read-only analyzer for the pricing matrix Excel file.
 *
 * Used by the two-step preview flow (D-4.3-6). Step 1: operator
 * uploads file → this service walks the headers + rows once and
 * returns the structured preview the Filament UI renders. Step 2
 * (commit) lives in PricingMatrixImporter (the actual writer).
 *
 * 4-layer column resolution (D-4.3-2):
 *   1. Exact match — normalised LOWER+TRIM excel column ==
 *      normalised service name OR services.slug.
 *   2. Saved alias from service_column_mappings (active rows).
 *   3. Fuzzy match — similar_text >= 85%.
 *   4. Unmapped — operator decides in the preview UI.
 *
 * The "vehicle columns" are fixed: Car_id, Make, Model, Fuel_Type,
 * Segment. Anything else in the header row is treated as a service
 * column.
 */
class PricingMatrixPreviewService
{
    // After norm() — underscores and hyphens become spaces, so the
    // operator-typed 'Car_id' / 'Fuel_Type' headers resolve as
    // 'car id' / 'fuel type'. Keep the canonical list aligned with
    // what norm() emits, not what the Excel sheet contains.
    public const VEHICLE_COLUMNS = ['car id', 'make', 'brand', 'model', 'fuel type', 'segment'];

    /** Aliases accepted when fuzzyVehicleField looks up a canonical
     *  vehicle field by row key. Lets sheets use "Brand" instead of
     *  "Make" and "Fuel" instead of "Fuel_Type" without re-tagging. */
    public const VEHICLE_FIELD_ALIASES = [
        'make'      => ['make', 'brand'],
        'model'     => ['model'],
        'fuel_type' => ['fuel_type', 'fueltype', 'fuel'],
    ];

    public const FUZZY_THRESHOLD = 85.0;

    /** @var array<string, CarBrand>   lowercase name → brand */
    protected array $brandsByName = [];

    /** @var array<string, CarModel>   "{brand_id}|{lowercase name}" → model */
    protected array $modelsByKey = [];

    /** @var array<string, FuelType>   lowercase name → fuel */
    protected array $fuelsByName = [];

    /** @var array<string, Service>    lowercase normalised → service (by name & by slug) */
    protected array $servicesByNorm = [];

    /** @var array<string, ?int>       lowercase excel column → service_id (null=ignore) */
    protected array $savedMappings = [];

    /** @var array<string, array{service_id:int, brand_id:int, model_id:int, fuel_type_id:int, price:float}> */
    protected array $existingPriceKeys = [];

    public function __construct()
    {
        // ── Pre-load master data once (D-4.3-5) ─────────────────────
        foreach (CarBrand::all() as $b) {
            $this->brandsByName[$this->norm($b->name)] = $b;
        }
        foreach (CarModel::all() as $m) {
            $this->modelsByKey[$m->brand_id . '|' . $this->norm($m->name)] = $m;
        }
        foreach (FuelType::all() as $f) {
            $this->fuelsByName[$this->norm($f->name)] = $f;
        }
        foreach (Service::all() as $s) {
            // Map by both name and slug — covers operators using either.
            $this->servicesByNorm[$this->norm($s->name)] = $s;
            $this->servicesByNorm[$this->norm($s->slug)] = $s;
        }
        foreach (ServiceColumnMapping::active()->get() as $cm) {
            $this->savedMappings[$this->norm($cm->excel_column)] = $cm->service_id;
        }
        foreach (\DB::table('service_prices')->select('id','service_id','brand_id','model_id','fuel_type_id','price')->get() as $row) {
            $key = "{$row->service_id}|{$row->brand_id}|{$row->model_id}|{$row->fuel_type_id}";
            $this->existingPriceKeys[$key] = (array) $row;
        }
    }

    /**
     * Read the file's header row + sample data and return a
     * structured preview.
     *
     * @return array{
     *   detected_columns: array{vehicle:array<string>, service:array<string>},
     *   column_mappings: array<int, array{excel:string, service_id:?int, service_name:?string, confidence:string, suggestion:?string}>,
     *   row_summary: array{total:int, valid_vehicles:int, invalid_vehicles:int, errors:array<int, array{row:int, errors:array<string>}>},
     *   price_summary: array{total_cells:int, valid_prices:int, skipped_na:int, invalid_prices:int, will_insert:int, will_update:int}
     * }
     */
    public function analyze(string $absolutePath): array
    {
        // Headers
        $headingArrays = (new HeadingRowImport())->toArray($absolutePath);
        $headers = $headingArrays[0][0] ?? [];

        $detectedVehicle = [];
        $detectedService = [];
        foreach ($headers as $h) {
            $h = trim((string) $h);
            if ($h === '') continue;
            if (in_array($this->norm($h), self::VEHICLE_COLUMNS, true)) {
                $detectedVehicle[] = $h;
            } else {
                $detectedService[] = $h;
            }
        }

        $columnMappings = [];
        foreach ($detectedService as $excel) {
            $columnMappings[] = $this->resolveColumn($excel);
        }

        // Now walk data rows for row + price validation.
        $rowSummary = ['total' => 0, 'valid_vehicles' => 0, 'invalid_vehicles' => 0, 'errors' => []];
        $priceSummary = ['total_cells' => 0, 'valid_prices' => 0, 'skipped_na' => 0, 'invalid_prices' => 0, 'will_insert' => 0, 'will_update' => 0];

        // Build excel-col → service_id map (for cell-level resolution).
        $colToServiceId = [];
        foreach ($columnMappings as $m) {
            $colToServiceId[$m['excel']] = $m['service_id'];
        }

        $data = $this->loadRows($absolutePath);

        foreach ($data as $rowIndex => $row) {
            $rowSummary['total']++;

            $brand = $this->fuzzyVehicleField($row, 'make');
            $model = $this->fuzzyVehicleField($row, 'model');
            $fuel  = $this->fuzzyVehicleField($row, 'fuel_type');

            $errors = [];
            if ($brand === null || ! isset($this->brandsByName[$this->norm($brand)])) {
                $errors[] = "make '{$brand}' not in car_brands";
            }
            $brandRow = $brand !== null ? ($this->brandsByName[$this->norm($brand)] ?? null) : null;
            if ($brandRow && $model !== null) {
                if (! isset($this->modelsByKey[$brandRow->id . '|' . $this->norm($model)])) {
                    $errors[] = "model '{$model}' not in car_models for brand '{$brand}'";
                }
            } elseif ($model === null) {
                $errors[] = 'model is required';
            }
            if ($fuel === null || ! isset($this->fuelsByName[$this->norm($fuel)])) {
                $errors[] = "fuel_type '{$fuel}' not in fuel_types";
            }

            if (! empty($errors)) {
                $rowSummary['invalid_vehicles']++;
                if (count($rowSummary['errors']) < 20) {
                    $rowSummary['errors'][] = ['row' => $rowIndex + 2, 'errors' => $errors];
                }
                continue;
            }
            $rowSummary['valid_vehicles']++;

            $brandId = $this->brandsByName[$this->norm($brand)]->id;
            $modelId = $this->modelsByKey[$brandId . '|' . $this->norm($model)]->id;
            $fuelId  = $this->fuelsByName[$this->norm($fuel)]->id;

            foreach ($colToServiceId as $col => $serviceId) {
                $priceSummary['total_cells']++;
                $cell = $row[$col] ?? null;
                if (BaseImport::isSkipToken($cell)) {
                    $priceSummary['skipped_na']++;
                    continue;
                }
                if ($serviceId === null) {
                    // Column unmapped — counted as skipped (operator must map first).
                    $priceSummary['skipped_na']++;
                    continue;
                }
                if (! is_numeric($cell)) {
                    $priceSummary['invalid_prices']++;
                    continue;
                }
                $price = (float) $cell;
                if ($price < 0) {
                    $priceSummary['invalid_prices']++;
                    continue;
                }
                $priceSummary['valid_prices']++;
                $key = "{$serviceId}|{$brandId}|{$modelId}|{$fuelId}";
                if (isset($this->existingPriceKeys[$key])) {
                    $priceSummary['will_update']++;
                } else {
                    $priceSummary['will_insert']++;
                }
            }
        }

        return [
            'detected_columns' => ['vehicle' => $detectedVehicle, 'service' => $detectedService],
            'column_mappings'  => $columnMappings,
            'row_summary'      => $rowSummary,
            'price_summary'    => $priceSummary,
        ];
    }

    /**
     * Resolve a single Excel column through the 4 layers.
     *
     * @return array{excel:string, service_id:?int, service_name:?string, confidence:string, suggestion:?string}
     */
    public function resolveColumn(string $excelColumn): array
    {
        $norm = $this->norm($excelColumn);

        // Layer 1 — exact (case-insens) on name or slug.
        if (isset($this->servicesByNorm[$norm])) {
            $svc = $this->servicesByNorm[$norm];
            return [
                'excel'        => $excelColumn,
                'service_id'   => $svc->id,
                'service_name' => $svc->name,
                'confidence'   => 'exact',
                'suggestion'   => null,
            ];
        }

        // Layer 2 — saved alias.
        if (array_key_exists($norm, $this->savedMappings)) {
            $sid = $this->savedMappings[$norm];
            $svc = $sid ? Service::find($sid) : null;
            return [
                'excel'        => $excelColumn,
                'service_id'   => $sid,
                'service_name' => $svc?->name,
                'confidence'   => $sid === null ? 'ignored' : 'alias',
                'suggestion'   => null,
            ];
        }

        // Layer 3 — fuzzy match against service names + slugs.
        $bestScore = 0.0;
        $bestSvc   = null;
        foreach ($this->servicesByNorm as $key => $svc) {
            similar_text($norm, $key, $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $bestSvc   = $svc;
            }
        }
        if ($bestSvc && $bestScore >= self::FUZZY_THRESHOLD) {
            return [
                'excel'        => $excelColumn,
                'service_id'   => $bestSvc->id,
                'service_name' => $bestSvc->name,
                'confidence'   => 'fuzzy',
                'suggestion'   => sprintf('%.0f%% match: %s', $bestScore, $bestSvc->name),
            ];
        }

        // Layer 4 — unmapped.
        return [
            'excel'        => $excelColumn,
            'service_id'   => null,
            'service_name' => null,
            'confidence'   => 'unmapped',
            'suggestion'   => $bestSvc ? sprintf('%.0f%% (below threshold): %s', $bestScore, $bestSvc->name) : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function loadRows(string $absolutePath): array
    {
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\WithHeadingRow, \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array)
            {
                return $array;
            }
        }, $absolutePath);

        return $sheets[0] ?? [];
    }

    public function norm(?string $s): string
    {
        if ($s === null) return '';
        // Lowercase, trim, collapse whitespace + underscores + hyphens.
        $s = strtolower(trim($s));
        $s = preg_replace('/[_\-]+/u', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s ?? '';
    }

    /**
     * Heading-row mode auto-normalises (lowercases, replaces spaces).
     * Operators write "Make" / "Model" / "Fuel_Type" — Maatwebsite
     * gives us "make" / "model" / "fuel_type". We accept either.
     */
    public function fuzzyVehicleField(array $row, string $field): ?string
    {
        // Build candidate-key list: the field itself, its underscore-
        // collapsed form, and any declared aliases (so "Brand" column
        // headers match the canonical "make" field).
        $candidates = [$field, str_replace('_', '', $field)];
        foreach (self::VEHICLE_FIELD_ALIASES[$field] ?? [] as $alias) {
            $candidates[] = $alias;
            $candidates[] = str_replace('_', '', $alias);
        }

        foreach (array_unique($candidates) as $candidate) {
            if (isset($row[$candidate])) {
                $v = trim((string) $row[$candidate]);
                if ($v !== '') return $v;
            }
        }
        // Case-insensitive last-ditch against any alias.
        $accepted = array_map('strtolower', $candidates);
        foreach ($row as $k => $v) {
            if (in_array(strtolower((string) $k), $accepted, true) && $v !== null) {
                $trim = trim((string) $v);
                if ($trim !== '') return $trim;
            }
        }
        return null;
    }
}
