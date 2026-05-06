<?php

namespace Database\Factories;

use App\Models\CarBrand;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CarModel>
 */
class CarModelFactory extends Factory
{
    protected $model = CarModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        return [
            'brand_id'  => CarBrand::factory(),
            'name'      => ucfirst($name),
            'slug'      => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 999999),
            'is_active' => true,
        ];
    }
}
