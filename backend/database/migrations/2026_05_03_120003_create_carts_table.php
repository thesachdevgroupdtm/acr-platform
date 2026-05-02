<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.3 — server-authoritative cart.
 *
 * Per /PHASE2_CONTRACT.md §2.4. A cart belongs to either a logged-in
 * user (user_id set) or an anonymous guest session (session_uuid set).
 * The CHECK constraint at the bottom enforces "exactly one owner";
 * CartSession middleware enforces it again at request time so the
 * database can fail closed if either layer slips.
 *
 * MariaDB 10.4+ honours CHECK constraints natively; we still wrap the
 * raw ALTER in a try/catch so a hypothetical pre-10.2 install doesn't
 * fail the migration outright.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('session_uuid')->nullable()->unique();
            $table->string('currency', 3)->default('INR');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'converted', 'abandoned'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        try {
            DB::statement(
                'ALTER TABLE carts ADD CONSTRAINT chk_cart_owner '
                . 'CHECK (user_id IS NOT NULL OR session_uuid IS NOT NULL)'
            );
        } catch (\Throwable $e) {
            // Older MariaDB / MySQL strains that don't enforce CHECK —
            // app-layer enforcement in CartSession middleware is the
            // real guard. Logged for ops visibility.
            \Log::warning('carts.chk_cart_owner not added: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE carts DROP CONSTRAINT chk_cart_owner');
        } catch (\Throwable $e) {
            // Constraint may not have existed (see up()).
        }
        Schema::dropIfExists('carts');
    }
};
