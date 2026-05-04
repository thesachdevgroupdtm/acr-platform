<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5b — coupons.
 *
 * Per /PHASE2_CONTRACT.md §2.9 + Phase 2.5b decision D-2.5b-1.
 *
 * Two filter axes:
 *   - applicable_service_ids JSON: when non-null, the coupon applies
 *     ONLY when the cart contains at least one matching service.
 *   - applicable_category_ids JSON: same, but matched against each
 *     item's parent service category.
 *   - Both null = applies to all carts.
 *
 * Two limit axes:
 *   - usage_limit: global ceiling (e.g. campaign cap of 1000 redemptions).
 *   - usage_per_user: per-account ceiling (e.g. first-booking-only=1).
 *
 * Stacking is forbidden (D-2.5b-3): carts.coupon_id and orders.coupon_id
 * each hold a single FK; applying a new coupon over an existing one
 * is last-apply-wins.
 *
 * Seeds 3 coupons inline (D-2.5b-8). The applicable_category_ids for
 * ACCOOL20 references id=11 (car-ac-service-repair) which exists in
 * service_categories from the Phase 1 seed; document inline so the
 * coupling is obvious to a future reader.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->text('description');
            $table->enum('discount_type', ['percent', 'flat']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->decimal('min_order_value', 10, 2)->default(0);
            $table->json('applicable_service_ids')->nullable();
            $table->json('applicable_category_ids')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_per_user')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('badge', 20)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('expiry_date');
            $table->index(['is_active', 'expiry_date']);
            $table->index('is_featured');
        });

        $now = now();
        DB::table('coupons')->insert([
            [
                'code'                     => 'FIRST10',
                'name'                     => 'First Booking Discount',
                'description'              => 'Flat 10% off your very first booking. One-time use per customer; max savings ₹500.',
                'discount_type'            => 'percent',
                'discount_value'           => 10.00,
                'max_discount'             => 500.00,
                'min_order_value'          => 500.00,
                'applicable_service_ids'   => null,
                'applicable_category_ids'  => null,
                'usage_limit'              => null,
                'usage_per_user'           => 1,
                'expiry_date'              => null,
                'is_active'                => true,
                'is_featured'              => true,
                'badge'                    => 'NEW',
                'display_order'            => 10,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'code'                     => 'ACCOOL20',
                'name'                     => 'AC Service Special',
                'description'              => 'Flat ₹500 off any AC Service or Repair booking. Min order ₹1,500.',
                'discount_type'            => 'flat',
                'discount_value'           => 500.00,
                'max_discount'             => null,
                'min_order_value'          => 1500.00,
                // car-ac-service-repair → id=11 (per Phase 1 service_categories seed).
                'applicable_service_ids'   => null,
                'applicable_category_ids'  => json_encode([11]),
                'usage_limit'              => null,
                'usage_per_user'           => null,
                'expiry_date'              => null,
                'is_active'                => true,
                'is_featured'              => true,
                'badge'                    => 'POPULAR',
                'display_order'            => 20,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'code'                     => 'SAVER15',
                'name'                     => 'Cart Saver',
                'description'              => 'Flat 15% off when your cart crosses ₹3,000. Max savings ₹750.',
                'discount_type'            => 'percent',
                'discount_value'           => 15.00,
                'max_discount'             => 750.00,
                'min_order_value'          => 3000.00,
                'applicable_service_ids'   => null,
                'applicable_category_ids'  => null,
                'usage_limit'              => null,
                'usage_per_user'           => null,
                'expiry_date'              => null,
                'is_active'                => true,
                'is_featured'              => true,
                'badge'                    => 'BEST',
                'display_order'            => 30,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
