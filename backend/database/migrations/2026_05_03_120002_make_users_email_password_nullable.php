<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.1.1 fix-up — OTP-only auth.
 *
 * email and password are optional per /PHASE2_CONTRACT.md §2.1
 * (Decision D-C / Assumption 15). Closes /PHASE2_2_REPORT.md
 * Deviation #2 — phone-only lead-capture used to crash on the
 * skeleton's `email NOT NULL` constraint, and 2.1 worked around
 * `password NOT NULL` with a throwaway random string. Both are
 * relaxed here.
 *
 * The email UNIQUE constraint is intentionally preserved — MySQL /
 * MariaDB allow multiple NULLs in a UNIQUE column, so the constraint
 * still enforces uniqueness across users that did supply an email.
 *
 * Down() is destructive-safe: backfills NULLs before re-tightening
 * the columns so a rollback can never fail on existing rows.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Backfill NULLs first so the NOT NULL constraint can re-attach.
        DB::table('users')
            ->whereNull('email')
            ->update(['email' => DB::raw('CONCAT("noemail-", id, "@local")')]);
        DB::table('users')
            ->whereNull('password')
            ->update(['password' => DB::raw('SHA2(RAND(), 256)')]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
