<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4.5.3 — drops the newsletter_subscriptions table created
     * in Phase 4.5.1. Operator decision: lead-form replaces the
     * newsletter widget; no need to retain the unused table.
     *
     * The original create migration stays in history; this drop
     * migration runs on top to leave the schema clean. Down()
     * recreates the original shape so a `migrate:rollback` chain
     * can re-instate the table without resurrecting deleted code.
     */
    public function up(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }

    public function down(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }
};
