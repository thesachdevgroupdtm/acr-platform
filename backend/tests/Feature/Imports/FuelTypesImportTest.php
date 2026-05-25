<?php

use App\Imports\FuelTypesImport;
use App\Models\FuelType;

it('FuelTypesImport inserts new fuel type', function () {
    $beforeCount = FuelType::count();
    $i = new FuelTypesImport();

    $i->collection(collect([
        collect(['name' => 'Hydrogen', 'slug' => '', 'is_active' => 1]),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect(FuelType::count())->toBe($beforeCount + 1);
    expect(FuelType::where('slug', 'hydrogen')->exists())->toBeTrue();
});

it('FuelTypesImport updates existing by slug', function () {
    $f = FuelType::create(['name' => 'Old Name', 'slug' => 'updateme-' . uniqid(), 'is_active' => true]);
    $i = new FuelTypesImport();

    $i->collection(collect([
        collect(['name' => 'New Name', 'slug' => $f->slug, 'is_active' => 1]),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect($f->fresh()->name)->toBe('New Name');
});

it('FuelTypesImport flags blank name', function () {
    $i = new FuelTypesImport();
    $i->collection(collect([collect(['name' => '   ', 'slug' => '', 'is_active' => 1])]));
    expect($i->rowsInvalid)->toBe(1);
});
