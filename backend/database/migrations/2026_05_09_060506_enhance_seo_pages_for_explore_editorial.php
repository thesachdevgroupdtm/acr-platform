<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5 — adds editorial-discovery columns to seo_pages.
 *
 * 7 new columns + 4 new indexes. `is_featured` and `view_count`
 * already exist from Phase 4.5b-polish — those are skipped
 * with column existence checks so this migration is safe to
 * re-apply on any DB state.
 *
 * Backfill at the bottom seeds the new SeoPageCategory defaults
 * (via SeoPageCategorySeeder) and links each existing page's
 * `category` string to a category_id row when names match
 * (case-insensitive).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('seo_pages', 'is_trending')) {
                $table->boolean('is_trending')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('seo_pages', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_trending');
            }
            if (! Schema::hasColumn('seo_pages', 'hero_priority')) {
                $table->tinyInteger('hero_priority')->nullable()->after('is_pinned');
            }
            if (! Schema::hasColumn('seo_pages', 'last_viewed_at')) {
                $table->timestamp('last_viewed_at')->nullable()->after('view_count');
            }
            if (! Schema::hasColumn('seo_pages', 'reading_time_minutes')) {
                $table->tinyInteger('reading_time_minutes')->unsigned()->nullable()->after('last_viewed_at');
            }
            if (! Schema::hasColumn('seo_pages', 'category_id')) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('seo_page_categories')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('seo_pages', 'hero_image_url')) {
                $table->string('hero_image_url')->nullable()->after('reading_time_minutes');
            }
        });

        // New indexes — try/catch in case they already exist.
        Schema::table('seo_pages', function (Blueprint $table) {
            try { $table->index(['is_featured', 'hero_priority'], 'seo_pages_featured_hero_priority_idx'); } catch (\Throwable $e) {}
            try { $table->index(['is_trending', 'view_count'], 'seo_pages_trending_views_idx'); } catch (\Throwable $e) {}
            try { $table->index(['category_id', 'is_published'], 'seo_pages_category_published_idx'); } catch (\Throwable $e) {}
            try { $table->index('view_count', 'seo_pages_view_count_idx'); } catch (\Throwable $e) {}
        });

        // Seed categories first so backfill below resolves
        // category_id for existing pages.
        (new \Database\Seeders\SeoPageCategorySeeder())->run();

        $categories = DB::table('seo_page_categories')->get(['id', 'name']);
        foreach ($categories as $cat) {
            DB::table('seo_pages')
                ->whereNull('category_id')
                ->whereRaw('LOWER(category) = ?', [strtolower($cat->name)])
                ->update(['category_id' => $cat->id]);
        }
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            try { $table->dropIndex('seo_pages_featured_hero_priority_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('seo_pages_trending_views_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('seo_pages_category_published_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('seo_pages_view_count_idx'); } catch (\Throwable $e) {}

            try { $table->dropForeign(['category_id']); } catch (\Throwable $e) {}

            $table->dropColumn([
                'is_trending', 'is_pinned', 'hero_priority',
                'last_viewed_at', 'reading_time_minutes',
                'category_id', 'hero_image_url',
            ]);
        });
    }
};
