<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.2 — user-owned shipping/service addresses.
 *
 * Per /PHASE2_CONTRACT.md §2.3. user_id is FK-constrained and cascades
 * on user delete. is_default is enforced as "exactly one true per user"
 * by AddressController inside a DB transaction; the schema does not
 * encode that invariant directly (a partial unique index on
 * (user_id) WHERE is_default = 1 would be portable only on Postgres).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('Home');
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 80);
            $table->string('state', 80);
            $table->string('pincode', 10);
            $table->string('landmark')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
