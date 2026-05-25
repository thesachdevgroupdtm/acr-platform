<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4.5.3 — leads table for the explore-sidebar capture form.
     *
     * Spam-tracking fields (status enum + ip_address) live alongside
     * the FK columns. All FKs use `nullable + onDelete set null` so
     * removing a brand / model / service in admin doesn't void the
     * historical lead record.
     *
     * Per HARD CONSTRAINTS (project_data_safety): additive only — no
     * existing schema touched.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->foreignId('brand_id')->nullable()
                ->constrained('car_brands')->nullOnDelete();
            $table->foreignId('model_id')->nullable()
                ->constrained('car_models')->nullOnDelete();
            $table->foreignId('service_id')->nullable()
                ->constrained('services')->nullOnDelete();
            $table->string('source', 64)->default('explore_sidebar');
            $table->enum('status', ['new', 'contacted', 'converted', 'spam'])
                ->default('new');
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
