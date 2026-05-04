<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5a — payment_transactions.
 *
 * Per /PHASE2_CONTRACT.md §2.8. Phase 2.5a ships with method =
 * 'cash_at_center' only (D-2.5a-9). Gateway columns (gateway_txn_id,
 * gateway_response) are reserved for Phase 4+ when a real gateway
 * (Razorpay/UPI) wires in.
 *
 * The table allows multiple rows per order for refund/retry tracking.
 * Phase 2.5a creates one row per order at placement time.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->enum('method', [
                'cash_at_center', 'upi', 'card', 'wallet', 'other',
            ])->default('cash_at_center');
            $table->enum('status', [
                'pending', 'succeeded', 'failed', 'refunded',
            ])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('gateway_txn_id', 120)->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
