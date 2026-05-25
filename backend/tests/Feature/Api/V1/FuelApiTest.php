<?php

use App\Models\FuelType;

/**
 * Sub-phase L1 — public read-only fuel-type endpoint.
 */

it('lists all active public fuel types', function () {
    FuelType::create(['name' => 'Petrol',   'slug' => 'petrol-l1',   'is_active' => true]);
    FuelType::create(['name' => 'Diesel',   'slug' => 'diesel-l1',   'is_active' => true]);
    FuelType::create(['name' => 'Inactive', 'slug' => 'inactive-l1', 'is_active' => false]);
    FuelType::create([
        'name' => 'AutoFuel', 'slug' => 'autofuel-l1',
        'is_active' => true,
        'is_auto_created' => true,
        'include_in_sitemap' => false,
    ]);

    $response = $this->getJson('/api/v1/public/vehicles/fuels');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'slug']], 'meta' => ['count']]);

    $slugs = collect($response->json('data'))->pluck('slug');
    expect($slugs)->toContain('petrol-l1');
    expect($slugs)->toContain('diesel-l1');
    expect($slugs)->not->toContain('inactive-l1');
    expect($slugs)->not->toContain('autofuel-l1');
});
