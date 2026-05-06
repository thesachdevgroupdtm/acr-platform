<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;

/**
 * Phase 2.6d edge cases — pricing fallback.
 *
 * The /api/v1/pricing endpoint (PricingController::quote) does NOT
 * fall back to base_price when no service_prices row exists for the
 * 4-tuple. It returns the raw set of matching service_prices rows;
 * a 4-tuple miss yields an empty matched_prices array and total = 0.
 *
 * (Cart pricing in CartService::priceServiceItem DOES fall back to
 * base_price — that path is exercised by the existing
 * Smoke\CartTest. These tests document the /pricing endpoint
 * specifically.)
 */

it('returns the configured price for a 4-tuple that exists in service_prices', function () {
    $brand    = CarBrand::factory()->create();
    $model    = CarModel::factory()->create(['brand_id' => $brand->id]);
    $fuel     = FuelType::factory()->create();
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 999]);

    ServicePrice::factory()->create([
        'service_id'   => $service->id,
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'price'        => 4250.00,
    ]);

    $resp = $this->postJson('/api/v1/pricing', [
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'service_id'   => $service->id,
    ]);

    $resp->assertStatus(200)->assertJson(['success' => true]);
    expect($resp->json('matched_prices'))->toHaveCount(1);
    expect((float) $resp->json('matched_prices.0.price'))->toBe(4250.0);
    expect((float) $resp->json('total'))->toBe(4250.0);
});

it('returns an empty matched_prices array when no 4-tuple match exists', function () {
    $brand    = CarBrand::factory()->create();
    $model    = CarModel::factory()->create(['brand_id' => $brand->id]);
    $petrol   = FuelType::factory()->create();
    $cng      = FuelType::factory()->create();
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 1500]);

    // Configure a price for petrol only — CNG row deliberately missing.
    ServicePrice::factory()->create([
        'service_id'   => $service->id,
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $petrol->id,
        'price'        => 2200.00,
    ]);

    $resp = $this->postJson('/api/v1/pricing', [
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $cng->id,
        'service_id'   => $service->id,
    ]);

    $resp->assertStatus(200)->assertJson(['success' => true]);
    // No fallback to base_price at this endpoint — empty list, zero total.
    expect($resp->json('matched_prices'))->toBe([]);
    expect((float) $resp->json('total'))->toBe(0.0);
});
