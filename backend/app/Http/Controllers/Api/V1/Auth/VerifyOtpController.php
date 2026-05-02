<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/auth/verify-otp
 *
 * Per /PHASE2_CONTRACT.md §5.1 #3, §6.5(d), and §7.5 dev-bypass.
 *
 * Two paths:
 *  1. OTP_DEV_BYPASS=true AND APP_ENV != production AND code is
 *     4–6 digits → accept. Persists a 'BYPASS' sentinel row to the
 *     OTP ledger so audits distinguish bypass from real verification.
 *  2. Normal path → look up active OTP, hash-compare, increment
 *     attempts on miss (lock at 3), mark verified on hit.
 *
 * On success: marks user.is_verified_phone (or _email) = true,
 * stamps last_login_at, issues a Sanctum token named 'app'.
 *
 * Cart merge (commit 2.4) is intentionally NOT in scope of 2.1.
 */
class VerifyOtpController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel'     => ['required', 'in:phone,email'],
            'destination' => ['required', 'string', 'max:191'],
            'code'        => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ]);

        $channel     = $validated['channel'];
        $destination = $validated['destination'];
        $code        = $validated['code'];

        // ── DEV BYPASS PATH (Decision D-C) ─────────────────────────
        if (
            filter_var(env('OTP_DEV_BYPASS', false), FILTER_VALIDATE_BOOLEAN)
            && !app()->environment('production')
        ) {
            $user = $this->findUserByDestination($channel, $destination);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found for that ' . $channel . '.',
                ], 404);
            }

            // Audit row: distinguishable from real OTP verification.
            OtpVerification::create([
                'user_id'     => $user->id,
                'channel'     => $channel,
                'destination' => $destination,
                'otp_code'    => 'BYPASS',
                'expires_at'  => now(),
                'verified_at' => now(),
                'attempts'    => 0,
                'ip'          => $request->ip(),
            ]);

            return $this->finishVerification($user, $channel);
        }

        // ── NORMAL PATH ────────────────────────────────────────────
        $row = OtpVerification::query()
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 3)
            ->latest('id')
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired or not found. Please request a new code.',
            ], 422);
        }

        if (!hash_equals($row->otp_code, hash('sha256', $code))) {
            $row->increment('attempts');
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
            ], 422);
        }

        $row->verified_at = now();
        $row->save();

        $user = $row->user_id
            ? User::find($row->user_id)
            : $this->findUserByDestination($channel, $destination);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found for verified destination.',
            ], 404);
        }

        return $this->finishVerification($user, $channel);
    }

    private function findUserByDestination(string $channel, string $destination): ?User
    {
        return User::query()
            ->where($channel === 'phone' ? 'phone' : 'email', $destination)
            ->first();
    }

    private function finishVerification(User $user, string $channel): JsonResponse
    {
        if ($channel === 'phone') {
            $user->is_verified_phone = true;
        } else {
            $user->is_verified_email = true;
        }
        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }
}
