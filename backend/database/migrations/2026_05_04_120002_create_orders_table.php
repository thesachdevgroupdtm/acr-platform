<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5a — orders.
 *
 * Per /PHASE2_CONTRACT.md §2.6.
 *
 * Snapshot pattern: name/phone/email/vehicle are copied at order
 * placement time so a later profile/vehicle change cannot mutate the
 * historical record. Pricing snapshots live on order_items.
 *
 * coupon_id is reserved for Phase 2.5b — declared as a plain
 * unsignedBigInteger nullable here. The FK constraint to coupons
 * lands when that table is created.
 *
 * order_number is the user-facing identifier (ACR-YEAR-NNNNN) and
 * is independent of the BIGINT primary key. UNIQUE so retries can't
 * collide.
 *
 * State machine (D-2.5a-5):
 *   pending → confirmed | cancelled
 *   confirmed → in_service | cancelled (admin)
 *   in_service → completed
 *   completed → terminal
 *   cancelled → terminal
 *
 * Indexes are tuned for the queries that ship in this commit:
 *   - user_id+status: "list my pending bookings"
 *   - phone_snapshot+created_at: fake-booking guard rate-limit lookup
 *   - status+created_at: auto-confirm scheduled command + admin scans
 *   - preferred_date: future bookings dashboards
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('service_center_id')
                ->nullable()
                ->constrained('service_centers')
                ->nullOnDelete();
            // FK to coupons deferred to Phase 2.5b (table doesn't exist yet).
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->enum('status', [
                'pending', 'confirmed', 'in_service', 'completed', 'cancelled',
            ])->default('pending');
            $table->enum('payment_status', [
                'pending', 'paid', 'failed', 'refunded',
            ])->default('pending');
            $table->string('name_snapshot');
            $table->string('phone_snapshot', 15);
            $table->string('email_snapshot')->nullable();
            $table->text('address')->nullable();
            $table->json('vehicle_snapshot');
            $table->date('preferred_date');
            $table->string('preferred_time', 40);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->boolean('is_high_risk')->default(false);
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('in_service_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('order_number');
            $table->index(['phone_snapshot', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('preferred_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
