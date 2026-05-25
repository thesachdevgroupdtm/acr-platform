<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.3 — audit log for every Excel import attempt.
 *
 * Captures who uploaded what, when, the result counts (valid /
 * invalid / skipped), an error_summary blob (up to 100 errors),
 * the stored file path (for re-download / re-import), and a
 * committed_at marker that flips from null to a timestamp once
 * the operator confirms the preview.
 *
 *   status:        validating | preview_ready | committing | completed | failed
 *   import_type:   brands | models | fuel_types | services | pricing_matrix
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('import_type', 40);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_path')->nullable();
            $table->string('status', 30)->default('validating');
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_valid')->default(0);
            $table->unsignedInteger('rows_invalid')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->json('error_summary')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->index(['import_type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
