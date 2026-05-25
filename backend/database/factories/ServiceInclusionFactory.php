<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceInclusion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceInclusion>
 */
class ServiceInclusionFactory extends Factory
{
    protected $model = ServiceInclusion::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'label'      => ucwords(fake()->unique()->words(2, true)),
            'image'      => null,
            'position'   => fake()->numberBetween(0, 10),
        ];
    }
}
