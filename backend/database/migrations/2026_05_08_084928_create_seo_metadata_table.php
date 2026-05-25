<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5a — polymorphic seo_metadata table.
 *
 * Single source of truth for per-resource SEO. Any SEO-aware
 * model (Service, ServiceCategory, ServiceCenter, SeoPage in
 * 4.5b, …) attaches via `morphOne` on the (seoable_type,
 * seoable_id) pair. The unique index enforces one SEO row per
 * resource — operators can't accidentally create competing
 * meta_titles for the same page.
 *
 * Field budget (D-4.5a-2): 20 SEO fields grouped basic /
 * Open Graph / Twitter / Schema / Sitemap. See
 * PHASE4_5A_ARCHITECTURE.md §1 for the full design.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();

            // Polymorphic. morphs() creates seoable_type +
            // seoable_id and indexes the pair.
            $table->morphs('seoable');

            // Basic SEO (5)
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots_meta', 50)->default('index,follow');

            // Open Graph (5)
            $table->string('og_title', 70)->nullable();
            $table->string('og_description', 200)->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_keywords', 255)->nullable();
            $table->string('og_type', 50)->default('website');

            // Twitter Cards (4)
            $table->string('twitter_card', 30)->default('summary_large_image');
            $table->string('twitter_title', 70)->nullable();
            $table->string('twitter_description', 200)->nullable();
            $table->string('twitter_image')->nullable();

            // Schema.org (3)
            $table->string('schema_type', 50)->default('None');
            $table->json('schema_data')->nullable();
            $table->text('custom_jsonld')->nullable();

            // Sitemap (3)
            $table->boolean('include_in_sitemap')->default(true);
            $table->decimal('priority', 2, 1)->default(0.5);
            $table->string('changefreq', 20)->default('monthly');

            $table->timestamps();

            // One SEO record per resource — enforced at the DB.
            $table->unique(
                ['seoable_type', 'seoable_id'],
                'seo_metadata_seoable_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
    }
};
