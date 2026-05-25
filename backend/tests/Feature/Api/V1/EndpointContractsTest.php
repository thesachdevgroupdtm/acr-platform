<?php

use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCenter;
use App\Models\CarBrand;

/**
 * Phase 4.2.5 — customer-facing endpoint contract tests.
 *
 * Each test asserts:
 *   - HTTP 200 on success
 *   - The exact response shape the frontend hooks expect (top-level
 *     keys; nested keys checked where the frontend dereferences them)
 *
 * Catches regressions when admin work modifies shared code or when
 * a controller refactor accidentally renames a top-level key.
 */

it('GET /api/v1/home returns the expected top-level shape', function () {
    $response = $this->getJson('/api/v1/home');
    $response->assertSuccessful();

    $response->assertJsonStructure([
        'success',
        'service_categories',
        'car_brands',
        'service_centers',
    ]);

    expect($response->json('success'))->toBeTrue();
});

it('GET /api/v1/coupons returns only active+featured coupons under the coupons key', function () {
    // /coupons listing uses Coupon::active()->notExpired()->featured() —
    // is_featured=true is required to surface on the public list.
    Coupon::factory()->create(['is_active' => true,  'is_featured' => true,  'code' => 'AAACTIVE']);
    Coupon::factory()->create(['is_active' => false, 'is_featured' => true,  'code' => 'BBINACTIVE']);
    Coupon::factory()->create(['is_active' => true,  'is_featured' => false, 'code' => 'CCNOTFEATURED']);

    $response = $this->getJson('/api/v1/coupons');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'coupons' => [
            '*' => ['id', 'code', 'name', 'discount_type', 'discount_value'],
        ],
    ]);

    $codes = collect($response->json('coupons'))->pluck('code')->all();
    expect($codes)->toContain('AAACTIVE');
    expect($codes)->not->toContain('BBINACTIVE');
    expect($codes)->not->toContain('CCNOTFEATURED');
});

it('GET /api/v1/coupons?context=cart returns the same shape as marketing context', function () {
    Coupon::factory()->create(['is_active' => true]);

    $response = $this->getJson('/api/v1/coupons?context=cart');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'coupons' => [
            '*' => ['id', 'code', 'name', 'discount_type', 'discount_value'],
        ],
    ]);
});

it('GET /api/v1/services returns categories nested under categories key', function () {
    $cat = ServiceCategory::factory()->create(['is_active' => true]);
    Service::factory()->count(2)->create([
        'category_id' => $cat->id,
        'is_active'   => true,
    ]);

    $response = $this->getJson('/api/v1/services');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'categories' => [
            '*' => ['id', 'slug', 'title'],
        ],
    ]);
});

it('GET /api/v1/services/{slug} returns the category and its services', function () {
    $cat = ServiceCategory::factory()->create([
        'is_active' => true,
        'slug'      => 'test-category-' . uniqid(),
    ]);
    Service::factory()->count(1)->create([
        'category_id' => $cat->id,
        'is_active'   => true,
    ]);

    $response = $this->getJson('/api/v1/services/' . $cat->slug);
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'category'  => ['id', 'slug', 'title'],
        'services'  => [
            '*' => ['id', 'slug', 'title'],
        ],
    ]);
});

it('GET /api/v1/service-centers returns only active centers under service_centers key', function () {
    ServiceCenter::factory()->create(['is_active' => true,  'name' => 'Active Center A']);
    ServiceCenter::factory()->create(['is_active' => false, 'name' => 'Inactive Center B']);

    $response = $this->getJson('/api/v1/service-centers');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'service_centers' => [
            '*' => ['id', 'slug', 'name', 'address', 'phone'],
        ],
    ]);

    $names = collect($response->json('service_centers'))->pluck('name')->all();
    expect($names)->toContain('Active Center A');
    expect($names)->not->toContain('Inactive Center B');
});

it('GET /api/v1/vehicle/brands returns brands list', function () {
    CarBrand::factory()->count(2)->create(['is_active' => true]);

    $response = $this->getJson('/api/v1/vehicle/brands');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'brands' => [
            '*' => ['id', 'slug', 'title'],
        ],
    ]);
});
