<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\Otp\OtpDriverInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/auth/send-otp
 *
 * Per /PHASE2_CONTRACT.md §5.1 #2.
 *
 * Generates and dispatches a new OTP for an EXISTING user identified
 * by destination + channel. Does NOT create users (that's
 * /auth/lead-capture's job). Returns 404 if no user matches.
 */
class SendOtpController extends Controller
{
    public function __invoke(Request $request, OtpDriverInterface $otp): JsonResponse
    {
        $validated = $request->validate([
            'channel'     => ['required', 'in:phone,email'],
            'destination' => ['required', 'string', 'max:191'],
        ]);

        $channel     = $validated['channel'];
        $destination = $validated['destination'];

        $user = User::query()
            ->where($channel === 'phone' ? 'phone' : 'email', $destination)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for that ' . $channel . '.',
            ], 404);
        }

        // Drop any stale unverified OTPs (only one active per pair).
        OtpVerification::query()
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->whereNull('verified_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        OtpVerification::create([
            'user_id'     => $user->id,
            'channel'     => $channel,
            'destination' => $destination,
            'otp_code'    => hash('sha256', $code),
            'expires_at'  => $expiresAt,
            'attempts'    => 0,
            'ip'          => $request->ip(),
        ]);

        $otp->send($channel, $destination, $code);

        $payload = [
            'success'    => true,
            'expires_at' => $expiresAt->toISOString(),
        ];
        if (config('app.debug')) {
            $payload['dev_code'] = $code;
        }

        return response()->json($payload);
    }
}
