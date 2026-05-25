<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5a — single-row site_seo_settings table.
 *
 * Holds the site-wide SEO defaults (per D-4.5a-3): the title
 * template, the fallback meta_description, the og_image used
 * when a resource hasn't supplied its own, the verification
 * tokens for Google / Facebook, and the org-level JSON-LD
 * snippet that lands on every page in Phase 4.5b.
 *
 * Always exactly one row. The model exposes `current()` which
 * `firstOrCreate({ id: 1 })` so callers don't have to guard
 * against a missing seed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_seo_settings', function (Blueprint $table) {
            $table->id();

            // Defaults applied via the HasSeoMetadata fallback chain.
            $table->string('default_meta_title_template')
                ->default('{{page_title}} | ACR Mechanics');
            $table->text('default_meta_description')->nullable();
            $table->string('default_og_image')->nullable();
            $table->string('default_twitter_handle', 50)->nullable();
            $table->string('default_twitter_card', 30)
                ->default('summary_large_image');
            $table->string('default_robots_meta', 50)
                ->default('index,follow');

            // Org-level schema rendered on every page in 4.5b.
            $table->json('organization_jsonld')->nullable();

            // Verification tokens injected as <meta> in 4.5b layout.
            $table->string('google_site_verification', 100)->nullable();
            $table->string('facebook_domain_verification', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_seo_settings');
    }
};
