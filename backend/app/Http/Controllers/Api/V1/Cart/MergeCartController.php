<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CartResource;
use App\Services\Cart\CartMergeException;
use App\Services\Cart\CartMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/v1/cart/merge — Phase 2.4 explicit re-merge endpoint.
 *
 * Per /PHASE2_CONTRACT.md §5.3 #18. The OTP verify hook in
 * VerifyOtpController already merges automatically when the verify
 * request carries an X-Cart-Session header; this endpoint exists
 * for the multi-device case where a user authenticates without
 * sending the guest UUID, then later wants to attach an orphaned
 * guest cart to their session.
 *
 * Auth: required (sanctum). The cart-session middleware on the
 * route resolves the user cart implicitly; this handler accepts
 * the guest UUID from the body and delegates to CartMergeService.
 *
 * Idempotent: a second call with the same guest_session_uuid is a
 * no-op (the guest cart is already 'converted' from the first
 * call), and returns the user cart unchanged.
 */
class MergeCartController extends Controller
{
    public function __construct(private CartMergeService $cartMerge)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_session_uuid' => ['required', 'uuid'],
        ]);

        try {
            $cart = $this->cartMerge->mergeGuestIntoUser(
                $validated['guest_session_uuid'],
                $request->user()->id,
            );
        } catch (CartMergeException $e) {
            // Service-side defensive — rare, logged for ops.
            Log::error('Cart merge unrecoverable failure', [
                'user_id'    => $request->user()->id,
                'guest_uuid' => $validated['guest_session_uuid'],
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Could not merge cart. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['cart' => new CartResource($cart)]);
    }
}
