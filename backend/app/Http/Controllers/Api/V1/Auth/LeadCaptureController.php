<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\Otp\OtpDriverInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/auth/lead-capture
 *
 * Per /PHASE2_CONTRACT.md §5.1 #1 + §6.5(c) lazy account creation.
 *
 * Upserts a user by phone (firstOrCreate). For an existing row,
 * updates `name` freely and updates `email` only when current email
 * is null/empty or unverified — never overwrites a verified email
 * with a different unverified address.
 *
 * Generates a fresh 6-digit OTP, persists it as sha256 hash, and
 * returns the plaintext code in the response when APP_DEBUG=true so
 * smoke tests can complete the flow without inspecting laravel.log.
 */
class LeadCaptureController extends Controller
{
    public function __invoke(Request $request, OtpDriverInterface $otp): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'email' => ['nullable', 'string', 'email', 'max:191'],
        ]);

        $phone = $validated['phone'];
        $name  = trim($validated['name']);
        $email = $validated['email'] ?? null;

        // OTP-only auth: `password` is nullable per Phase 2.1.1
        // migration; we never write to it on this path.
        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name'  => $name,
                'email' => $email,        // may be null
                'role'  => 'customer',
            ]
        );

        if (!$user->wasRecentlyCreated) {
            // Existing row — update name freely; email only if not
            // overwriting a verified-different one.
            $user->name = $name;
            if ($email) {
                $canReplaceEmail =
                    !$user->email
                    || !$user->is_verified_email
                    || strcasecmp($user->email, $email) === 0;
                if ($canReplaceEmail) {
                    $user->email = $email;
                }
            }
            $user->save();
        }

        $code = $this->generateCode();
        $this->persistOtp($user->id, 'phone', $phone, $code, $request->ip());
        $otp->send('phone', $phone, $code);

        $payload = [
            'success'         => true,
            'pending_user_id' => $user->id,
            'otp_sent_to'     => 'phone',
        ];
        if (config('app.debug')) {
            $payload['dev_code'] = $code;          // visible in dev only
        }

        return response()->json($payload);
    }

    /** 6-digit zero-padded code. */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Per contract §6.4: only one active OTP per (channel, destination).
     * Existing unverified rows are deleted before insert.
     */
    private function persistOtp(?int $userId, string $channel, string $destination, string $code, ?string $ip): void
    {
        OtpVerification::query()
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->whereNull('verified_at')
            ->delete();

        OtpVerification::create([
            'user_id'     => $userId,
            'channel'     => $channel,
            'destination' => $destination,
            'otp_code'    => hash('sha256', $code),
            'expires_at'  => now()->addMinutes(10),
            'attempts'    => 0,
            'ip'          => $ip,
        ]);
    }
}
