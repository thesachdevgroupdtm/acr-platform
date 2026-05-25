<?php

use App\Filament\Pages\PricingMatrixImportPage;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Import;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceColumnMapping;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Phase 4.3.3 — Livewire integration coverage for the operator
 * flow: upload → analyze → preview → commit → success.
 *
 * Why this exists: the 11 PricingMatrixImportTest tests prove the
 * importer business logic is correct in isolation. They don't
 * cover the Filament page wiring (Livewire properties, action
 * binding, audit row persistence). Operator reported "click
 * Analyze → nothing happens" with imports table at 0 rows even
 * though importer tests passed. These two tests close that gap.
 */

/** Build a small in-process xlsx in the local disk's imports/ dir. */
function pmipBuildSampleFile(array $headers, array $rows, string $filename): string
{
    $export = new class($headers, $rows) implements FromArray, WithHeadings {
        public function __construct(public array $h, public array $r) {}
        public function headings(): array { return $this->h; }
        public function array(): array { return $this->r; }
    };

    Excel::store($export, "imports/{$filename}", 'local');
    return "imports/{$filename}";
}

/** Seed the universe that the matrix needs to land prices in. */
function pmipSeedUniverse(): array
{
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'pmip-cat-' . uniqid()],
        ['name' => 'PMIP Cat', 'position' => 99, 'is_active' => true]
    );
    $brand = CarBrand::firstOrCreate(['slug' => 'audi'], ['name' => 'Audi', 'is_active' => true]);
    $model = CarModel::firstOrCreate(
        ['brand_id' => $brand->id, 'slug' => 'a3'],
        ['name' => 'A3', 'is_active' => true],
    );
    $fuel = FuelType::firstOrCreate(['slug' => 'petrol'], ['name' => 'Petrol', 'is_active' => true]);
    $svcA = Service::firstOrCreate(
        ['slug' => 'pmip-svc-a-' . uniqid()],
        ['name' => 'PMIP Svc A', 'category_id' => $cat->id, 'is_active' => true]
    );

    return compact('brand', 'model', 'fuel', 'svcA');
}

it('PricingMatrixImportPage.analyze creates an Import audit row and populates preview', function () {
    $u = pmipSeedUniverse();
    $admin = User::factory()->admin()->create();

    $relPath = pmipBuildSampleFile(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $u['svcA']->name],
        [[1, 'Audi', 'A3', 'Petrol', 'Luxury', 1500]],
        'pmip-test-' . uniqid() . '.xlsx',
    );

    $beforeImports = Import::count();

    Livewire::actingAs($admin)
        ->test(PricingMatrixImportPage::class)
        ->set('uploadData', ['file' => $relPath])
        ->call('analyze')
        ->assertHasNoErrors()
        ->assertSet('state', PricingMatrixImportPage::STATE_PREVIEW);

    expect(Import::count())->toBe($beforeImports + 1);
    $audit = Import::latest('id')->first();
    expect($audit->import_type)->toBe(Import::TYPE_PRICING_MATRIX);
    expect($audit->status)->toBe(Import::STATUS_PREVIEW_READY);
    expect($audit->rows_total)->toBe(1);
    expect($audit->rows_valid)->toBe(1);
});

it('PricingMatrixImportPage.commit upserts service_prices and stamps the audit row', function () {
    $u = pmipSeedUniverse();
    $admin = User::factory()->admin()->create();

    $relPath = pmipBuildSampleFile(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $u['svcA']->name],
        [[1, 'Audi', 'A3', 'Petrol', 'Luxury', 2500]],
        'pmip-commit-' . uniqid() . '.xlsx',
    );

    $beforePrices  = \DB::table('service_prices')->count();
    $beforeMaps    = ServiceColumnMapping::count();

    $component = Livewire::actingAs($admin)
        ->test(PricingMatrixImportPage::class)
        ->set('uploadData', ['file' => $relPath])
        ->call('analyze')
        ->assertSet('state', PricingMatrixImportPage::STATE_PREVIEW);

    // Operator clicks Confirm with saveMappings on by default.
    $component->call('commit')
        ->assertSet('state', PricingMatrixImportPage::STATE_SUCCESS);

    // service_prices got the new row.
    expect(\DB::table('service_prices')->count())->toBe($beforePrices + 1);
    expect((float) \DB::table('service_prices')
        ->where('service_id', $u['svcA']->id)
        ->where('brand_id', $u['brand']->id)
        ->where('model_id', $u['model']->id)
        ->where('fuel_type_id', $u['fuel']->id)
        ->value('price'))->toBe(2500.0);

    // service_column_mappings got the operator's choice persisted
    // (saveMappings defaults to true).
    expect(ServiceColumnMapping::count())->toBe($beforeMaps + 1);

    // Audit row stamped completed.
    $audit = Import::latest('id')->first();
    expect($audit->status)->toBe(Import::STATUS_COMPLETED);
    expect($audit->committed_at)->not->toBeNull();
});

/**
 * Phase 4.3.4 — the real-world failure mode: Filament v3's FileUpload
 * writes a hash-keyed array (['<hash>' => 'imports/...xlsx']) into the
 * form state, NOT a bare string. Before resolveUploadedFilePath(),
 * analyze() blew up inside League\Flysystem\PathPrefixer with
 * "Argument #1 ($path) must be of type string, array given". This
 * test pins the helper-mediated flow so the regression can't return.
 */
/**
 * Phase 4.3.5 — Livewire-level pin: analyze() must NOT write any
 * master-data rows. The dry-run path is the only auto-bootstrap path
 * touched by analyze(); persistence happens only on commit().
 */
it('PricingMatrixImportPage.analyze does not write master data during dry-run', function () {
    pmipSeedUniverse(); // pre-seed so PreviewService can still build a preview
    $admin = User::factory()->admin()->create();

    $relPath = pmipBuildSampleFile(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Mystery Service ' . uniqid()],
        [[1, 'BrandX-' . uniqid(), 'ModelX', 'NewFuelZ', 'Luxury', 9000]],
        'pmip-dryrun-' . uniqid() . '.xlsx',
    );

    $before = [
        'brands'   => \App\Models\CarBrand::count(),
        'models'   => \App\Models\CarModel::count(),
        'fuels'    => \App\Models\FuelType::count(),
        'services' => \App\Models\Service::count(),
        'cats'     => \App\Models\ServiceCategory::count(),
        'maps'     => ServiceColumnMapping::count(),
    ];

    $component = Livewire::actingAs($admin)
        ->test(PricingMatrixImportPage::class)
        ->set('uploadData', ['file' => $relPath])
        ->call('analyze')
        ->assertHasNoErrors()
        ->assertSet('state', PricingMatrixImportPage::STATE_PREVIEW);

    // Master-data counts must be byte-identical to pre-analyze state.
    expect(\App\Models\CarBrand::count())->toBe($before['brands']);
    expect(\App\Models\CarModel::count())->toBe($before['models']);
    expect(\App\Models\FuelType::count())->toBe($before['fuels']);
    expect(\App\Models\Service::count())->toBe($before['services']);
    expect(\App\Models\ServiceCategory::count())->toBe($before['cats']);
    expect(ServiceColumnMapping::count())->toBe($before['maps']);

    // …and the bootstrap report should be populated as a dry-run.
    $component->assertSet('bootstrap.isDryRun', true);
    $component->assertSet('bootstrap.importId', null);
});

it('PricingMatrixImportPage.analyze tolerates Filament hash-keyed array state', function () {
    $u = pmipSeedUniverse();
    $admin = User::factory()->admin()->create();

    $relPath = pmipBuildSampleFile(
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', $u['svcA']->name],
        [[1, 'Audi', 'A3', 'Petrol', 'Luxury', 1750]],
        'pmip-array-' . uniqid() . '.xlsx',
    );

    $beforeImports = Import::count();

    // Mimic Filament's exact shape — single-element hash-keyed array.
    Livewire::actingAs($admin)
        ->test(PricingMatrixImportPage::class)
        ->set('uploadData', ['file' => ['hash-' . uniqid() => $relPath]])
        ->call('analyze')
        ->assertHasNoErrors()
        ->assertSet('state', PricingMatrixImportPage::STATE_PREVIEW);

    // Audit row written: proves resolveUploadedFilePath() handed the
    // PreviewService an absolute string, and the rest of the flow ran.
    expect(Import::count())->toBe($beforeImports + 1);
    $audit = Import::latest('id')->first();
    expect($audit->rows_total)->toBe(1);
    expect($audit->rows_valid)->toBe(1);
});
