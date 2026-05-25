<?php

use App\Filament\Resources\CarBrandResource\Pages\EditCarBrand;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Images\BulkImageMatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * IMAGE-UPLOAD-FIX (PART E) — per-tab auto-process (processForType) +
 * fuel column + inline resource upload. Complements the L2
 * BulkImageMatcherTest (folder-based analyze/commit) which still passes.
 */

const PFT_PNG = "\x89PNG\r\n\x1a\nfake-image-bytes";

/** A plain payload item like the page builds from a TemporaryUploadedFile. */
function pftFile(string $name, ?string $bytes = null): array
{
    $bytes ??= PFT_PNG;
    return ['name' => $name, 'contents' => $bytes, 'size' => strlen($bytes)];
}

/** Build a real .zip in memory (returns raw bytes). */
function pftZipBytes(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'pft') . '.zip';
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $bytes) {
        $zip->addFromString($name, $bytes);
    }
    $zip->close();
    $raw = file_get_contents($path);
    @unlink($path);

    return $raw;
}

beforeEach(fn () => Storage::fake('public'));

it('processForType stores matched brand images from multiple files (no folder prefix)', function () {
    $audi = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $bmw  = CarBrand::factory()->create(['name' => 'BMW', 'slug' => 'bmw']);

    $report = app(BulkImageMatcher::class)->processForType(
        [pftFile('Audi.png'), pftFile('BMW.png')],
        'brands',
    );

    expect($report->totalMatched())->toBe(2)
        ->and($report->committed)->toBeTrue();
    Storage::disk('public')->assertExists('entity-images/brands/audi.png');
    Storage::disk('public')->assertExists('entity-images/brands/bmw.png');
    expect($audi->fresh()->image)->toBe('entity-images/brands/audi.png')
        ->and($bmw->fresh()->image)->toBe('entity-images/brands/bmw.png');
});

it('processForType matches a model by Brand_Model and stores under models/', function () {
    $audi = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $q5 = CarModel::factory()->create(['brand_id' => $audi->id, 'name' => 'Q5', 'slug' => 'audi-q5']);

    $report = app(BulkImageMatcher::class)->processForType([pftFile('Audi_Q5.png')], 'models');

    expect($report->matchedCount('models'))->toBe(1);
    expect($q5->fresh()->image)->toBe('entity-images/models/audi-q5.png');
});

it('processForType matches + stores a fuel-type image', function () {
    $petrol = FuelType::factory()->create(['name' => 'Petrol', 'slug' => 'petrol']);

    $report = app(BulkImageMatcher::class)->processForType([pftFile('Petrol.png')], 'fuel-types');

    expect($report->matchedCount('fuel-types'))->toBe(1);
    Storage::disk('public')->assertExists('entity-images/fuel-types/petrol.png');
    expect($petrol->fresh()->image)->toBe('entity-images/fuel-types/petrol.png');
});

it('processForType matches category + service images', function () {
    $cat = ServiceCategory::factory()->create(['name' => 'Battery', 'slug' => 'battery']);
    $svc = Service::factory()->create(['name' => 'Battery Replacement', 'slug' => 'battery-replacement']);

    $catReport = app(BulkImageMatcher::class)->processForType([pftFile('Battery.png')], 'categories');
    $svcReport = app(BulkImageMatcher::class)->processForType([pftFile('Battery Replacement.png')], 'services');

    expect($cat->fresh()->image)->toBe('entity-images/categories/battery.png')
        ->and($svc->fresh()->image)->toBe('entity-images/services/battery-replacement.png')
        ->and($catReport->matchedCount('categories'))->toBe(1)
        ->and($svcReport->matchedCount('services'))->toBe(1);
});

it('processForType accepts a .zip and extracts + stores its images', function () {
    $audi = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $bmw  = CarBrand::factory()->create(['name' => 'BMW', 'slug' => 'bmw']);

    $zipBytes = pftZipBytes([
        'Audi.png' => PFT_PNG,
        'BMW.png'  => PFT_PNG,
        '__MACOSX/._Audi.png' => 'junk', // ignored
    ]);

    $report = app(BulkImageMatcher::class)->processForType([pftFile('bundle.zip', $zipBytes)], 'brands');

    expect($report->totalMatched())->toBe(2);
    expect($audi->fresh()->image)->toBe('entity-images/brands/audi.png')
        ->and($bmw->fresh()->image)->toBe('entity-images/brands/bmw.png');
});

it('processForType reports unmatched filenames', function () {
    $report = app(BulkImageMatcher::class)->processForType([pftFile('Tesla.png')], 'brands');

    expect($report->totalMatched())->toBe(0)
        ->and($report->unmatched['brands'][0])->toBe('Tesla.png');
});

it('processForType skips bad format and oversize with reasons', function () {
    CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $big = str_repeat('x', 6 * 1024 * 1024);

    $report = app(BulkImageMatcher::class)->processForType(
        [pftFile('Audi.gif'), pftFile('Audi.png', $big)],
        'brands',
    );

    expect($report->totalMatched())->toBe(0)
        ->and($report->totalSkipped())->toBe(2);
});

it('fuel_types has an image column and FuelType is fillable', function () {
    expect(Schema::hasColumn('fuel_types', 'image'))->toBeTrue()
        ->and(in_array('image', (new FuelType())->getFillable(), true))->toBeTrue();
});

it('renders the redesigned bulk image upload page for an admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(\App\Filament\Pages\BulkImageUploadPage::getUrl())
        ->assertSuccessful();
});

it('renders the CarBrand edit form with the inline image upload', function () {
    $admin = User::factory()->admin()->create();
    $brand = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    $this->actingAs($admin)
        ->get(\App\Filament\Resources\CarBrandResource::getUrl('edit', ['record' => $brand]))
        ->assertSuccessful();
});

it('stores an inline image upload from the CarBrand edit form to entity-images/brands/{slug}', function () {
    $admin = User::factory()->admin()->create();
    $brand = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    // ->image() needs the GD extension (absent here); ->create() with an
    // image mime satisfies Filament's ->image() validation without GD.
    $file = UploadedFile::fake()->create('whatever.png', 40, 'image/png');

    Livewire::actingAs($admin)
        ->test(EditCarBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm(['image' => $file])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($brand->fresh()->image)->toBe('entity-images/brands/audi.png');
    Storage::disk('public')->assertExists('entity-images/brands/audi.png');
});
