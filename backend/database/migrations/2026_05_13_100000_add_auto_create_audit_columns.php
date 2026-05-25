<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.3.5 — Audit + SEO-posture columns for the auto-bootstrap
 * resolver layer. Applied additively to the five master-data tables
 * the resolver auto-populates from the pricing-matrix Excel:
 *
 *   - car_brands, car_models, fuel_types, services, service_categories
 *
 * Every column lands with a safe default so existing rows (the
 * production master data shipped pre-Phase-4.3.5) are untouched:
 *
 *   is_auto_created       BOOLEAN  DEFAULT FALSE
 *   auto_created_from     VARCHAR  NULL    (e.g. 'pricing_matrix_import')
 *   auto_created_import_id BIGINT  NULL    FK → imports.id (SET NULL)
 *   reviewed_at           TIMESTAMP NULL
 *   reviewed_by           BIGINT   NULL    FK → users.id (SET NULL)
 *   include_in_sitemap    BOOLEAN  DEFAULT TRUE
 *   seo_enriched_at       TIMESTAMP NULL
 *
 * Existing rows therefore default to:
 *   is_auto_created = false (they were created manually)
 *   include_in_sitemap = true (preserve current sitemap content)
 *   the rest = null
 *
 * Auto-created rows post-Phase-4.3.5 will explicitly set
 * is_auto_created=true and include_in_sitemap=false so the sitemap
 * generator (Phase 4.5b) can exclude them until an operator marks
 * the row reviewed and SEO-enriched.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->boolean('is_auto_created')->default(false)->after('is_active');
                $t->string('auto_created_from', 50)->nullable()->after('is_auto_created');
                $t->unsignedBigInteger('auto_created_import_id')->nullable()->after('auto_created_from');
                $t->timestamp('reviewed_at')->nullable()->after('auto_created_import_id');
                $t->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
                $t->boolean('include_in_sitemap')->default(true)->after('reviewed_by');
                $t->timestamp('seo_enriched_at')->nullable()->after('include_in_sitemap');

                $t->index('auto_created_import_id', "{$table}_auto_created_import_idx");

                // FK to imports.id and users.id with SET NULL so audit
                // rows aren't tombstoned if their source import is
                // deleted, and reviewer assignment survives user
                // deletion (deactivated reviewer becomes "unknown").
                $t->foreign('auto_created_import_id', "{$table}_auto_created_import_fk")
                    ->references('id')->on('imports')->nullOnDelete();
                $t->foreign('reviewed_by', "{$table}_reviewed_by_fk")
                    ->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign("{$table}_auto_created_import_fk");
                $t->dropForeign("{$table}_reviewed_by_fk");
                $t->dropIndex("{$table}_auto_created_import_idx");
                $t->dropColumn([
                    'is_auto_created',
                    'auto_created_from',
                    'auto_created_import_id',
                    'reviewed_at',
                    'reviewed_by',
                    'include_in_sitemap',
                    'seo_enriched_at',
                ]);
            });
        }
    }

    private const TABLES = [
        'car_brands',
        'car_models',
        'fuel_types',
        'services',
        'service_categories',
    ];
};
