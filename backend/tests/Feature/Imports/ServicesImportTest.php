<?php

use App\Imports\ServicesImport;
use App\Models\Service;
use App\Models\ServiceCategory;

it('ServicesImport upserts a service with category lookup', function () {
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'imports-test-cat-' . uniqid()],
        ['name' => 'Imports Test Cat', 'position' => 99, 'is_active' => true]
    );

    $i = new ServicesImport();

    $i->collection(collect([
        collect([
            'name'          => 'New Service',
            'category_name' => 'Imports Test Cat',
            'slug'          => '',
            'description'   => 'desc',
            'is_active'     => 1,
        ]),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect(Service::where('slug', 'new-service')->where('category_id', $cat->id)->exists())->toBeTrue();
});

it('ServicesImport flags unknown category_name', function () {
    $i = new ServicesImport();

    $i->collection(collect([
        collect([
            'name'          => 'X',
            'category_name' => 'NotARealCategoryXYZ',
            'slug'          => '',
            'is_active'     => 1,
        ]),
    ]));

    expect($i->rowsInvalid)->toBe(1);
    expect($i->errorLog[0]['errors'][0])->toContain('NotARealCategoryXYZ');
});

it('ServicesImport accepts numeric base_price', function () {
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'bp-cat-' . uniqid()],
        ['name' => 'BP Cat', 'position' => 99, 'is_active' => true]
    );

    $i = new ServicesImport();
    $i->collection(collect([
        collect([
            'name'          => 'Priced Service',
            'category_name' => 'BP Cat',
            'base_price'    => '1500.50',
            'is_active'     => 1,
        ]),
    ]));

    expect($i->rowsValid)->toBe(1);
    expect((float) Service::where('slug', 'priced-service')->value('base_price'))->toBe(1500.50);
});

it('ServicesImport flags non-numeric base_price', function () {
    $cat = ServiceCategory::firstOrCreate(
        ['slug' => 'bad-bp-cat-' . uniqid()],
        ['name' => 'Bad BP Cat', 'position' => 99, 'is_active' => true]
    );

    $i = new ServicesImport();
    $i->collection(collect([
        collect([
            'name'          => 'Bad Priced',
            'category_name' => 'Bad BP Cat',
            'base_price'    => 'fifteen hundred',
            'is_active'     => 1,
        ]),
    ]));

    expect($i->rowsInvalid)->toBe(1);
});
