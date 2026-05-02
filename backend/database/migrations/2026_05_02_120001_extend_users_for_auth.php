<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.1 — extend users for OTP-based auth.
 *
 * Adds:
 *   phone               (nullable, unique) — primary identifier post-Phase 2
 *   is_verified_phone   (bool, default false)
 *   is_verified_email   (bool, default false)
 *   last_login_at       (nullable timestamp)
 *   role                (enum customer|admin, default customer)
 *
 * Per /PHASE2_CONTRACT.md §2.1.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 15)->nullable()->after('email');
            $table->boolean('is_verified_phone')->default(false)->after('phone');
            $table->boolean('is_verified_email')->default(false)->after('is_verified_phone');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->enum('role', ['customer', 'admin'])->default('customer')->after('last_login_at');

            $table->unique('phone');
            $table->index(['role', 'last_login_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'last_login_at']);
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'phone',
                'is_verified_phone',
                'is_verified_email',
                'last_login_at',
                'role',
            ]);
        });
    }
};
