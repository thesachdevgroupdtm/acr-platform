<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5b — coupon_usages.
 *
 * One row per (coupon, order) pair. Written inside
 * CheckoutService::placeOrder transaction (D-2.5b-7) so the
 * usage claim is atomic with the order itself.
 *
 * The (coupon_id, user_id) composite index supports the
 * usage_per_user limit check in CouponService::validate.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('used_at');
            $table->timestamps();

            $table->index('coupon_id');
            $table->index('user_id');
            $table->index('order_id');
            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
