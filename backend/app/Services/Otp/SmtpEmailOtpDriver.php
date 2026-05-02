<?php

namespace App\Services\Otp;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * Email-only OTP driver. Phone-channel sends fall back to the dev
 * driver — this driver is meant to be paired with a real SMS driver
 * once one is procured, OR used in dev to verify the email path.
 *
 * Per /PHASE2_CONTRACT.md §7.2.
 */
class SmtpEmailOtpDriver implements OtpDriverInterface
{
    public function __construct(private DevModeOtpDriver $fallback)
    {
    }

    public function send(string $channel, string $destination, string $code): bool
    {
        if ($channel !== 'email') {
            return $this->fallback->send($channel, $destination, $code);
        }
        Mail::to($destination)->queue(new OtpMail($code));
        return true;
    }
}
