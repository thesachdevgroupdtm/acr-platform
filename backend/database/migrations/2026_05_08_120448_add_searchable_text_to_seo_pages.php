<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5b-fix — searchable_text column on seo_pages.
 *
 * Stores a stripped/normalized concatenation of title + excerpt
 * + category + tags + body so the /api/v1/explore search query
 * matches against rich content, not just title. Repopulated on
 * every save via the SeoPage::saving event.
 *
 * MySQL gets a FULLTEXT index for natural-language matching;
 * SQLite (test env) just uses LIKE which scales fine for the
 * test fixtures. The model's relevance ORDER BY works on both
 * drivers — see SeoPageController::explore.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->text('searchable_text')->nullable()->after('body');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE seo_pages ADD FULLTEXT INDEX seo_pages_searchable_fulltext (searchable_text)'
            );
        }

        // Backfill existing rows so any pages seeded BEFORE this
        // migration get an indexable searchable_text on first
        // explore search. saveQuietly bypasses model events to
        // avoid re-firing the sitemap cache-bust hook.
        \App\Models\SeoPage::query()->each(function (\App\Models\SeoPage $page) {
            $page->searchable_text = \App\Models\SeoPage::generateSearchableText($page);
            $page->saveQuietly();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // DROP INDEX before dropping the column it depends on.
            DB::statement('ALTER TABLE seo_pages DROP INDEX seo_pages_searchable_fulltext');
        }

        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropColumn('searchable_text');
        });
    }
};
