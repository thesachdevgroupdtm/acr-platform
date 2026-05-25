<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5 — normalized seo_page_categories table.
 *
 * Phase 4.5b shipped a free-form `seo_pages.category` string
 * column. This migration adds the normalized table; the
 * existing string column stays for backwards-compat. The
 * follow-up migration (enhance_seo_pages_for_explore_editorial)
 * adds `category_id` and backfills it by matching the string
 * to a seeded category name.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_page_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon_name', 50)->nullable();
            $table->smallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_page_categories');
    }
};
