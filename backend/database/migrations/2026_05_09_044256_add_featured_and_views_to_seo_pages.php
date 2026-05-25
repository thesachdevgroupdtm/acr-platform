<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5b-polish — feature flag + view counter on seo_pages.
 *
 * `is_featured` drives the editorial layout on /explore (Hero +
 * Trending sections). `view_count` is a placeholder for Phase 6
 * analytics — ships at zero, NOT incremented anywhere yet.
 *
 * Compound index `(is_featured, is_published)` covers the
 * featured-pages query the Hero section runs on every render.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_published');
            $table->unsignedInteger('view_count')->default(0)->after('is_featured');
            $table->index(['is_featured', 'is_published'], 'seo_pages_featured_published_idx');
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table) {
            $table->dropIndex('seo_pages_featured_published_idx');
            $table->dropColumn(['is_featured', 'view_count']);
        });
    }
};
