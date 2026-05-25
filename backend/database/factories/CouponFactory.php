<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Phase 4.2 — minimal Coupon factory for admin-resource tests.
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code'            => strtoupper($this->faker->unique()->bothify('TEST???##')),
            'name'            => $this->faker->words(3, true),
            'description'     => $this->faker->sentence(),
            'discount_type'   => 'percent',
            'discount_value'  => 10.00,
            'max_discount'    => 500.00,
            'min_order_value' => 0,
            'usage_limit'     => null,
            'usage_per_user'  => null,
            'expiry_date'     => null,
            'is_active'       => true,
            'is_featured'     => false,
            'badge'           => null,
            'display_order'   => 0,
        ];
    }
}
