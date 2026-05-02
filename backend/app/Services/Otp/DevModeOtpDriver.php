<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

/**
 * Dev-only OTP driver. Logs to laravel.log and trusts the code shipped.
 * The matching controller exposes the plaintext code in its response
 * when APP_DEBUG=true so smoke tests can complete the flow without
 * inspecting logs.
 *
 * Production safety: AppServiceProvider::boot() throws a
 * RuntimeException if this class is the bound driver while
 * APP_ENV=production.
 */
class DevModeOtpDriver implements OtpDriverInterface
{
    public function send(string $channel, string $destination, string $code): bool
    {
        Log::info("[OTP/{$channel}] {$destination} → {$code}");
        return true;
    }
}
