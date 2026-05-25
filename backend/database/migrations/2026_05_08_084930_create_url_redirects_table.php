<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5a — url_redirects table.
 *
 * Operator-managed 301/302 redirects. Phase 4.5a creates the
 * table + lookup helper only; the catch-all middleware that
 * actually performs the redirect lands in Phase 4.5b alongside
 * the SeoPage frontend route.
 *
 * `hits` exists for Phase 6 analytics but is not incremented
 * yet — touching it on every request without a queue would
 * convert a read into a write per redirect.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('url_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->smallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['from_path', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_redirects');
    }
};
