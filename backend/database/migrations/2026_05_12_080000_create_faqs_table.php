<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5d — FAQ infrastructure (Path B from PHASE4_5D_AUDIT.md).
 *
 * Previously the 6 home-page FAQs lived hardcoded in
 * src/components/HomeFAQ.tsx. This table gives admin SEO operators a
 * place to manage them and lets SchemaTemplateEngine emit a real
 * FAQPage JSON-LD on any record with schema_type=FAQPage.
 *
 * The customer-facing HomeFAQ.tsx component is NOT being switched to
 * consume this data in Phase 4.5d (per audit decision — out of
 * scope here). That migration can happen any time once the operator
 * has had a chance to verify the seeded content.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
