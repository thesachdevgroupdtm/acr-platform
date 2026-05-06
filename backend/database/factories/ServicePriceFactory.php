<?php

namespace Database\Factories;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePrice>
 */
class ServicePriceFactory extends Factory
{
    protected $model = ServicePrice::class;

    public function definition(): array
    {
        return [
            'service_id'   => Service::factory(),
            'brand_id'     => CarBrand::factory(),
            'model_id'     => CarModel::factory(),
            'fuel_type_id' => FuelType::factory(),
            'price'        => fake()->randomFloat(2, 500, 8000),
        ];
    }
}
