<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5b — orders.coupon_id FK.
 *
 * Phase 2.5a created the orders.coupon_id column as a plain
 * unsignedBigInteger nullable (the coupons table didn't exist yet).
 * Now that coupons is migrated, add the actual FK constraint with
 * nullOnDelete semantics so deactivating a coupon doesn't break the
 * historical order record.
 *
 * Existing rows: orders.coupon_id is null on every row pre-2.5b
 * (CheckoutService passed null). The FK adds cleanly without a
 * data migration.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('coupon_id')
                ->references('id')
                ->on('coupons')
                ->nullOnDelete();
            $table->index('coupon_id', 'orders_coupon_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropIndex('orders_coupon_id_idx');
        });
    }
};
