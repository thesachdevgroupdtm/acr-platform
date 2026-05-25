<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\DB;

/**
 * Sub-phase L1 — public read-only pricing lookup endpoint.
 *
 *   GET /api/v1/public/pricing/lookup
 *     ?brand_slug=...&model_slug=...&fuel_slug=...&service_slug=...
 */

function priceL1Universe(): array
{
    $brand = CarBrand::create(['name' => 'Honda',  'slug' => 'honda-pl1',  'is_active' => true]);
    $model = CarModel::create(['brand_id' => $brand->id, 'name' => 'City',  'slug' => 'city-pl1',  'is_active' => true]);
    $fuel  = FuelType::create(['name' => 'Petrol', 'slug' => 'petrol-pl1', 'is_active' => true]);
    $cat   = ServiceCategory::create(['name' => 'PL1Cat', 'slug' => 'pl1cat-' . uniqid(), 'position' => 1, 'is_active' => true]);
    $svc   = Service::create([
        'category_id' => $cat->id,
        'name'        => 'Battery Replacement',
        'slug'        => 'battery-replacement-pl1',
        'time_takes'  => '2', 'time_unit' => 'hours',
        'is_active'   => true,
    ]);
    DB::table('service_prices')->insert([
        'service_id'   => $svc->id,
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'price'        => 4500.00,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
    return compact('brand', 'model', 'fuel', 'svc');
}

it('returns price + estimated_time for a valid 4-tuple combination', function () {
    $u = priceL1Universe();

    $response = $this->getJson(
        '/api/v1/public/pricing/lookup?'
        . http_build_query([
            'brand_slug'   => $u['brand']->slug,
            'model_slug'   => $u['model']->slug,
            'fuel_slug'    => $u['fuel']->slug,
            'service_slug' => $u['svc']->slug,
        ]),
    );

    // PHP's json_encode of 4500.0 emits "4500" (no decimal), which
    // decodes as int — so assert the JSON-roundtrip value, not the
    // float we conceptually passed in. Loose-equal here to express the
    // contract: "the response price equals ₹4500 in some JSON-numeric
    // shape." Frontend (JS) sees one number type anyway.
    $response->assertOk()
        ->assertJsonStructure(['data' => ['price', 'currency', 'estimated_time', 'service', 'vehicle' => ['brand', 'model', 'fuel']]])
        ->assertJsonPath('data.currency', 'INR')
        ->assertJsonPath('data.estimated_time', '2 hours');
    expect((float) $response->json('data.price'))->toBe(4500.0);
});

it('returns 404 with service_not_found code when service slug is unknown', function () {
    $u = priceL1Universe();

    $response = $this->getJson(
        '/api/v1/public/pricing/lookup?'
        . http_build_query([
            'brand_slug'   => $u['brand']->slug,
            'model_slug'   => $u['model']->slug,
            'fuel_slug'    => $u['fuel']->slug,
            'service_slug' => 'nonexistent-service',
        ]),
    );

    $response->assertStatus(404)->assertJsonPath('error.code', 'service_not_found');
});

it('returns 404 with brand_not_found code when brand slug is unknown', function () {
    priceL1Universe();

    $response = $this->getJson(
        '/api/v1/public/pricing/lookup?'
        . http_build_query([
            'brand_slug'   => 'no-brand',
            'model_slug'   => 'irrelevant',
            'fuel_slug'    => 'irrelevant',
            'service_slug' => 'irrelevant',
        ]),
    );

    $response->assertStatus(404)->assertJsonPath('error.code', 'brand_not_found');
});

it('returns 404 with price_not_available when the combination has no price row', function () {
    $u = priceL1Universe();

    // Create a second model under the same brand WITHOUT a price row.
    $model2 = CarModel::create([
        'brand_id' => $u['brand']->id, 'name' => 'Civic', 'slug' => 'civic-pl1', 'is_active' => true,
    ]);

    $response = $this->getJson(
        '/api/v1/public/pricing/lookup?'
        . http_build_query([
            'brand_slug'   => $u['brand']->slug,
            'model_slug'   => $model2->slug,
            'fuel_slug'    => $u['fuel']->slug,
            'service_slug' => $u['svc']->slug,
        ]),
    );

    $response->assertStatus(404)->assertJsonPath('error.code', 'price_not_available');
});

it('returns 422 with validation_failed when required params are missing', function () {
    $response = $this->getJson('/api/v1/public/pricing/lookup');

    $response->assertStatus(422)
        ->assertJsonStructure(['error' => ['code', 'message', 'fields']])
        ->assertJsonPath('error.code', 'validation_failed');
});
