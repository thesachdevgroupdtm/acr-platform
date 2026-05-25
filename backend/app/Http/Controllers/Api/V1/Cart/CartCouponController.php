<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CartResource;
use App\Models\Cart;
use App\Services\Coupon\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2.5b — coupon apply / remove on the active cart.
 *
 * Replaces the 501 stubs that lived on CartController in Phases
 * 2.3–2.5a. Both endpoints sit under the `cart-session` middleware
 * group so the resolved Cart is already attached to the request.
 *
 * Design notes:
 *  - Apply is guest-capable (no auth middleware) so a not-signed-in
 *    visitor can preview the discounted total. The user is resolved
 *    OPTIONALLY via $request->user('sanctum'): present for a logged-in
 *    caller (so validate() still enforces usage_per_user at apply),
 *    null for a guest (validate() skips the per-user check). The
 *    per-user limit for guests is enforced later, at the gated
 *    checkout/place-order step where the customer identity is known.
 *  - Last-apply-wins (D-2.5b-3): re-apply just overwrites cart.coupon_id.
 *  - Validate is the gatekeeper; applyToCart never re-checks.
 */
class CartCouponController extends Controller
{
    public function __construct(private CouponService $coupons)
    {
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        $cart = $this->cart($request);
        // Optional Bearer user — the route no longer forces auth, so
        // resolve via the sanctum guard explicitly (the default guard is
        // session-based and would return null for a token request). Null
        // for guests; validate() then skips the per-user usage check.
        $user = $request->user('sanctum');

        $cart->load(['items.service', 'coupon']);

        $result = $this->coupons->validate($validated['code'], $cart, $user);

        if (!$result['valid'] || !$result['coupon']) {
            return response()->json(
                ['message' => $result['reason'] ?? 'Invalid coupon code.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->coupons->applyToCart($result['coupon'], $cart);

        return $this->respondWithCart($cart);
    }

    public function remove(Request $request): JsonResponse
    {
        $cart = $this->cart($request);
        $this->coupons->removeFromCart($cart);
        return $this->respondWithCart($cart);
    }

    private function respondWithCart(Cart $cart): JsonResponse
    {
        $cart->load([
            'items.service',
            'items.brand',
            'items.carModel',
            'items.fuel',
            'coupon',
        ]);
        return response()->json(['cart' => new CartResource($cart)]);
    }

    private function cart(Request $request): Cart
    {
        $cart = $request->attributes->get('cart');
        if (!$cart instanceof Cart) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Cart not resolved by middleware');
        }
        return $cart;
    }
}
