<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\Otp\OtpDriverInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/auth/login
 *
 * Per /PHASE2_CONTRACT.md §5.1 #4. Phone-only entry: triggers an
 * OTP send and returns the pending state. The actual auth grant
 * happens at /auth/verify-otp.
 *
 * 404 when no user matches the phone — prevents enumeration noise
 * while staying honest. Sign-up flows must call /auth/lead-capture.
 */
class LoginController extends Controller
{
    public function __invoke(Request $request, OtpDriverInterface $otp): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
        ]);

        $phone = $validated['phone'];
        $user = User::query()->where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for that phone. Please sign up first.',
            ], 404);
        }

        OtpVerification::query()
            ->where('channel', 'phone')
            ->where('destination', $phone)
            ->whereNull('verified_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::create([
            'user_id'     => $user->id,
            'channel'     => 'phone',
            'destination' => $phone,
            'otp_code'    => hash('sha256', $code),
            'expires_at'  => now()->addMinutes(10),
            'attempts'    => 0,
            'ip'          => $request->ip(),
        ]);

        $otp->send('phone', $phone, $code);

        $payload = [
            'success'         => true,
            'pending_user_id' => $user->id,
            'otp_sent_to'     => 'phone',
        ];
        if (config('app.debug')) {
            $payload['dev_code'] = $code;
        }

        return response()->json($payload);
    }
}
