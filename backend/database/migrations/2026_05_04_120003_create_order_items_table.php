<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5a — order_items.
 *
 * Per /PHASE2_CONTRACT.md §2.7. Mirrors the cart_items shape but
 * snapshots pricing at order time. Once an order is placed, the
 * service_prices row could change and these snapshots must not.
 *
 * package_id and product_id are nullable unsignedBigInteger with no
 * FK constraint — those tables land in Phase 2.6, same as the cart_items
 * convention.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();
            $table->unsignedBigInteger('package_id')->nullable();   // FK in 2.6
            $table->unsignedBigInteger('product_id')->nullable();   // FK in 2.6
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('car_brands')
                ->nullOnDelete();
            $table->foreignId('model_id')
                ->nullable()
                ->constrained('car_models')
                ->nullOnDelete();
            $table->foreignId('fuel_id')
                ->nullable()
                ->constrained('fuel_types')
                ->nullOnDelete();
            $table->string('service_title_snapshot');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price_snapshot', 10, 2);
            $table->decimal('line_total_snapshot', 10, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
