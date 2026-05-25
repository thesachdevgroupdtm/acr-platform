<?php

use App\Models\CarBrand;
use App\Models\CarModel;

/**
 * Sub-phase L1 — public read-only brand endpoints.
 *
 * Coverage:
 *   - list active brands (auto-created hidden)
 *   - list models for a brand
 *   - 404 for non-existent brand slug
 */

it('lists active brands and hides auto-created ones from public view', function () {
    CarBrand::create(['name' => 'Audi',  'slug' => 'audi-l1',  'is_active' => true]);
    CarBrand::create(['name' => 'BMW',   'slug' => 'bmw-l1',   'is_active' => true]);
    CarBrand::create(['name' => 'Hidden Inactive', 'slug' => 'hidden-l1', 'is_active' => false]);
    CarBrand::create([
        'name' => 'Auto-Created Brand', 'slug' => 'auto-l1',
        'is_active' => true,
        'is_auto_created' => true,
        'include_in_sitemap' => false,
    ]);

    $response = $this->getJson('/api/v1/public/vehicles/brands');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'hero_image_url']],
            'meta' => ['count'],
        ]);

    $slugs = collect($response->json('data'))->pluck('slug');
    expect($slugs)->toContain('audi-l1');
    expect($slugs)->toContain('bmw-l1');
    expect($slugs)->not->toContain('hidden-l1');         // inactive
    expect($slugs)->not->toContain('auto-l1');           // auto-created + not in sitemap
});

it('lists models for a brand under /vehicles/brands/{slug}/models', function () {
    $brand = CarBrand::create(['name' => 'Toyota', 'slug' => 'toyota-l1', 'is_active' => true]);
    CarModel::create(['brand_id' => $brand->id, 'name' => 'Camry',   'slug' => 'camry-l1',  'is_active' => true]);
    CarModel::create(['brand_id' => $brand->id, 'name' => 'Corolla', 'slug' => 'corolla-l1','is_active' => true]);

    $response = $this->getJson("/api/v1/public/vehicles/brands/{$brand->slug}/models");

    $response->assertOk();
    $data = $response->json('data');
    expect(collect($data)->pluck('slug')->sort()->values()->all())
        ->toBe(['camry-l1', 'corolla-l1']);
    expect($data[0])->toHaveKeys(['id', 'name', 'slug', 'brand_id']);
});

it('returns 404 when looking up models for a non-existent brand slug', function () {
    $response = $this->getJson('/api/v1/public/vehicles/brands/does-not-exist/models');

    $response->assertStatus(404)
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonPath('error.code', 'brand_not_found');
});
