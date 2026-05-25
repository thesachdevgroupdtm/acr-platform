<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5.3 — public lookups for the explore lead form.
 */

beforeEach(function () {
    Cache::flush();
});

it('returns active brands list', function () {
    $active   = CarBrand::factory()->create(['name' => 'Toyota', 'is_active' => true]);
    $inactive = CarBrand::factory()->create(['name' => 'GhostBrand', 'is_active' => false]);

    $response = $this->getJson('/api/v1/lookups/brands');
    $response->assertSuccessful();

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('Toyota');
    expect($names)->not->toContain('GhostBrand');

    // Shape: [{id, slug, name}]
    $first = $response->json('data.0');
    expect($first)->toHaveKeys(['id', 'slug', 'name']);
});

it('returns models for a given brand', function () {
    $brandA = CarBrand::factory()->create(['is_active' => true]);
    $brandB = CarBrand::factory()->create(['is_active' => true]);

    CarModel::factory()->create([
        'brand_id'  => $brandA->id,
        'name'      => 'A-Model',
        'is_active' => true,
    ]);
    CarModel::factory()->create([
        'brand_id'  => $brandB->id,
        'name'      => 'B-Model',
        'is_active' => true,
    ]);
    CarModel::factory()->create([
        'brand_id'  => $brandA->id,
        'name'      => 'Inactive-A',
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/lookups/models?brand_id=' . $brandA->id);
    $response->assertSuccessful();

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('A-Model');
    expect($names)->not->toContain('B-Model');
    expect($names)->not->toContain('Inactive-A');
});

it('returns services with category info', function () {
    $cat = ServiceCategory::factory()->create(['name' => 'Detailing', 'slug' => 'detailing']);
    Service::factory()->create([
        'category_id' => $cat->id,
        'name'        => 'Premium Wash',
        'is_active'   => true,
    ]);

    $response = $this->getJson('/api/v1/lookups/services');
    $response->assertSuccessful();

    $service = collect($response->json('data'))->firstWhere('name', 'Premium Wash');
    expect($service)->not->toBeNull();
    expect($service['category']['slug'])->toBe('detailing');
    expect($service['category']['name'])->toBe('Detailing');
});
