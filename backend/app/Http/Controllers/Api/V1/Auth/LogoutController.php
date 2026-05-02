<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/auth/logout
 *
 * Per /PHASE2_CONTRACT.md §5.1 #5. Revokes the current Sanctum
 * personal-access token only — other tokens (other devices) stay
 * valid. Always returns success even if the token is already gone.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        $token?->delete();

        return response()->json(['success' => true]);
    }
}
