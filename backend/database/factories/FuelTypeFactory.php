<?php

namespace Database\Factories;

use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FuelType>
 */
class FuelTypeFactory extends Factory
{
    protected $model = FuelType::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Petrol', 'Diesel', 'CNG', 'Electric', 'Hybrid']);
        return [
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 999999),
            'is_active' => true,
        ];
    }
}
