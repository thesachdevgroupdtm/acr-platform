<?php

namespace Database\Factories;

use App\Models\ServiceCenter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Phase 4.2 — minimal ServiceCenter factory for tests.
 */
class ServiceCenterFactory extends Factory
{
    protected $model = ServiceCenter::class;

    public function definition(): array
    {
        $name = $this->faker->city() . ' Center';
        return [
            'slug'      => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 99999),
            'name'      => $name,
            'address'   => $this->faker->address(),
            'phone'     => $this->faker->numerify('011-########'),
            'email'     => $this->faker->safeEmail(),
            'city'      => $this->faker->city(),
            'state'     => 'Delhi NCR',
            'pincode'   => $this->faker->numerify('1100##'),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
