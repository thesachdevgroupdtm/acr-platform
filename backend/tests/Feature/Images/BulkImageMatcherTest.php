<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\Images\BulkImageMatcher;
use Illuminate\Support\Facades\Storage;

/**
 * L2 — BulkImageMatcher coverage (D-L2-2 .. D-L2-7).
 *
 * Uses a real on-disk ZIP (PHP-native ZipArchive) + a faked `public`
 * disk so storage writes are asserted without touching real storage.
 */

/** Build a temp .zip from ['folder/file.ext' => bytes]. Returns abs path. */
function bimZip(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'bim') . '.zip';
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $bytes) {
        $zip->addFromString($name, $bytes);
    }
    $zip->close();

    return $path;
}

const BIM_PNG = "\x89PNG\r\n\x1a\nfake-image-bytes"; // ext is what matters, not content

beforeEach(function () {
    Storage::fake('public');
});

afterEach(function () {
    // Atomicity test attaches a saving listener; forget it so it can't
    // leak into sibling tests. No-op when nothing was registered.
    app('events')->forget('eloquent.saving: ' . CarBrand::class);
});

it('analyze does not store images or write the DB', function () {
    $brand = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    $report = app(BulkImageMatcher::class)->analyze(bimZip(['brands/Audi.png' => BIM_PNG]));

    expect($report->matchedCount('brands'))->toBe(1)
        ->and($report->committed)->toBeFalse();
    Storage::disk('public')->assertMissing('entity-images/brands/audi.png');
    expect($brand->fresh()->image)->toBeNull();
});

it('matches a brand by exact name, case-insensitive', function () {
    CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    $report = app(BulkImageMatcher::class)->analyze(bimZip(['brands/AUDI.png' => BIM_PNG]));

    expect($report->matchedCount('brands'))->toBe(1)
        ->and($report->matched['brands'][0]['entity'])->toBe('Audi');
});

it('matches a category by name', function () {
    ServiceCategory::factory()->create(['name' => 'Battery', 'slug' => 'battery']);

    $report = app(BulkImageMatcher::class)->analyze(bimZip(['categories/Battery.png' => BIM_PNG]));

    expect($report->matchedCount('categories'))->toBe(1)
        ->and($report->matched['categories'][0]['entity'])->toBe('Battery');
});

it('matches a service by name (with spaces)', function () {
    Service::factory()->create(['name' => 'Battery Replacement', 'slug' => 'battery-replacement']);

    $report = app(BulkImageMatcher::class)->analyze(bimZip(['services/Battery Replacement.png' => BIM_PNG]));

    expect($report->matchedCount('services'))->toBe(1)
        ->and($report->matched['services'][0]['entity'])->toBe('Battery Replacement');
});

it('matches a model by Brand_Model format and disambiguates by brand', function () {
    $audi  = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $honda = CarBrand::factory()->create(['name' => 'Honda', 'slug' => 'honda']);
    $audiQ5  = CarModel::factory()->create(['brand_id' => $audi->id,  'name' => 'Q5', 'slug' => 'audi-q5']);
    CarModel::factory()->create(['brand_id' => $honda->id, 'name' => 'Q5', 'slug' => 'honda-q5']);

    $report = app(BulkImageMatcher::class)->commit(bimZip(['models/Audi_Q5.png' => BIM_PNG]));

    expect($report->matchedCount('models'))->toBe(1)
        ->and($report->matched['models'][0]['slug'])->toBe('audi-q5');
    expect($audiQ5->fresh()->image)->toBe('entity-images/models/audi-q5.png');
});

it('reports unmatched filenames', function () {
    $report = app(BulkImageMatcher::class)->analyze(bimZip(['brands/Tesla.png' => BIM_PNG]));

    expect($report->matchedCount('brands'))->toBe(0)
        ->and($report->unmatchedCount('brands'))->toBe(1)
        ->and($report->unmatched['brands'][0])->toBe('Tesla.png');
});

it('skips oversized images with a reason', function () {
    CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $big = str_repeat('x', 6 * 1024 * 1024); // 6 MB > 5 MB cap

    $report = app(BulkImageMatcher::class)->analyze(bimZip(['brands/Audi.png' => $big]));

    expect($report->matchedCount('brands'))->toBe(0)
        ->and($report->totalSkipped())->toBe(1)
        ->and($report->skipped[0]['reason'])->toContain('too large');
});

it('skips invalid formats with a reason', function () {
    CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    $report = app(BulkImageMatcher::class)->analyze(bimZip(['brands/Audi.gif' => BIM_PNG]));

    expect($report->totalSkipped())->toBe(1)
        ->and($report->skipped[0]['reason'])->toContain('unsupported format');
});

it('commit stores images and updates entity.image', function () {
    $brand = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    $report = app(BulkImageMatcher::class)->commit(bimZip(['brands/Audi.png' => BIM_PNG]));

    expect($report->committed)->toBeTrue()
        ->and($report->matched['brands'][0]['stored_path'])->toBe('entity-images/brands/audi.png');
    Storage::disk('public')->assertExists('entity-images/brands/audi.png');
    expect($brand->fresh()->image)->toBe('entity-images/brands/audi.png');
});

it('commit is transactionally atomic — a failure rolls back all DB writes', function () {
    $audi = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $bmw  = CarBrand::factory()->create(['name' => 'BMW',  'slug' => 'bmw']);

    // Force the BMW update to throw mid-transaction.
    CarBrand::saving(function (CarBrand $b) {
        if (str_contains((string) $b->image, 'bmw')) {
            throw new RuntimeException('boom');
        }
    });

    $zip = bimZip(['brands/Audi.png' => BIM_PNG, 'brands/BMW.png' => BIM_PNG]);

    expect(fn () => app(BulkImageMatcher::class)->commit($zip))->toThrow(RuntimeException::class);

    // Audi was updated BEFORE the throw, but the transaction rolled it back.
    expect($audi->fresh()->image)->toBeNull()
        ->and($bmw->fresh()->image)->toBeNull();
});

it('re-upload overwrites an existing image and is idempotent', function () {
    $brand = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi', 'image' => 'old/audi.png']);
    $matcher = app(BulkImageMatcher::class);

    $matcher->commit(bimZip(['brands/Audi.png' => BIM_PNG]));
    expect($brand->fresh()->image)->toBe('entity-images/brands/audi.png');

    // Same ZIP again → same result (idempotent).
    $matcher->commit(bimZip(['brands/Audi.png' => BIM_PNG]));
    expect($brand->fresh()->image)->toBe('entity-images/brands/audi.png');
    Storage::disk('public')->assertExists('entity-images/brands/audi.png');
});

it('handles a ZIP with mixed folders and ignores stray entries', function () {
    $audi = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    CarModel::factory()->create(['brand_id' => $audi->id, 'name' => 'Q5', 'slug' => 'audi-q5']);
    ServiceCategory::factory()->create(['name' => 'Battery', 'slug' => 'battery']);
    Service::factory()->create(['name' => 'Oil Change', 'slug' => 'oil-change']);

    $report = app(BulkImageMatcher::class)->analyze(bimZip([
        'brands/Audi.png'        => BIM_PNG,
        'models/Audi_Q5.png'     => BIM_PNG,
        'categories/Battery.png' => BIM_PNG,
        'services/Oil Change.png' => BIM_PNG,
        '__MACOSX/._Audi.png'    => 'junk',  // ignored
        'random/whatever.png'    => BIM_PNG, // ignored folder
        'README.txt'             => 'hello', // ignored root file
    ]));

    expect($report->matchedCount('brands'))->toBe(1)
        ->and($report->matchedCount('models'))->toBe(1)
        ->and($report->matchedCount('services'))->toBe(1)
        ->and($report->matchedCount('categories'))->toBe(1)
        ->and($report->totalMatched())->toBe(4)
        ->and($report->totalSkipped())->toBe(0)
        ->and($report->totalUnmatched())->toBe(0);
});
