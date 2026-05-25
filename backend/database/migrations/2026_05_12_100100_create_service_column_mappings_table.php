<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.3 — persistent mapping from Excel pricing-matrix column
 * headers to services.id.
 *
 * Layer 2 of the four-layer resolution strategy (D-4.3-2). Once
 * the operator confirms an ambiguous Excel header maps to a given
 * service, the mapping lives here and Layer 2 hits on every
 * subsequent import — no manual re-mapping per upload.
 *
 * `service_id` is nullable so the operator can also store
 * "explicitly ignore this column" decisions (mapping with
 * is_active=true and service_id=null).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_column_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('excel_column', 200);
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            // App-level case-insensitive matching uses LOWER(TRIM(excel_column)).
            // We index the raw column for fast equality lookups; the in-memory
            // hash in PricingMatrixImport handles the normalisation.
            $table->index('excel_column');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_column_mappings');
    }
};
