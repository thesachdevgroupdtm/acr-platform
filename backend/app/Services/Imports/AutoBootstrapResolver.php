<?php

namespace App\Services\Imports;

use App\Exceptions\AutoBootstrapException;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceColumnMapping;
use App\Services\Imports\DTOs\EntitySummary;
use App\Services\Imports\Strategies\FuzzyMatcher;
use App\Services\Imports\Strategies\SectionHeaderDetector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — Auto-bootstrap resolver for the
 * pricing-matrix import flow. STRICT dry-run / persist separation:
 *
 *   resolveDryRun($file): BootstrapReport
 *     - Read-only. Only SELECT queries. NEVER writes.
 *     - Idempotent on identical (file, DB state) inputs.
 *     - Called by PricingMatrixImportPage::analyze().
 *
 *   resolveAndPersist($file, $importId): BootstrapReport
 *     - MUST be called inside the caller's DB::transaction so that a
 *       failure in either bootstrap OR the downstream importer rolls
 *       back every entity the bootstrap created.
 *     - Called by PricingMatrixImportPage::commit().
 *
 * Both methods share parseInventory() — read raw Excel rows, classify
 * the header row, detect section banners, extract unique brand /
 * model / fuel / service-column values.
 *
 * Importer core (PricingMatrixImporter, PricingMatrixPreviewService)
 * stays untouched. After resolveAndPersist commits the entities, the
 * importer's existing strict-lookup queries find everything.
 */
class AutoBootstrapResolver
{
    public const SOURCE_TAG = 'pricing_matrix_import';
    public const FALLBACK_CATEGORY_SLUG = 'imported-services';
    public const FALLBACK_CATEGORY_NAME = 'Imported Services';

    /** Header keywords that mark the column-header row. */
    private const VEHICLE_HEADER_KEYWORDS = ['carid', 'make', 'brand', 'model', 'fueltype', 'fuel', 'segment'];

    private const VEHICLE_FIELD_LABELS = [
        'make'      => ['make', 'brand'],
        'model'     => ['model'],
        'fuel_type' => ['fueltype', 'fuel'],
        'segment'   => ['segment'],
        'car_id'    => ['carid', 'id'],
    ];

    public function __construct(
        private readonly FuzzyMatcher $matcher,
        private readonly SectionHeaderDetector $sectionDetector,
    ) {
    }

    // ─── PUBLIC API ──────────────────────────────────────────────────

    /**
     * Read-only dry-run. Walks the file, queries the DB for fuzzy
     * matching, reports what WOULD be created on commit. Guarantees
     * zero DB writes.
     */
    public function resolveDryRun(string $absoluteFilePath): BootstrapReport
    {
        Log::info('Phase4.3.5: resolveDryRun() started', [
            'file' => basename($absoluteFilePath),
        ]);

        $inventory = $this->parseInventory($absoluteFilePath);

        // Categories — including implicit fallback if any service column
        // has no detected section.
        $categoryNames = $this->collectCategoryNames($inventory);
        $categoryReport = $this->matchEntitiesDryRun(
            $categoryNames,
            ServiceCategory::all(),
            'name',
        );

        $brandReport = $this->matchEntitiesDryRun(
            $inventory['brands'],
            CarBrand::all(),
            'name',
        );

        // Models — scoped per brand. Each unique (brand, model) pair
        // is matched against models under the brand's would-match row,
        // or treated as wouldCreate if the brand itself wouldCreate.
        $modelReport = $this->matchModelsDryRun(
            $inventory['models'],
            $brandReport,
        );

        $fuelReport = $this->matchEntitiesDryRun(
            $inventory['fuels'],
            FuelType::all(),
            'name',
        );

        $serviceReport = $this->matchEntitiesDryRun(
            $inventory['service_columns'],
            Service::all(),
            'name',
        );

        $report = new BootstrapReport(
            brands:     $brandReport,
            models:     $modelReport,
            fuelTypes:  $fuelReport,
            services:   $serviceReport,
            categories: $categoryReport,
            isDryRun:   true,
            importId:   null,
        );

        Log::info('Phase4.3.5: resolveDryRun() finished', [
            'would_create_brands'     => $report->brands->wouldCreate,
            'would_create_models'     => $report->models->wouldCreate,
            'would_create_fuels'      => $report->fuelTypes->wouldCreate,
            'would_create_services'   => $report->services->wouldCreate,
            'would_create_categories' => $report->categories->wouldCreate,
        ]);

        return $report;
    }

    /**
     * Persistent bootstrap. MUST be called inside the caller's
     * DB::transaction; we do NOT open our own so any exception
     * raised by the downstream importer also rolls back our creates.
     *
     * @throws AutoBootstrapException on persistence failure
     */
    public function resolveAndPersist(string $absoluteFilePath, ?int $importId): BootstrapReport
    {
        Log::info('Phase4.3.5: resolveAndPersist() started', [
            'file'      => basename($absoluteFilePath),
            'import_id' => $importId,
        ]);

        try {
            $inventory = $this->parseInventory($absoluteFilePath);

            // Step 1 — Categories (services depend on them).
            $categoryNames  = $this->collectCategoryNames($inventory);
            $categoryReport = $this->persistCategories($categoryNames, $importId);

            // Step 2 — Brands.
            $brandReport = $this->persistBrands($inventory['brands'], $importId);

            // Step 3 — Models, scoped by brand.
            $modelReport = $this->persistModels(
                $inventory['models'],
                $brandReport['name_to_id'],
                $importId,
            );

            // Step 4 — Fuel types.
            $fuelReport = $this->persistFuels($inventory['fuels'], $importId);

            // Step 5 — Services (with category) + alias rows.
            $serviceReport = $this->persistServices(
                $inventory['service_columns'],
                $inventory['column_to_section'],
                $categoryReport['name_to_id'],
                $importId,
            );

            $report = new BootstrapReport(
                brands:     $brandReport['summary'],
                models:     $modelReport['summary'],
                fuelTypes:  $fuelReport['summary'],
                services:   $serviceReport['summary'],
                categories: $categoryReport['summary'],
                isDryRun:   false,
                importId:   $importId,
            );

            Log::info('Phase4.3.5: resolveAndPersist() finished', [
                'import_id'       => $importId,
                'created_brands'  => $report->brands->created,
                'created_models'  => $report->models->created,
                'created_fuels'   => $report->fuelTypes->created,
                'created_services' => $report->services->created,
                'created_categories' => $report->categories->created,
            ]);

            return $report;
        } catch (\Throwable $e) {
            // Re-raise as AutoBootstrapException so the outer caller's
            // catch block has a typed handle. The DB::transaction the
            // caller opened will roll back automatically when this
            // bubbles up.
            throw AutoBootstrapException::persistenceError(
                "Bootstrap failed mid-flight: " . $e->getMessage(),
                $e,
            );
        }
    }

    // ─── parseInventory (shared) ─────────────────────────────────────

    /**
     * Read raw Excel (no heading-row formatter — preserves casing for
     * service names) and classify rows into an inventory.
     *
     * Layout A (single-row header):
     *   row 0: [Car_id, Make, Model, Fuel_Type, Segment, Svc1, Svc2, ...]
     *   row 1+: data
     *   sections: empty; service columns inherit FALLBACK_CATEGORY
     *
     * Layout B (banner row above header row):
     *   row 0: [_, _, _, _, _, "Battery", _, _, "Brake", _, ...]
     *   row 1: [Car_id, Make, Model, Fuel_Type, Segment, Svc1, Svc2, ...]
     *   row 2+: data
     *   sections: detected via SectionHeaderDetector
     *
     * @return array{
     *   brands: array<int, string>,
     *   models: array<int, array{brand:string, name:string}>,
     *   fuels: array<int, string>,
     *   service_columns: array<int, string>,
     *   sections: array<int, string>,
     *   column_to_section: array<string, ?string>,
     * }
     *
     * @throws AutoBootstrapException on malformed input
     */
    private function parseInventory(string $absolutePath): array
    {
        try {
            $sheets = Excel::toArray($this->rawReader(), $absolutePath);
        } catch (\Throwable $e) {
            throw AutoBootstrapException::parseError(
                "Failed to read Excel file: " . $e->getMessage(),
            );
        }

        $rows = $sheets[0] ?? [];
        if (empty($rows)) {
            return $this->emptyInventory();
        }

        $headerIdx = $this->detectHeaderRow($rows);
        if ($headerIdx === null) {
            throw AutoBootstrapException::parseError(
                "Could not locate the column-header row (no Make/Model/Fuel keywords found)",
            );
        }

        $headerRow   = array_map(fn ($v) => trim((string) $v), $rows[$headerIdx]);
        $vehicleCols = $this->locateVehicleColumns($headerRow);

        // Service columns + their original index in the header row.
        $serviceColumns     = [];
        $serviceColumnByIdx = [];
        foreach ($headerRow as $i => $cell) {
            $i = (int) $i;
            if ($cell === '' || in_array($i, $vehicleCols, true)) {
                continue;
            }
            $serviceColumns[]       = $cell;
            $serviceColumnByIdx[$i] = $cell;
        }

        // Section detection over rows ABOVE the header row.
        $bannerRows       = array_slice($rows, 0, $headerIdx);
        $detectedSections = $this->sectionDetector->detect($bannerRows);

        $columnToSection = [];
        $sectionsSeen    = [];
        foreach ($serviceColumnByIdx as $colIdx => $colName) {
            $section = $detectedSections[$colIdx] ?? null;
            $columnToSection[$colName] = $section;
            if ($section !== null) {
                $sectionsSeen[$this->matcher->normalize($section)] ??= $section;
            }
        }

        // Data rows — extract uniques.
        $brands  = [];
        $models  = [];
        $fuels   = [];
        $dataRows = array_slice($rows, $headerIdx + 1);
        foreach ($dataRows as $r) {
            if (! is_array($r)) {
                continue;
            }
            $brand = $this->cellAt($r, $vehicleCols['make'] ?? null);
            $model = $this->cellAt($r, $vehicleCols['model'] ?? null);
            $fuel  = $this->cellAt($r, $vehicleCols['fuel_type'] ?? null);

            if ($brand !== null) {
                $brands[$this->matcher->normalize($brand)] ??= $brand;
            }
            if ($brand !== null && $model !== null) {
                $key = $this->matcher->normalize($brand) . '|' . $this->matcher->normalize($model);
                $models[$key] ??= ['brand' => $brand, 'name' => $model];
            }
            if ($fuel !== null) {
                $fuels[$this->matcher->normalize($fuel)] ??= $fuel;
            }
        }

        return [
            'brands'            => array_values($brands),
            'models'            => array_values($models),
            'fuels'             => array_values($fuels),
            'service_columns'   => $serviceColumns,
            'sections'          => array_values($sectionsSeen),
            'column_to_section' => $columnToSection,
        ];
    }

    /** Single-row anonymous reader that returns raw cell values. */
    private function rawReader(): ToArray
    {
        return new class implements ToArray {
            public function array(array $array) {}
        };
    }

    private function emptyInventory(): array
    {
        return [
            'brands'            => [],
            'models'            => [],
            'fuels'             => [],
            'service_columns'   => [],
            'sections'          => [],
            'column_to_section' => [],
        ];
    }

    private function detectHeaderRow(array $rows): ?int
    {
        foreach ($rows as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $cell) {
                $norm = $this->matcher->normalize((string) $cell);
                if (in_array($norm, self::VEHICLE_HEADER_KEYWORDS, true)) {
                    return (int) $idx;
                }
            }
        }
        return null;
    }

    /**
     * @return array<string, int>  with `_indices` listing all used cols
     */
    private function locateVehicleColumns(array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $i => $cell) {
            $norm = $this->matcher->normalize((string) $cell);
            foreach (self::VEHICLE_FIELD_LABELS as $field => $accepted) {
                if (in_array($norm, $accepted, true)) {
                    $out[$field] = (int) $i;
                    break;
                }
            }
        }
        $out['_indices'] = array_values(array_filter($out, fn ($k) => $k !== '_indices', ARRAY_FILTER_USE_KEY));
        // Flatten so the array_intersect / in_array usages can be index-only.
        $indices = [];
        foreach ($out as $k => $v) {
            if ($k === '_indices') {
                continue;
            }
            $indices[] = $v;
        }
        $out['_indices'] = $indices;
        return $out;
    }

    private function cellAt(array $row, ?int $idx): ?string
    {
        if ($idx === null || ! array_key_exists($idx, $row)) {
            return null;
        }
        $v = trim((string) $row[$idx]);
        return $v === '' ? null : $v;
    }

    /**
     * Categories used = unique sections detected + the fallback name
     * if any column has no section assigned.
     *
     * @return array<int, string>
     */
    private function collectCategoryNames(array $inventory): array
    {
        $names = $inventory['sections'];
        foreach ($inventory['column_to_section'] as $section) {
            if ($section === null) {
                $names[] = self::FALLBACK_CATEGORY_NAME;
                break;
            }
        }
        // Dedupe by normalized form, keep first-seen casing.
        $unique = [];
        foreach ($names as $n) {
            $unique[$this->matcher->normalize($n)] ??= $n;
        }
        return array_values($unique);
    }

    // ─── DRY-RUN matchers (read-only, no writes) ─────────────────────

    /**
     * @param  array<int, string>  $inputNames
     * @param  iterable            $existing
     */
    private function matchEntitiesDryRun(array $inputNames, iterable $existing, string $field): EntitySummary
    {
        $matched      = 0;
        $wouldNames   = [];
        $existingCol  = is_array($existing) ? $existing : iterator_to_array($existing, false);

        // Simulate a "running set" as we walk: a fresh dry-run name
        // that resolves below threshold against the DB is still
        // considered new — but later identical inputs match it via
        // exact-norm equality so we don't double-count.
        $simulatedCreates = [];

        foreach ($inputNames as $name) {
            $dbMatch = $this->matcher->findBest($name, $existingCol, $field);
            if ($dbMatch !== null) {
                $matched++;
                continue;
            }

            // Check against simulated would-creates from this same run.
            $alreadyQueued = false;
            foreach ($simulatedCreates as $queued) {
                if ($this->matcher->similarity($name, $queued) >= FuzzyMatcher::DEFAULT_THRESHOLD) {
                    $alreadyQueued = true;
                    break;
                }
            }
            if ($alreadyQueued) {
                continue;
            }

            $simulatedCreates[] = $name;
            $wouldNames[]       = $name;
        }

        return EntitySummary::dryRun($matched, $wouldNames);
    }

    /**
     * Per-brand model matching. Models belonging to a brand that's
     * would-create are themselves would-create (we can't match against
     * a brand row that doesn't exist yet).
     */
    private function matchModelsDryRun(array $models, EntitySummary $brandReport): EntitySummary
    {
        $matched       = 0;
        $wouldNames    = [];
        $brandsToBeNew = array_flip(array_map(fn ($n) => $this->matcher->normalize($n), $brandReport->previewNames));

        // Pre-load all models grouped by brand_id for efficiency.
        $existingModelsByBrandId = [];
        foreach (CarModel::all() as $m) {
            $existingModelsByBrandId[$m->brand_id][] = $m;
        }

        // Resolve each brand name to either an existing CarBrand id or
        // a sentinel for "to-be-created" so we can scope model matches.
        $existingBrands = CarBrand::all();
        $simulatedModelsByBrandKey = [];

        foreach ($models as $entry) {
            ['brand' => $brandName, 'name' => $modelName] = $entry;
            $brandNorm = $this->matcher->normalize($brandName);

            if (isset($brandsToBeNew[$brandNorm])) {
                // Brand will be new on commit → model is also new.
                $brandKey = 'new:' . $brandNorm;
                $brandModels = [];
            } else {
                $brandMatch = $this->matcher->findBest($brandName, $existingBrands, 'name');
                if ($brandMatch === null) {
                    // Defensive — shouldn't happen because brand report
                    // already covered every input brand. Skip rather
                    // than throw on a dry-run.
                    continue;
                }
                $brandKey    = 'existing:' . $brandMatch['entity']->id;
                $brandModels = $existingModelsByBrandId[$brandMatch['entity']->id] ?? [];
            }

            $dbMatch = $this->matcher->findBest($modelName, $brandModels, 'name');
            if ($dbMatch !== null) {
                $matched++;
                continue;
            }

            $simulatedModelsByBrandKey[$brandKey] ??= [];
            $alreadyQueued = false;
            foreach ($simulatedModelsByBrandKey[$brandKey] as $queued) {
                if ($this->matcher->similarity($modelName, $queued) >= FuzzyMatcher::DEFAULT_THRESHOLD) {
                    $alreadyQueued = true;
                    break;
                }
            }
            if ($alreadyQueued) {
                continue;
            }
            $simulatedModelsByBrandKey[$brandKey][] = $modelName;
            $wouldNames[] = "{$modelName} ({$brandName})";
        }

        return EntitySummary::dryRun($matched, $wouldNames);
    }

    // ─── PERSIST creators (called inside caller's DB::transaction) ───

    /**
     * @param  array<int, string>  $names
     * @return array{summary: EntitySummary, name_to_id: array<string, int>}
     */
    private function persistCategories(array $names, ?int $importId): array
    {
        $matched      = 0;
        $createdNames = [];
        $createdIds   = [];
        $nameToId     = [];
        $existing     = ServiceCategory::all();

        foreach ($names as $name) {
            $match = $this->matcher->findBest($name, $existing, 'name');
            if ($match !== null) {
                $nameToId[$name] = $match['entity']->id;
                $matched++;
                continue;
            }

            $entity = ServiceCategory::create([
                'name'                   => $name,
                'slug'                   => $this->uniqueSlug(ServiceCategory::class, $name),
                'position'               => 999,
                'is_active'              => true,
                'is_auto_created'        => true,
                'auto_created_from'      => self::SOURCE_TAG,
                'auto_created_import_id' => $importId,
                'include_in_sitemap'     => false,
            ]);
            $existing->push($entity);
            $nameToId[$name] = $entity->id;
            $createdNames[]  = $entity->name;
            $createdIds[]    = $entity->id;
        }

        return [
            'summary'    => EntitySummary::persisted($matched, $createdNames, $createdIds),
            'name_to_id' => $nameToId,
        ];
    }

    /**
     * @param  array<int, string>  $names
     * @return array{summary: EntitySummary, name_to_id: array<string, int>}
     */
    private function persistBrands(array $names, ?int $importId): array
    {
        $matched      = 0;
        $createdNames = [];
        $createdIds   = [];
        $nameToId     = [];
        $existing     = CarBrand::all();

        foreach ($names as $name) {
            $match = $this->matcher->findBest($name, $existing, 'name');
            if ($match !== null) {
                $nameToId[$name] = $match['entity']->id;
                $matched++;
                continue;
            }

            $entity = CarBrand::create([
                'name'                   => $name,
                'slug'                   => $this->uniqueSlug(CarBrand::class, $name),
                'is_active'              => true,
                'is_auto_created'        => true,
                'auto_created_from'      => self::SOURCE_TAG,
                'auto_created_import_id' => $importId,
                'include_in_sitemap'     => false,
            ]);
            $existing->push($entity);
            $nameToId[$name] = $entity->id;
            $createdNames[]  = $entity->name;
            $createdIds[]    = $entity->id;
        }

        return [
            'summary'    => EntitySummary::persisted($matched, $createdNames, $createdIds),
            'name_to_id' => $nameToId,
        ];
    }

    /**
     * @param  array<int, array{brand:string, name:string}>  $models
     * @param  array<string, int>                            $brandNameToId
     */
    private function persistModels(array $models, array $brandNameToId, ?int $importId): array
    {
        $matched      = 0;
        $createdNames = [];
        $createdIds   = [];

        // Group requested models by brand_id for efficient batching.
        $byBrandId = [];
        foreach ($models as $m) {
            $brandId = $brandNameToId[$m['brand']] ?? null;
            if ($brandId === null) {
                continue;
            }
            $byBrandId[$brandId][] = $m['name'];
        }

        foreach ($byBrandId as $brandId => $modelNames) {
            $existing = CarModel::where('brand_id', $brandId)->get();
            foreach ($modelNames as $modelName) {
                $match = $this->matcher->findBest($modelName, $existing, 'name');
                if ($match !== null) {
                    $matched++;
                    continue;
                }
                $entity = CarModel::create([
                    'brand_id'               => $brandId,
                    'name'                   => $modelName,
                    'slug'                   => $this->uniqueSlug(CarModel::class, $modelName),
                    'is_active'              => true,
                    'is_auto_created'        => true,
                    'auto_created_from'      => self::SOURCE_TAG,
                    'auto_created_import_id' => $importId,
                    'include_in_sitemap'     => false,
                ]);
                $existing->push($entity);
                $createdNames[] = $entity->name;
                $createdIds[]   = $entity->id;
            }
        }

        return [
            'summary' => EntitySummary::persisted($matched, $createdNames, $createdIds),
        ];
    }

    private function persistFuels(array $names, ?int $importId): array
    {
        $matched      = 0;
        $createdNames = [];
        $createdIds   = [];
        $existing     = FuelType::all();

        foreach ($names as $name) {
            $match = $this->matcher->findBest($name, $existing, 'name');
            if ($match !== null) {
                $matched++;
                continue;
            }
            $entity = FuelType::create([
                'name'                   => $name,
                'slug'                   => $this->uniqueSlug(FuelType::class, $name),
                'is_active'              => true,
                'is_auto_created'        => true,
                'auto_created_from'      => self::SOURCE_TAG,
                'auto_created_import_id' => $importId,
                'include_in_sitemap'     => false,
            ]);
            $existing->push($entity);
            $createdNames[] = $entity->name;
            $createdIds[]   = $entity->id;
        }

        return [
            'summary' => EntitySummary::persisted($matched, $createdNames, $createdIds),
        ];
    }

    /**
     * @param  array<int, string>      $columns
     * @param  array<string, ?string>  $columnToSection
     * @param  array<string, int>      $categoryNameToId
     */
    private function persistServices(
        array $columns,
        array $columnToSection,
        array $categoryNameToId,
        ?int $importId,
    ): array {
        $matched      = 0;
        $createdNames = [];
        $createdIds   = [];
        $existing     = Service::all();
        $fallbackId   = $categoryNameToId[self::FALLBACK_CATEGORY_NAME] ?? null;

        foreach ($columns as $column) {
            $match = $this->matcher->findBest($column, $existing, 'name');
            if ($match !== null) {
                $matched++;
                $this->ensureColumnMapping($column, $match['entity']->id);
                continue;
            }

            $sectionName = $columnToSection[$column] ?? null;
            $categoryId  = ($sectionName !== null && isset($categoryNameToId[$sectionName]))
                ? $categoryNameToId[$sectionName]
                : $fallbackId;

            if ($categoryId === null) {
                // Should never happen — collectCategoryNames ensures
                // the fallback is in the report. Defensive throw.
                throw AutoBootstrapException::persistenceError(
                    "No category resolved for service column '{$column}' and no fallback available",
                );
            }

            $entity = Service::create([
                'name'                   => $column,
                'slug'                   => $this->uniqueSlug(Service::class, $column),
                'category_id'            => $categoryId,
                'is_active'              => true,
                'is_auto_created'        => true,
                'auto_created_from'      => self::SOURCE_TAG,
                'auto_created_import_id' => $importId,
                'include_in_sitemap'     => false,
            ]);
            $existing->push($entity);
            $createdNames[] = $entity->name;
            $createdIds[]   = $entity->id;

            $this->ensureColumnMapping($column, $entity->id);
        }

        return [
            'summary' => EntitySummary::persisted($matched, $createdNames, $createdIds),
        ];
    }

    private function ensureColumnMapping(string $excelColumn, int $serviceId): void
    {
        // Align the alias key with what PricingMatrixImporter sees at
        // commit-time. Maatwebsite's HeadingRowImport applies
        // FORMATTER_SLUG (Str::slug with '_' separator) to every header
        // before the importer's $overrides reach it. Storing the raw
        // (e.g. "PMIP Svc A xyz") here would let the importer's
        // updateOrCreate(['excel_column' => 'pmip_svc_a_xyz']) miss
        // our row and create a duplicate. Snapping to the slug form
        // makes our alias key match.
        $key = Str::slug($excelColumn, '_');
        if ($key === '') {
            $key = $excelColumn;
        }

        ServiceColumnMapping::firstOrCreate(
            ['excel_column' => $key],
            [
                'service_id' => $serviceId,
                'is_active'  => true,
                'created_by' => auth()->id(),
            ],
        );
    }

    /**
     * Slug from name with -2, -3, ... suffix on collision against the
     * given Eloquent model class.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function uniqueSlug(string $modelClass, string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'auto-' . Str::lower(Str::random(6));
        }
        $slug = $base;
        $i    = 1;
        while ($modelClass::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }
}
