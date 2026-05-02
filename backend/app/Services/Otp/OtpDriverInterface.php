<?php

namespace App\Services\Otp;

/**
 * Per /PHASE2_CONTRACT.md §7.1.
 *
 * Implementations are bound in AppServiceProvider::register() via
 * config('services.otp.driver'). Production deploys MUST bind a
 * non-DevModeOtpDriver — the boot guard in AppServiceProvider::boot()
 * refuses to start the framework otherwise.
 */
interface OtpDriverInterface
{
    /**
     * @param  'phone'|'email' $channel
     * @param  string          $destination  10-digit phone (no +91) or email
     * @param  string          $code         4–6 digit code, plaintext
     * @return bool                          delivered? (true means we trust it shipped)
     */
    public function send(string $channel, string $destination, string $code): bool;
}
