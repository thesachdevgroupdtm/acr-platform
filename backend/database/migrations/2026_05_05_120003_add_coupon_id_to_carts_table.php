<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5b — carts.coupon_id.
 *
 * Single FK per cart (D-2.5b-3, no stacking). nullOnDelete so
 * deactivating a coupon doesn't cascade-orphan active carts —
 * the coupon row stays for audit, the cart auto-clears its ref
 * via CartService::totalsFor when it loads a stale coupon.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('coupon_id')
                ->nullable()
                ->after('currency')
                ->constrained('coupons')
                ->nullOnDelete();
            $table->index('coupon_id', 'carts_coupon_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropIndex('carts_coupon_id_idx');
            $table->dropColumn('coupon_id');
        });
    }
};
