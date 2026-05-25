<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5 — operator-curated related-page pivot.
 *
 * When rows exist for a page, they win over the on-the-fly
 * category/tag heuristic in SeoPage::getRelatedPages(). When
 * empty, the controller falls back to the heuristic — so
 * Phase 4.5 ships without requiring operator curation.
 *
 * Phase 4.5 follow-up: Filament admin UX for picking related
 * pages drag-drop.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_page_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_page_id')
                ->constrained('seo_pages')
                ->cascadeOnDelete();
            $table->foreignId('related_seo_page_id')
                ->constrained('seo_pages')
                ->cascadeOnDelete();
            $table->smallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['seo_page_id', 'related_seo_page_id'], 'seo_page_related_unique_pair');
            $table->index(['seo_page_id', 'display_order'], 'seo_page_related_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_page_related');
    }
};
