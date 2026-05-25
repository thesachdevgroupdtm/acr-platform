<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L2 — bulk image upload.
 *
 * Adds a nullable `image` string column to the four master-data tables
 * that the bulk-image-upload page targets. Guarded + idempotent: the
 * column is already declared in the original create-table migrations
 * (L1), so on this codebase every branch is a no-op. The guard makes the
 * migration safe to run on any environment (including one whose
 * create-table migrations predate the column) and re-runnable.
 *
 * The column lifecycle belongs to the create-table migrations, so down()
 * intentionally does nothing — we never drop a column another migration
 * may own.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = ['car_brands', 'car_models', 'services', 'service_categories'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'image')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('image')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // No-op: the `image` column is owned by the create-table
        // migrations; this defensive migration never created it here.
    }
};
