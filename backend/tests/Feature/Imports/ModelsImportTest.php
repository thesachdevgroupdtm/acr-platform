<?php

use App\Imports\ModelsImport;
use App\Models\CarBrand;
use App\Models\CarModel;

it('ModelsImport upserts model + resolves brand by name', function () {
    $brand = CarBrand::create(['name' => 'TestBrand', 'slug' => 'testbrand-' . uniqid(), 'is_active' => true]);
    $i = new ModelsImport();

    $i->collection(collect([
        collect(['name' => 'TestModel', 'brand_name' => 'TestBrand', 'slug' => '', 'is_active' => 1]),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect(CarModel::where('brand_id', $brand->id)->where('name', 'TestModel')->exists())->toBeTrue();
});

it('ModelsImport flags unknown brand_name as invalid', function () {
    $i = new ModelsImport();

    $i->collection(collect([
        collect(['name' => 'X', 'brand_name' => 'NonexistentBrand', 'slug' => '', 'is_active' => 1]),
    ]));

    expect($i->rowsInvalid)->toBe(1);
    expect($i->rowsValid)->toBe(0);
    expect($i->errorLog[0]['errors'][0])->toContain('NonexistentBrand');
});

it('ModelsImport requires name and brand_name', function () {
    $i = new ModelsImport();

    $i->collection(collect([
        collect(['name' => '', 'brand_name' => '', 'slug' => '', 'is_active' => 1]),
    ]));

    expect($i->rowsInvalid)->toBe(1);
    expect(count($i->errorLog[0]['errors']))->toBeGreaterThanOrEqual(2);
});
