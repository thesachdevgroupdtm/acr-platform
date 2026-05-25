<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service-pages redesign Phase 1 (D-P1-2) — per-service service-interval
 * copy, e.g. "Every 5000 km or 3 months" (additive only).
 *
 * A free-text string (not a numeric km column) so operators can write
 * the GoMechanic-style display copy directly. Nullable; existing rows
 * stay null and keep working.
 *
 * Guarded (Schema::hasColumn) so a re-run is a no-op — never drops or
 * renames anything.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('services') || Schema::hasColumn('services', 'interval_info')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->string('interval_info')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('services') && Schema::hasColumn('services', 'interval_info')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('interval_info');
            });
        }
    }
};
