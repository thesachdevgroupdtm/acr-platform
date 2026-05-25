<?php

use App\Imports\BrandsImport;
use App\Models\CarBrand;
use Illuminate\Support\Collection;

/**
 * Phase 4.3 — Family A row-per-record import: brands.
 */

function brandRowCollection(array $arr): Collection
{
    return collect($arr);
}

it('BrandsImport upserts a brand by slug', function () {
    CarBrand::create(['name' => 'Audi', 'slug' => 'audi', 'is_active' => true]);
    $i = new BrandsImport();

    $i->collection(collect([
        brandRowCollection(['name' => 'Audi (updated)', 'slug' => 'audi', 'is_active' => 1]),
        brandRowCollection(['name' => 'New Brand', 'slug' => '', 'is_active' => 1]),
    ]));

    expect($i->rowsValid)->toBe(2);
    expect($i->rowsInvalid)->toBe(0);
    expect(CarBrand::where('slug', 'audi')->value('name'))->toBe('Audi (updated)');
    expect(CarBrand::where('slug', 'new-brand')->exists())->toBeTrue();
});

it('BrandsImport flags blank name as invalid', function () {
    $i = new BrandsImport();

    $i->collection(collect([
        brandRowCollection(['name' => '', 'slug' => '', 'is_active' => 1]),
    ]));

    expect($i->rowsValid)->toBe(0);
    expect($i->rowsInvalid)->toBe(1);
    expect($i->errorLog)->toHaveCount(1);
});

it('BrandsImport never regenerates slug on existing row', function () {
    CarBrand::create(['name' => 'Volvo', 'slug' => 'volvo-classic', 'is_active' => true]);
    $i = new BrandsImport();

    // Operator changes the name in Excel but leaves slug blank — slug must NOT auto-regenerate from new name.
    $i->collection(collect([
        brandRowCollection(['name' => 'Volvo Cars', 'slug' => 'volvo-classic', 'is_active' => 1]),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect(CarBrand::where('slug', 'volvo-classic')->value('name'))->toBe('Volvo Cars');
    // Slug preserved.
    expect(CarBrand::where('slug', 'volvo-cars')->exists())->toBeFalse();
});

it('BrandsImport skips totally empty rows', function () {
    $i = new BrandsImport();

    $i->collection(collect([
        brandRowCollection(['name' => 'Real Brand', 'slug' => '', 'is_active' => 1]),
        brandRowCollection(['name' => null, 'slug' => null, 'is_active' => null]),
        brandRowCollection(['name' => '', 'slug' => '', 'is_active' => '']),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect($i->rowsSkipped)->toBe(2);
});
