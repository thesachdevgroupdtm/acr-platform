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
            'name'   => ['required', 'string', 'min:2', 'max:120'],
            'phone'  => ['required', 'string', 'regex:/^\d{10}$/'],
            'email'  => ['nullable', 'string', 'email', 'max:191'],
            // Phase 2.3.4 — caller declares intent so we can apply
            // strict phone-uniqueness only when this endpoint is
            // serving a sign-up form. Quick-Estimate / lead-capture
            // continues to soft-merge by phone (existing behavior).
            'intent' => ['nullable', 'string', 'in:signup,lead_capture'],
        ]);

        $phone  = $validated['phone'];
        $name   = trim($validated['name']);
        $email  = $validated['email']  ?? null;
        $intent = $validated['intent'] ?? 'lead_capture';

        // Phase 2.3.4 — strict phone uniqueness on signup. Without
        // this, a duplicate phone is silently merged and the
        // existing account's name was being overwritten by the
        // pre-2.3.4 update branch. Existing accounts must use the
        // login flow.
        if ($intent === 'signup') {
            $existingPhone = User::query()->where('phone', $phone)->first();
            if ($existingPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered. Please log in to your existing account.',
                    'errors'  => [
                        'phone' => ['This phone is already registered.'],
                    ],
                ], 422);
            }
        }

        // Phase 2.3.3 — pre-validate email uniqueness so the
        // users.email UNIQUE index never has a chance to throw a
        // raw QueryException at the UI. If the email already
        // belongs to a DIFFERENT phone, return a clean 422 with a
        // helpful message. If it belongs to the same phone, the
        // firstOrCreate below resolves the existing row and the
        // email assignment is a no-op.
        if ($email) {
            $emailOwner = User::query()
                ->where('email', $email)
                ->where('phone', '!=', $phone)
                ->first();
            if ($emailOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered with another account. Please use a different email, or log in with your existing account.',
                    'errors'  => [
                        'email' => ['This email is already registered.'],
                    ],
                ], 422);
            }
        }

        // OTP-only auth: `password` is nullable per Phase 2.1.1
        // migration; we never write to it on this path.
        // Defense in depth: any future integrity-violation edge case
        // (race on email, etc.) is caught here so APP_DEBUG=true does
        // not leak SQL traces to the response body.
        try {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name'  => $name,
                    'email' => $email,        // may be null
                    'role'  => 'customer',
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account creation failed due to a conflict. Please try a different phone or email.',
                    'errors'  => [],
                ], 422);
            }
            throw $e;
        }

        if (!$user->wasRecentlyCreated) {
            // Phase 2.3.4 — name is NOT overwritten on existing rows.
            // Profile-name edits go through PUT /user/profile (Phase
            // 2.1). Pre-2.3.4 this branch silently rewrote the name
            // on every Quick-Estimate call, which the user reported
            // as "my name keeps changing".
            //
            // Email is still soft-updated when there is no existing
            // verified-different email on the row — same rules as
            // before. A separate-account collision was already
            // caught by the pre-validation block above.
            $changed = false;
            if ($email) {
                $canReplaceEmail =
                    !$user->email
                    || !$user->is_verified_email
                    || strcasecmp($user->email, $email) === 0;
                if ($canReplaceEmail && $user->email !== $email) {
                    $user->email = $email;
                    $changed = true;
                }
            }
            if ($changed) {
                $user->save();
            }
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
