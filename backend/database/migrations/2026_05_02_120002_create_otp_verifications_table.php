<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.1 — OTP verification ledger.
 *
 * Per /PHASE2_CONTRACT.md §2.2. user_id nullable so OTPs can be sent
 * during lead-capture before the user record is finalised. otp_code
 * stored hashed (sha256) — never plaintext. attempts enforces lockout
 * (max 3 per code).
 *
 * NOTE: contract specified otp_code as string(8) but sha256 hex is
 * 64 chars; using 64 here. The 'BYPASS' sentinel for the dev-bypass
 * audit row (Decision D-C) fits inside the same column. Documented
 * as a deviation in /PHASE2_1_REPORT.md.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('channel', ['phone', 'email']);
            $table->string('destination', 191);          // phone digits or email
            $table->string('otp_code', 64);               // hashed (sha256, 64 hex chars) — never plaintext
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['channel', 'destination', 'verified_at']);
            $table->index(['user_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
