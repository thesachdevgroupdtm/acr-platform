<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service-pages redesign Phase 1.5 (D-1.5-1) — group inclusions into
 * GoMechanic-style buckets ("Essential" / "Performance" / "Additional").
 *
 * Adds `service_inclusions.group_name` (string, NULLABLE). NULL means
 * "ungrouped" (D-1.5-2) — the frontend buckets NULL under Essential in
 * Phase 2; nothing here forces a value. Named `group_name` (not the
 * reserved word `group`).
 *
 * Additive + guarded (Schema::hasColumn) — existing rows keep
 * group_name = NULL, untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('service_inclusions') || Schema::hasColumn('service_inclusions', 'group_name')) {
            return;
        }

        Schema::table('service_inclusions', function (Blueprint $table) {
            // AFTER label on MySQL (prod); the modifier is ignored on
            // SQLite (tests) — no error, column still added nullable.
            $table->string('group_name')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('service_inclusions') && Schema::hasColumn('service_inclusions', 'group_name')) {
            Schema::table('service_inclusions', function (Blueprint $table) {
                $table->dropColumn('group_name');
            });
        }
    }
};
