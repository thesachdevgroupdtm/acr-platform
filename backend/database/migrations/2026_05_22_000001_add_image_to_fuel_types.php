<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IMAGE-UPLOAD-FIX (PART B) — fuel_types gains a nullable `image` column so
 * fuel-type images can be uploaded via the bulk page + the FuelTypeResource
 * inline field. Guarded + idempotent (re-runnable, safe if already present).
 *
 * Unlike the four L1 master-data tables, fuel_types' create migration did
 * NOT declare `image`, so this migration owns the column — down() drops it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fuel_types') && ! Schema::hasColumn('fuel_types', 'image')) {
            Schema::table('fuel_types', function (Blueprint $table) {
                $table->string('image')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fuel_types') && Schema::hasColumn('fuel_types', 'image')) {
            Schema::table('fuel_types', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
