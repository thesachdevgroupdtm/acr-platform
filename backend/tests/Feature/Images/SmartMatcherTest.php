<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Services\Images\BulkImageMatcher;
use Illuminate\Support\Facades\Storage;

/**
 * IMAGE-SYSTEM-FIXES (PART B) — smart "messy filename" matcher:
 * trailing-timestamp strip + glued BRAND+MODEL split (longest brand prefix)
 * + exact/fuzzy model match. Applied to the Models tab; timestamp strip
 * applies to all tabs.
 */

function smatcher(): BulkImageMatcher
{
    return app(BulkImageMatcher::class);
}

beforeEach(fn () => Storage::fake('public'));

it('stripTimestamp removes a trailing 10-digit unix timestamp', function () {
    expect(smatcher()->stripTimestamp('VOLVOXC601698310597'))->toBe('VOLVOXC60')
        ->and(smatcher()->stripTimestamp('PORSCHE9111698311285'))->toBe('PORSCHE911')
        ->and(smatcher()->stripTimestamp('Audi'))->toBe('Audi'); // no timestamp → unchanged
});

it('splitBrandModel splits a glued BRAND+MODEL using the brand prefix', function () {
    CarBrand::factory()->create(['name' => 'Volvo', 'slug' => 'volvo']);

    $split = smatcher()->splitBrandModel('VOLVOXC60');

    expect($split)->not->toBeNull()
        ->and($split['brand']->name)->toBe('Volvo')
        ->and($split['model_norm'])->toBe('xc60');
});

it('splitBrandModel — longest brand prefix wins', function () {
    CarBrand::factory()->create(['name' => 'Mer', 'slug' => 'mer']);
    $mercedes = CarBrand::factory()->create(['name' => 'Mercedes', 'slug' => 'mercedes']);

    $split = smatcher()->splitBrandModel('MercedesGLE43');

    expect($split['brand']->id)->toBe($mercedes->id)
        ->and($split['model_norm'])->toBe('gle43');
});

it('matchModelSmart resolves BRAND+MODEL+timestamp to the right model', function () {
    $audi = CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);
    $a8 = CarModel::factory()->create(['brand_id' => $audi->id, 'name' => 'A8', 'slug' => 'audi-a8']);

    expect(smatcher()->matchModelSmart('AudiA81698311289.png')?->id)->toBe($a8->id);
});

it('matchModelSmart returns null when the brand prefix is not found', function () {
    CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    expect(smatcher()->matchModelSmart('ZZZUnknown1698311289.png'))->toBeNull();
});

it('matchModelSmart uses the fuzzy fallback for a near-miss model name', function () {
    $porsche = CarBrand::factory()->create(['name' => 'Porsche', 'slug' => 'porsche']);
    $carrera = CarModel::factory()->create(['brand_id' => $porsche->id, 'name' => 'Carrera', 'slug' => 'porsche-carrera']);

    // "Carera" (one missing r) → ~0.857 similarity ≥ 0.85 → Carrera.
    expect(smatcher()->matchModelSmart('PorscheCarera1698311285.png')?->id)->toBe($carrera->id);
});

it('processForType (models) auto-matches a messy old-website filename and stores it', function () {
    $volvo = CarBrand::factory()->create(['name' => 'Volvo', 'slug' => 'volvo']);
    $xc60 = CarModel::factory()->create(['brand_id' => $volvo->id, 'name' => 'XC60', 'slug' => 'volvo-xc60']);

    $report = smatcher()->processForType(
        [['name' => 'VOLVOXC601698310597.png', 'contents' => 'PNGDATA', 'size' => 7]],
        'models',
    );

    expect($report->matchedCount('models'))->toBe(1);
    expect($xc60->fresh()->image)->toBe('entity-images/models/volvo-xc60.png');
    Storage::disk('public')->assertExists('entity-images/models/volvo-xc60.png');
});

it('processForType (brands) strips a trailing timestamp before matching', function () {
    $volvo = CarBrand::factory()->create(['name' => 'Volvo', 'slug' => 'volvo']);

    $report = smatcher()->processForType(
        [['name' => 'VOLVO1698310597.png', 'contents' => 'X', 'size' => 1]],
        'brands',
    );

    expect($report->matchedCount('brands'))->toBe(1);
    expect($volvo->fresh()->image)->toBe('entity-images/brands/volvo.png');
});

it('processForType (models) reports an unparseable filename as skipped with a hint', function () {
    CarBrand::factory()->create(['name' => 'Audi', 'slug' => 'audi']);

    $report = smatcher()->processForType(
        [['name' => 'ZZZNothing1698310597.png', 'contents' => 'x', 'size' => 1]],
        'models',
    );

    expect($report->totalMatched())->toBe(0)
        ->and($report->totalSkipped())->toBe(1)
        ->and($report->skipped[0]['reason'])->toContain("couldn't parse");
});
