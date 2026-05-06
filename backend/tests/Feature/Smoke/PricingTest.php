<?php

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;

it('returns the configured per-vehicle price for a service tuple', function () {
    $brand    = CarBrand::factory()->create();
    $model    = CarModel::factory()->create(['brand_id' => $brand->id]);
    $fuel     = FuelType::factory()->create();
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create([
        'category_id' => $category->id,
        'base_price'  => 999.00,
    ]);

    ServicePrice::factory()->create([
        'service_id'   => $service->id,
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'price'        => 3499.00,
    ]);

    $resp = $this->postJson('/api/v1/pricing', [
        'brand_id'     => $brand->id,
        'model_id'     => $model->id,
        'fuel_type_id' => $fuel->id,
        'service_id'   => $service->id,
    ]);

    $resp->assertStatus(200)
        ->assertJson(['success' => true]);

    $matched = $resp->json('matched_prices');
    expect($matched)->toHaveCount(1);
    expect((float) $matched[0]['price'])->toBe(3499.0);
    expect((float) $resp->json('total'))->toBe(3499.0);
});
