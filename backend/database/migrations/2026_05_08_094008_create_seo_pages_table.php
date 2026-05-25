<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5b — operator-managed SEO pages.
 *
 * Each row is a customer-facing content page rendered at /:slug
 * by the React frontend. SEO meta lives in the polymorphic
 * seo_metadata table from Phase 4.5a (via the HasSeoMetadata
 * trait on App\Models\SeoPage), NOT inline here — keeps the
 * SEO surface uniform across SeoPage, Service, ServiceCategory,
 * ServiceCenter.
 *
 * Reserved-slug enforcement happens in the Filament form layer
 * (SeoPage::reservedSlugs() guard); the unique index on slug
 * is the database-level safety net.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('title', 200);
            $table->string('excerpt', 300)->nullable();
            $table->text('body');
            $table->string('category', 100)->nullable()->index();
            $table->json('tags')->nullable();
            $table->string('layout', 50)->default('standard');
            $table->string('cta_title', 100)->nullable();
            $table->string('cta_button_text', 50)->nullable();
            $table->string('cta_button_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
