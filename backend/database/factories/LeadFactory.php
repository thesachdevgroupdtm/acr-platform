<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'email'      => fake()->optional()->safeEmail(),
            'phone'      => '9' . fake()->numerify('#########'),
            'brand_id'   => null,
            'model_id'   => null,
            'service_id' => null,
            'source'     => 'explore_sidebar',
            'status'     => 'new',
            'notes'      => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
