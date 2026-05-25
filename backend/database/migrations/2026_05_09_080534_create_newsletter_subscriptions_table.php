<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4.5.1 — minimal newsletter capture table.
 *
 * Just an email + timestamp pair for the /explore sidebar
 * widget. Phase 6 will add double-opt-in confirmation + an
 * unsubscribe token; this is intentionally lean so operator
 * can ship the widget today.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
