<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.3 — cart line items.
 *
 * Per /PHASE2_CONTRACT.md §2.5. Exactly one of (service_id,
 * package_id, product_id) is non-null per row — enforced in the
 * CartItem model's saving event, since MySQL/MariaDB CHECK on JSON-
 * coerced expressions is awkward.
 *
 * package_id and product_id are declared as plain unsignedBigInteger
 * nullable here (no FK constraint) — the service_packages and
 * products tables don't land until Phase 2.6. The FKs are added in
 * commit 2.6 alongside those tables. In 2.3 cart-write endpoints
 * accept `kind='service'` only and reject the other two with 422.
 *
 * unit_price_snapshot is the server-trusted price at insert time.
 * Per contract §6.6, a re-snapshot happens on update if the vehicle
 * tuple changes — the cart never trusts client-sent prices.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->unsignedBigInteger('package_id')->nullable();   // FK in 2.6
            $table->unsignedBigInteger('product_id')->nullable();   // FK in 2.6
            $table->foreignId('brand_id')->nullable()->constrained('car_brands')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('car_models')->nullOnDelete();
            $table->foreignId('fuel_id')->nullable()->constrained('fuel_types')->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price_snapshot', 10, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
