<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Phase 4.2 — minimal Order factory for admin-resource tests.
 *
 * Required columns are populated with realistic-but-deterministic
 * values; status defaults to 'pending' and individual tests should
 * override via ->create(['status' => ...]) when exercising
 * transitions.
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(500, 5000);

        return [
            'order_number'      => 'ACR-2026-' . str_pad((string) $this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'user_id'           => User::factory(),
            'service_center_id' => ServiceCenter::factory(),
            'coupon_id'         => null,
            'status'            => 'pending',
            'payment_status'    => 'pending',
            'name_snapshot'     => $this->faker->name(),
            'phone_snapshot'    => $this->faker->numerify('98#######0'),
            'email_snapshot'    => $this->faker->safeEmail(),
            'address'           => $this->faker->address(),
            'vehicle_snapshot'  => [
                'brand_name' => 'Maruti',
                'model_name' => 'Swift',
                'fuel_name'  => 'Petrol',
            ],
            'preferred_date'    => now()->addDays(2)->toDateString(),
            'preferred_time'    => '10:00 AM - 12:00 PM',
            'subtotal'          => $subtotal,
            'discount'          => 0,
            'tax'               => round($subtotal * 0.18, 2),
            'total'             => round($subtotal * 1.18, 2),
            'notes'             => null,
            'is_high_risk'      => false,
            'placed_at'         => now(),
        ];
    }
}
