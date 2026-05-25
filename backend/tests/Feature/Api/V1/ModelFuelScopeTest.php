<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;

/**
 * MODEL_FUEL_SCOPE — GET /api/v1/public/vehicles/models/{slug}/fuels.
 *
 * Returns ONLY the fuels that have a valid pricing combination
 * (service_prices) for the resolved model. Falls back to the full
 * active-fuel catalog when the model has no pricing rows (D-FUEL-4),
 * so the booking flow never dead-ends. Same {data, meta} envelope and
 * FuelResource mapping (full hero_image_url) as the global fuels list.
 */

/** Create one service_prices row tying a fuel to a model. */
function priceFuelForModel(CarBrand $brand, CarModel $model, FuelType $fuel, Service $service): void
{
    ServicePrice::factory()->create([
        'service_id'   => $service->id,
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'price'        => 1500.00,
    ]);
}

it('returns only fuels that have pricing rows for the model', function () {
    $brand    = CarBrand::factory()->create();
    $model    = CarModel::factory()->create(['brand_id' => $brand->id, 'slug' => 'scoped-model']);
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id]);

    $petrol = FuelType::create(['name' => 'Petrol', 'slug' => 'petrol-scope', 'is_active' => true]);
    $diesel = FuelType::create(['name' => 'Diesel', 'slug' => 'diesel-scope', 'is_active' => true]);
    FuelType::create(['name' => 'CNG', 'slug' => 'cng-scope', 'is_active' => true]); // no price for this model

    // Model is priced for Petrol + Diesel only — never CNG.
    priceFuelForModel($brand, $model, $petrol, $service);
    priceFuelForModel($brand, $model, $diesel, $service);

    $resp = $this->getJson('/api/v1/public/vehicles/models/scoped-model/fuels');

    $resp->assertOk();
    $slugs = collect($resp->json('data'))->pluck('slug');
    expect($slugs)->toContain('petrol-scope');
    expect($slugs)->toContain('diesel-scope');
    expect($slugs)->not->toContain('cng-scope');
    expect($resp->json('meta.count'))->toBe(2);
    expect($resp->json('meta.fallback'))->toBeFalse();
    expect($resp->json('meta.model_slug'))->toBe('scoped-model');
    expect($resp->json('meta.model_id'))->toBe($model->id);
});

it('falls back to all active fuels when the model has no pricing rows', function () {
    $brand = CarBrand::factory()->create();
    $model = CarModel::factory()->create(['brand_id' => $brand->id, 'slug' => 'unpriced-model']);

    FuelType::create(['name' => 'Petrol', 'slug' => 'petrol-fb', 'is_active' => true]);
    FuelType::create(['name' => 'Diesel', 'slug' => 'diesel-fb', 'is_active' => true]);
    FuelType::create(['name' => 'Inactive', 'slug' => 'inactive-fb', 'is_active' => false]);

    // No service_prices rows exist for this model at all.
    $resp = $this->getJson('/api/v1/public/vehicles/models/unpriced-model/fuels');

    $resp->assertOk();
    $slugs = collect($resp->json('data'))->pluck('slug');
    expect($slugs)->toContain('petrol-fb');
    expect($slugs)->toContain('diesel-fb');
    expect($slugs)->not->toContain('inactive-fb'); // visibility rule still applies
    expect($resp->json('meta.fallback'))->toBeTrue();
});

it('exposes the {data, meta} shape with a full hero_image_url', function () {
    $brand    = CarBrand::factory()->create();
    $model    = CarModel::factory()->create(['brand_id' => $brand->id, 'slug' => 'shape-model']);
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id]);
    $fuel     = FuelType::create([
        'name' => 'Petrol', 'slug' => 'petrol-shape', 'is_active' => true,
        'image' => 'entity-images/fuel-types/petrol.webp',
    ]);

    priceFuelForModel($brand, $model, $fuel, $service);

    $resp = $this->getJson('/api/v1/public/vehicles/models/shape-model/fuels');

    $resp->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'hero_image_url']],
            'meta' => ['count', 'model_id', 'model_slug', 'fallback'],
        ]);

    expect($resp->json('data.0.hero_image_url'))
        ->toContain('/storage/entity-images/fuel-types/petrol.webp');
});

it('excludes a priced fuel that is inactive', function () {
    $brand    = CarBrand::factory()->create();
    $model    = CarModel::factory()->create(['brand_id' => $brand->id, 'slug' => 'vis-model']);
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id]);

    $active   = FuelType::create(['name' => 'Petrol', 'slug' => 'petrol-vis', 'is_active' => true]);
    $inactive = FuelType::create(['name' => 'Inactive', 'slug' => 'inactive-vis', 'is_active' => false]);

    priceFuelForModel($brand, $model, $active, $service);
    priceFuelForModel($brand, $model, $inactive, $service); // priced, but inactive → must be hidden

    $resp = $this->getJson('/api/v1/public/vehicles/models/vis-model/fuels');

    $resp->assertOk();
    $slugs = collect($resp->json('data'))->pluck('slug');
    expect($slugs)->toContain('petrol-vis');
    expect($slugs)->not->toContain('inactive-vis');
    expect($resp->json('meta.fallback'))->toBeFalse(); // an active fuel is priced
});

it('returns 404 for an unknown model slug', function () {
    $this->getJson('/api/v1/public/vehicles/models/no-such-model/fuels')->assertNotFound();
});
