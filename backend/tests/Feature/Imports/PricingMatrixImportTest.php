<?php

use App\Imports\BaseImport;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceColumnMapping;
use App\Services\Imports\PricingMatrixImporter;
use App\Services\Imports\PricingMatrixPreviewService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Phase 4.3 — PricingMatrixImport CORE tests.
 *
 * We build small fixture XLSXs in-process (no test fixtures
 * checked into git) and run the same preview + commit flow the
 * Filament page does.
 */

/**
 * Helper to write an array-based XLSX into the test storage dir
 * and return the absolute path.
 */
function writeMatrixXlsx(array $headers, array $rows, string $name = null): string
{
    Storage::fake('local');
    $name = $name ?? ('test-matrix-' . uniqid() . '.xlsx');

    $export = new class($headers, $rows) implements FromArray, WithHeadings {
        public function __construct(public array $headers, public array $rows) {}
        public function headings(): array { return $this->headers; }
        public function array(): array { return $this->rows; }
    };

    Excel::store($export, $name, 'local');
    return Storage::disk('local')->path($name);
}

/** Seed a small but complete vehicle universe for matrix tests. */
function seedMatrixVehicleUniverse(): array
{
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'matrix-test-cat-' . uniqid()],
        ['name' => 'Matrix Test Cat', 'position' => 99, 'is_active' => true]
    );
    $brand = CarBrand::firstOrCreate(['slug' => 'audi'], ['name' => 'Audi', 'is_active' => true]);
    $model = CarModel::firstOrCreate(
        ['brand_id' => $brand->id, 'slug' => 'a3'],
        ['name' => 'A3', 'is_active' => true],
    );
    $fuel  = FuelType::firstOrCreate(['slug' => 'petrol'], ['name' => 'Petrol', 'is_active' => true]);

    $svcA = Service::firstOrCreate(
        ['slug' => 'matrix-svc-a-' . uniqid()],
        ['name' => 'Matrix Svc A', 'category_id' => $cat->id, 'is_active' => true]
    );
    $svcB = Service::firstOrCreate(
        ['slug' => 'matrix-svc-b-' . uniqid()],
        ['name' => 'Matrix Svc B', 'category_id' => $cat->id, 'is_active' => true]
    );

    return compact('brand', 'model', 'fuel', 'svcA', 'svcB');
}

it('PricingMatrixImporter inserts new price rows for a 1-vehicle file', function () {
    ['brand' => $brand, 'model' => $model, 'fuel' => $fuel, 'svcA' => $svcA, 'svcB' => $svcB] = seedMatrixVehicleUniverse();

    $path = writeMatrixXlsx(
        headers: ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $svcA->name, $svcB->name],
        rows:    [[1, 'Audi', 'A3', 'Petrol', 'Luxury', 1500, 2500]],
    );

    $before = DB::table('service_prices')->count();
    $preview = app(PricingMatrixPreviewService::class);
    $importer = new PricingMatrixImporter($preview);
    $importer->commit($path);

    expect($importer->inserted)->toBe(2);
    expect($importer->updated)->toBe(0);
    expect(DB::table('service_prices')->count())->toBe($before + 2);

    expect((float) DB::table('service_prices')
        ->where('service_id', $svcA->id)
        ->where('brand_id', $brand->id)
        ->where('model_id', $model->id)
        ->where('fuel_type_id', $fuel->id)
        ->value('price'))->toBe(1500.0);
});

it('PricingMatrixImporter skips NA, empty, and skip-token cells', function () {
    ['brand' => $brand, 'svcA' => $svcA, 'svcB' => $svcB] = seedMatrixVehicleUniverse();

    $path = writeMatrixXlsx(
        headers: ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $svcA->name, $svcB->name],
        rows:    [[1, 'Audi', 'A3', 'Petrol', 'Luxury', 'NA', '']],
    );

    $importer = new PricingMatrixImporter(app(PricingMatrixPreviewService::class));
    $importer->commit($path);

    expect($importer->inserted)->toBe(0);
    expect($importer->skipped)->toBe(2);
});

it('PricingMatrixImporter updates existing price rows instead of duplicating', function () {
    ['brand' => $brand, 'model' => $model, 'fuel' => $fuel, 'svcA' => $svcA] = seedMatrixVehicleUniverse();

    // Pre-existing row.
    DB::table('service_prices')->insert([
        'service_id'   => $svcA->id,
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'price'        => 1000.0,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $path = writeMatrixXlsx(
        headers: ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $svcA->name],
        rows:    [[1, 'Audi', 'A3', 'Petrol', 'Luxury', 2222]],
    );

    $importer = new PricingMatrixImporter(app(PricingMatrixPreviewService::class));
    $importer->commit($path);

    expect($importer->inserted)->toBe(0);
    expect($importer->updated)->toBe(1);

    expect((float) DB::table('service_prices')
        ->where('service_id', $svcA->id)
        ->where('brand_id', $brand->id)
        ->where('model_id', $model->id)
        ->where('fuel_type_id', $fuel->id)
        ->value('price'))->toBe(2222.0);
});

it('PricingMatrixImporter rejects negative prices and counts them as invalid', function () {
    ['svcA' => $svcA] = seedMatrixVehicleUniverse();

    $path = writeMatrixXlsx(
        headers: ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $svcA->name],
        rows:    [[1, 'Audi', 'A3', 'Petrol', 'Luxury', -100]],
    );

    $importer = new PricingMatrixImporter(app(PricingMatrixPreviewService::class));
    $importer->commit($path);

    expect($importer->invalid)->toBe(1);
    expect($importer->inserted)->toBe(0);
});

it('PricingMatrixImporter skips an entire row whose vehicle FKs are invalid', function () {
    ['svcA' => $svcA] = seedMatrixVehicleUniverse();

    $path = writeMatrixXlsx(
        headers: ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $svcA->name],
        rows:    [[1, 'NotARealBrand', 'NoModel', 'Petrol', 'Luxury', 999]],
    );

    $before = DB::table('service_prices')->count();
    $importer = new PricingMatrixImporter(app(PricingMatrixPreviewService::class));
    $importer->commit($path);

    expect($importer->inserted)->toBe(0);
    expect(DB::table('service_prices')->count())->toBe($before);
});

it('PreviewService resolveColumn exact-matches a service by name', function () {
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'resolve-cat-' . uniqid()],
        ['name' => 'Resolve Cat', 'position' => 99, 'is_active' => true],
    );
    $svc = Service::firstOrCreate(
        ['slug' => 'resolve-test-svc-' . uniqid()],
        ['name' => 'Resolve Test Service', 'category_id' => $cat->id, 'is_active' => true],
    );

    $svc->refresh();

    $service = new PricingMatrixPreviewService();
    $result = $service->resolveColumn('Resolve Test Service');

    expect($result['confidence'])->toBe('exact');
    expect($result['service_id'])->toBe($svc->id);
});

it('PreviewService resolveColumn returns alias when ServiceColumnMapping exists', function () {
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'alias-cat-' . uniqid()],
        ['name' => 'Alias Cat', 'position' => 99, 'is_active' => true],
    );
    $svc = Service::firstOrCreate(
        ['slug' => 'alias-test-svc-' . uniqid()],
        ['name' => 'Alias Test Service', 'category_id' => $cat->id, 'is_active' => true],
    );

    ServiceColumnMapping::create([
        'excel_column' => 'My Custom Header',
        'service_id'   => $svc->id,
        'is_active'    => true,
    ]);

    $service = new PricingMatrixPreviewService();
    $result = $service->resolveColumn('My Custom Header');

    expect($result['confidence'])->toBe('alias');
    expect($result['service_id'])->toBe($svc->id);
});

it('PreviewService resolveColumn returns unmapped for unknown headers', function () {
    $service = new PricingMatrixPreviewService();
    $result = $service->resolveColumn('Totally Unknown Column ZZZ');

    expect($result['service_id'])->toBeNull();
    expect(in_array($result['confidence'], ['unmapped', 'ignored', 'fuzzy'], true))->toBeTrue();
});

it('BaseImport::isSkipToken matches NA family and empty values', function () {
    expect(BaseImport::isSkipToken(null))->toBeTrue();
    expect(BaseImport::isSkipToken(''))->toBeTrue();
    expect(BaseImport::isSkipToken('  '))->toBeTrue();
    expect(BaseImport::isSkipToken('NA'))->toBeTrue();
    expect(BaseImport::isSkipToken('n/a'))->toBeTrue();
    expect(BaseImport::isSkipToken('-'))->toBeTrue();
    expect(BaseImport::isSkipToken('none'))->toBeTrue();

    expect(BaseImport::isSkipToken('0'))->toBeFalse();
    expect(BaseImport::isSkipToken(0))->toBeFalse();
    expect(BaseImport::isSkipToken('1500'))->toBeFalse();
    expect(BaseImport::isSkipToken('Audi'))->toBeFalse();
});

it('PreviewService analyze returns structured preview with vehicle + service columns split', function () {
    ['svcA' => $svcA] = seedMatrixVehicleUniverse();

    $path = writeMatrixXlsx(
        headers: ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $svcA->name, 'Unknown Service Header'],
        rows:    [
            [1, 'Audi', 'A3', 'Petrol', 'Luxury', 1000, 2000],
            [2, 'NoBrand', 'NoModel', 'Petrol', 'Luxury', 500, 'NA'],
        ],
    );

    $preview = (new PricingMatrixPreviewService())->analyze($path);

    // Maatwebsite's HeadingRowImport applies the config-default
    // snake_case formatter to headers — 'Car_id' arrives as
    // 'car_id', and service columns get downcased too. We test
    // the split case-insensitively.
    $vehicleLower = array_map('strtolower', $preview['detected_columns']['vehicle']);
    $serviceLower = array_map('strtolower', $preview['detected_columns']['service']);

    // HeadingRowImport normalises via snake_case: spaces → underscores.
    $svcASnake = str_replace(' ', '_', strtolower($svcA->name));
    expect($vehicleLower)->toContain('car_id');
    expect($serviceLower)->toContain($svcASnake);
    expect($serviceLower)->toContain('unknown_service_header');

    expect($preview['row_summary']['total'])->toBe(2);
    expect($preview['row_summary']['valid_vehicles'])->toBe(1);
    expect($preview['row_summary']['invalid_vehicles'])->toBe(1);

    expect($preview['price_summary']['total_cells'])->toBe(2);  // only valid row contributes counted cells
});

it('PricingMatrixImporter handles a 50-row × 5-service file in reasonable time', function () {
    // Performance smoke: not a strict contract, just a sanity ceiling.
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'perf-cat-' . uniqid()],
        ['name' => 'Perf Cat', 'position' => 99, 'is_active' => true]
    );
    $brand = CarBrand::firstOrCreate(['slug' => 'audi'], ['name' => 'Audi', 'is_active' => true]);
    $model = CarModel::firstOrCreate(
        ['brand_id' => $brand->id, 'slug' => 'a3'],
        ['name' => 'A3', 'is_active' => true]
    );
    $fuel  = FuelType::firstOrCreate(['slug' => 'petrol'], ['name' => 'Petrol', 'is_active' => true]);

    $services = collect(range(1, 5))->map(fn ($i) => Service::firstOrCreate(
        ['slug' => "perf-svc-{$i}-" . uniqid()],
        ['name' => "Perf Svc {$i}", 'category_id' => $cat->id, 'is_active' => true],
    ));

    $headers = ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment'];
    foreach ($services as $s) $headers[] = $s->name;

    $rows = [];
    for ($i = 1; $i <= 50; $i++) {
        $rows[] = [$i, 'Audi', 'A3', 'Petrol', 'X', 100 + $i, 200 + $i, 300 + $i, 400 + $i, 500 + $i];
    }

    $path = writeMatrixXlsx($headers, $rows);
    $start = microtime(true);
    $importer = new PricingMatrixImporter(app(PricingMatrixPreviewService::class));
    $importer->commit($path);
    $elapsed = microtime(true) - $start;

    // 50 rows × 5 services = 250 cells. Should be well under 10s
    // on dev hardware.
    expect($elapsed)->toBeLessThan(10.0);
    // All 50 rows share the same (brand, model, fuel) tuple, so
    // the importer's in-batch dedupe collapses them into 5 final
    // composite keys (one per service). Last-cell-wins semantics
    // give us 5 inserts on the first run, 0 updates.
    expect($importer->inserted)->toBe(5);
    expect($importer->updated)->toBe(0);
});
