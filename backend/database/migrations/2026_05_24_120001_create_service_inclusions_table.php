<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service-pages redesign Phase 1 (D-P1-1) — per-service "what's
 * included" inclusions as a child table (additive only).
 *
 * One service hasMany inclusions, ordered by `position`. Each row is a
 * bulleted line item ("Engine Oil Replacement") with an optional
 * thumbnail. Cascade-deletes with its parent service.
 *
 * Guarded (Schema::hasTable) so a re-run / partial-state migrate is a
 * no-op — never drops or renames anything.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('service_inclusions')) {
            return;
        }

        Schema::create('service_inclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('image')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['service_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inclusions');
    }
};
