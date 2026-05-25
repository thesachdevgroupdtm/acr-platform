<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.1 — Filament admin panel access flag.
 *
 * Per locked decision D-4.1-2. Adds a boolean `is_admin` to the
 * users table; Filament's canAccessPanel implementation on
 * App\Models\User reads this column.
 *
 * Note on the existing `role` enum: Phase 2.1's
 * 2026_05_02_120001_extend_users_for_auth migration added a
 * `role` ENUM('customer','admin'). The two columns both encode
 * admin status. D-4.1-2 chose the boolean for Filament; future
 * cleanup may consolidate. Both remain truthful as long as admin
 * users have role='admin' AND is_admin=true (or one — Filament
 * only reads is_admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
