<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GET /api/v1/user/profile  (auth:sanctum)
 * PUT /api/v1/user/profile  (auth:sanctum)
 *
 * Per /PHASE2_CONTRACT.md §5.1 #6 #7. Phone updates are intentionally
 * NOT permitted via PUT — phone is the primary identifier and any
 * change must round-trip through /auth/verify-otp. Incoming `phone`
 * fields are silently ignored with a logged warning.
 */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('defaultAddress');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if ($request->has('phone')) {
            Log::warning(
                'Ignored phone update attempt on /user/profile (PUT).',
                ['user_id' => $request->user()->id]
            );
        }

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'min:2', 'max:120'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:191'],
        ]);

        $user = $request->user();

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        // Email change drops the verification flag — re-verify required.
        if (array_key_exists('email', $validated) && $validated['email'] !== $user->email) {
            $user->email = $validated['email'];
            $user->is_verified_email = false;
        }

        $user->save();

        return response()->json([
            'user' => new UserResource($user->fresh()->load('defaultAddress')),
        ]);
    }
}
