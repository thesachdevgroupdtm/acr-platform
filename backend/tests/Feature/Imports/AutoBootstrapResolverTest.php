<?php

use App\Exceptions\AutoBootstrapException;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceColumnMapping;
use App\Services\Imports\AutoBootstrapResolver;
use App\Services\Imports\BootstrapReport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — feature coverage for AutoBootstrapResolver.
 *
 * Twelve scenarios pinning the dry-run / persist contract:
 *   1.  dry-run NEVER writes
 *   2.  dry-run counts are accurate
 *   3.  dry-run identifies fuzzy-matched existing entities
 *   4.  commit creates entities with the right audit-trail fields
 *   5.  commit is transactionally atomic — rollback on failure
 *   6.  commit creates models scoped by brand
 *   7.  commit creates services under auto-detected category
 *   8.  commit falls back to "Imported Services" when no section
 *   9.  re-upload of same file is idempotent
 *   10. ServiceColumnMapping rows persisted per service column
 *   11. fuzzy threshold of 85% honoured at the boundary
 *   12. dry-run is deterministic across multiple runs
 */

/** Build a small xlsx in storage/app/imports for resolver to read. */
function abrtBuildXlsx(array $headers, array $rows, string $filename): string
{
    $export = new class($headers, $rows) implements FromArray, WithHeadings {
        public function __construct(public array $h, public array $r) {}
        public function headings(): array { return $this->h; }
        public function array(): array { return $this->r; }
    };
    Excel::store($export, "imports/{$filename}", 'local');
    return storage_path("app/imports/{$filename}");
}

function abrtResolver(): AutoBootstrapResolver
{
    return app(AutoBootstrapResolver::class);
}

it('dry-run does not create any entities', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Frunk Detailing'],
        [[1, 'Ferrari', 'F8', 'Petrol', 'Super', 10000]],
        'abrt-dry-noop-' . uniqid() . '.xlsx',
    );

    $beforeBrands   = CarBrand::count();
    $beforeModels   = CarModel::count();
    $beforeFuels    = FuelType::count();
    $beforeServices = Service::count();
    $beforeCats     = ServiceCategory::count();
    $beforeMaps     = ServiceColumnMapping::count();

    $report = abrtResolver()->resolveDryRun($abs);

    expect($report)->toBeInstanceOf(BootstrapReport::class);
    expect($report->isDryRun)->toBeTrue();
    expect($report->importId)->toBeNull();

    expect(CarBrand::count())->toBe($beforeBrands);
    expect(CarModel::count())->toBe($beforeModels);
    expect(FuelType::count())->toBe($beforeFuels);
    expect(Service::count())->toBe($beforeServices);
    expect(ServiceCategory::count())->toBe($beforeCats);
    expect(ServiceColumnMapping::count())->toBe($beforeMaps);
});

it('dry-run reports accurate would-create counts', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Brand-New Svc'],
        [
            [1, 'Lamborghini', 'Huracan', 'Petrol', 'Super', 8500],
            [2, 'Lamborghini', 'Aventador', 'Petrol', 'Super', 12000],
        ],
        'abrt-dry-counts-' . uniqid() . '.xlsx',
    );

    $report = abrtResolver()->resolveDryRun($abs);

    expect($report->brands->wouldCreate)->toBe(1);
    expect($report->brands->previewNames)->toBe(['Lamborghini']);
    expect($report->models->wouldCreate)->toBe(2);
    expect($report->fuelTypes->wouldCreate)->toBe(1);
    expect($report->services->wouldCreate)->toBe(1);
    // No section banner → categories needs at minimum the fallback.
    expect($report->categories->wouldCreate)->toBeGreaterThanOrEqual(1);
});

it('dry-run identifies existing entities via fuzzy match', function () {
    CarBrand::create([
        'name' => 'Maruti Suzuki', 'slug' => 'maruti-suzuki', 'is_active' => true,
    ]);

    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Svc'],
        [[1, 'maruti suzuki', 'Swift', 'Petrol', 'Hatch', 1500]], // case difference
        'abrt-dry-fuzzy-' . uniqid() . '.xlsx',
    );

    $report = abrtResolver()->resolveDryRun($abs);
    expect($report->brands->matchedExisting)->toBe(1);
    expect($report->brands->wouldCreate)->toBe(0);
});

it('commit creates entities with correct audit fields', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Cabin Polishing'],
        [[1, 'Porsche', 'Cayenne', 'Petrol', 'SUV', 7800]],
        'abrt-commit-audit-' . uniqid() . '.xlsx',
    );

    DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 42));

    $brand = CarBrand::where('name', 'Porsche')->first();
    expect($brand)->not->toBeNull();
    expect($brand->is_auto_created)->toBeTrue();
    expect($brand->auto_created_from)->toBe(AutoBootstrapResolver::SOURCE_TAG);
    expect($brand->auto_created_import_id)->toBe(42);
    expect($brand->include_in_sitemap)->toBeFalse();
    expect($brand->is_active)->toBeTrue();

    $service = Service::where('name', 'Cabin Polishing')->first();
    expect($service)->not->toBeNull();
    expect($service->is_auto_created)->toBeTrue();
    expect($service->include_in_sitemap)->toBeFalse();
});

it('commit is transactionally atomic — rollback on failure', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Svc1', 'Svc2'],
        [[1, 'Bentley', 'Continental', 'Petrol', 'Luxury', 9000, 8000]],
        'abrt-rollback-' . uniqid() . '.xlsx',
    );

    $beforeBrands   = CarBrand::count();
    $beforeServices = Service::count();

    // Wrap caller-side and throw mid-flight to simulate downstream
    // importer failure. The outer transaction must roll back EVERY
    // entity the resolver created.
    try {
        DB::transaction(function () use ($abs) {
            abrtResolver()->resolveAndPersist($abs, 99);
            throw new \RuntimeException('simulated importer failure');
        });
    } catch (\RuntimeException $e) {
        // expected
    }

    expect(CarBrand::count())->toBe($beforeBrands);
    expect(Service::count())->toBe($beforeServices);
});

it('commit creates models scoped by brand', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Svc'],
        [
            [1, 'Honda', 'City', 'Petrol', 'Sedan', 1000],
            [2, 'Honda', 'Civic', 'Petrol', 'Sedan', 1200],
            [3, 'Toyota', 'Camry', 'Petrol', 'Sedan', 1500],
        ],
        'abrt-models-scoped-' . uniqid() . '.xlsx',
    );

    DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 1));

    $honda = CarBrand::where('name', 'Honda')->first();
    $toyota = CarBrand::where('name', 'Toyota')->first();
    expect(CarModel::where('brand_id', $honda->id)->where('name', 'City')->exists())->toBeTrue();
    expect(CarModel::where('brand_id', $honda->id)->where('name', 'Civic')->exists())->toBeTrue();
    expect(CarModel::where('brand_id', $toyota->id)->where('name', 'Camry')->exists())->toBeTrue();
    expect(CarModel::where('brand_id', $honda->id)->where('name', 'Camry')->exists())->toBeFalse();
});

it('commit creates services with auto-detected category', function () {
    // Layout B — banner row above header row. Build by hand using raw
    // array (not Maatwebsite, which would slugify the headers).
    $name = 'abrt-section-' . uniqid() . '.xlsx';
    $relPath = "imports/{$name}";

    // Two-row header layout: row 0 = banner, row 1 = column headers.
    $rawExport = new class implements \Maatwebsite\Excel\Concerns\FromArray {
        public function array(): array
        {
            return [
                ['', '', '', '', '', 'Battery', 'Battery', 'Paint'],
                ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Battery Replace', 'Battery Check', 'Bumper Paint'],
                [1, 'Audi', 'A4', 'Petrol', 'Sedan', 5000, 500, 3000],
            ];
        }
    };
    Excel::store($rawExport, $relPath, 'local');
    $abs = storage_path("app/{$relPath}");

    DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 1));

    $batteryCat = ServiceCategory::where('name', 'Battery')->first();
    $paintCat   = ServiceCategory::where('name', 'Paint')->first();
    expect($batteryCat)->not->toBeNull();
    expect($paintCat)->not->toBeNull();

    $batteryReplace = Service::where('name', 'Battery Replace')->first();
    $bumperPaint    = Service::where('name', 'Bumper Paint')->first();
    expect($batteryReplace->category_id)->toBe($batteryCat->id);
    expect($bumperPaint->category_id)->toBe($paintCat->id);
});

it('commit falls back to Imported Services when section undetected', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Mystery Service'],
        [[1, 'Audi', 'A3', 'Petrol', 'Hatch', 2000]],
        'abrt-fallback-' . uniqid() . '.xlsx',
    );

    DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 1));

    $fallback = ServiceCategory::where('slug', AutoBootstrapResolver::FALLBACK_CATEGORY_SLUG)->first();
    expect($fallback)->not->toBeNull();
    expect($fallback->name)->toBe(AutoBootstrapResolver::FALLBACK_CATEGORY_NAME);

    $service = Service::where('name', 'Mystery Service')->first();
    expect($service->category_id)->toBe($fallback->id);
});

it('re-upload of same file is idempotent', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Wheel Balance'],
        [[1, 'Volvo', 'XC90', 'Diesel', 'SUV', 3500]],
        'abrt-idem-' . uniqid() . '.xlsx',
    );

    DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 1));
    $afterFirst = [
        'brands'   => CarBrand::count(),
        'models'   => CarModel::count(),
        'fuels'    => FuelType::count(),
        'services' => Service::count(),
        'cats'     => ServiceCategory::count(),
    ];

    $report2 = DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 2));

    expect($report2->brands->created)->toBe(0);
    expect($report2->models->created)->toBe(0);
    expect($report2->fuelTypes->created)->toBe(0);
    expect($report2->services->created)->toBe(0);
    // Categories already cover; fallback exists.
    expect($report2->categories->created)->toBe(0);

    expect(CarBrand::count())->toBe($afterFirst['brands']);
    expect(CarModel::count())->toBe($afterFirst['models']);
    expect(FuelType::count())->toBe($afterFirst['fuels']);
    expect(Service::count())->toBe($afterFirst['services']);
    expect(ServiceCategory::count())->toBe($afterFirst['cats']);
});

it('creates ServiceColumnMapping for each service column on commit', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'AC Top Up', 'Cabin Filter'],
        [[1, 'Skoda', 'Octavia', 'Petrol', 'Sedan', 1200, 600]],
        'abrt-mapping-' . uniqid() . '.xlsx',
    );

    $beforeMaps = ServiceColumnMapping::count();
    DB::transaction(fn () => abrtResolver()->resolveAndPersist($abs, 1));

    // Resolver normalises excel_column to Str::slug($name, '_') to
    // align with the importer's downstream lookups.
    expect(ServiceColumnMapping::where('excel_column', 'ac_top_up')->exists())->toBeTrue();
    expect(ServiceColumnMapping::where('excel_column', 'cabin_filter')->exists())->toBeTrue();
    expect(ServiceColumnMapping::count())->toBe($beforeMaps + 2);
});

it('respects fuzzy threshold of 85% at the boundary', function () {
    // Seed an existing brand "Hondax" — a one-letter typo away from a
    // 5-char Honda. Levenshtein distance 1, max len 6 → similarity
    // 1 - 1/6 = 0.833 (below 0.85). The resolver should NOT match.
    CarBrand::create(['name' => 'Hondax', 'slug' => 'hondax', 'is_active' => true]);

    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Svc'],
        [[1, 'Honda', 'City', 'Petrol', 'Sedan', 1000]],
        'abrt-threshold-' . uniqid() . '.xlsx',
    );

    $report = abrtResolver()->resolveDryRun($abs);
    // The 'Honda' input should NOT match 'Hondax' (below threshold).
    // So it counts as a wouldCreate.
    expect($report->brands->wouldCreate)->toBe(1);
    expect($report->brands->previewNames)->toBe(['Honda']);
});

it('produces deterministic results across multiple dry runs', function () {
    $abs = abrtBuildXlsx(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Svc One', 'Svc Two'],
        [
            [1, 'Tata', 'Nexon', 'Diesel', 'SUV', 2000, 1500],
            [2, 'Mahindra', 'XUV700', 'Diesel', 'SUV', 3000, 1800],
        ],
        'abrt-determ-' . uniqid() . '.xlsx',
    );

    $r1 = abrtResolver()->resolveDryRun($abs);
    $r2 = abrtResolver()->resolveDryRun($abs);
    $r3 = abrtResolver()->resolveDryRun($abs);

    expect($r1->toArray())->toEqual($r2->toArray());
    expect($r2->toArray())->toEqual($r3->toArray());
});
