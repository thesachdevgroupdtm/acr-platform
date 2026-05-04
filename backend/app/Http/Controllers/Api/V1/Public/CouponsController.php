<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CouponResource;
use App\Models\Cart;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 2.5b — public coupon listing (D-2.5b-4).
 *
 * Two contexts:
 *
 *   ?context=marketing (default)
 *     Returns featured / active / non-expired coupons. Used by
 *     /coupons marketing page and the picker modal's "browse all"
 *     state when the cart isn't ready.
 *
 *   ?context=cart
 *     Same filter, plus per-coupon eligibility check against the
 *     authenticated user's active cart. Each coupon gets `eligible`
 *     and `ineligible_reason` fields stamped via dynamic attributes
 *     (CouponResource emits them when present). When the request is
 *     unauthenticated or has no cart, eligibility falls back to
 *     "no cart in scope" with eligible=false.
 *
 * Public route — no auth requirement. The eligibility check uses
 * `optional($request->user())` so an anonymous request still gets
 * the marketing payload but with cart-shaped feedback.
 */
class CouponsController extends Controller
{
    public function __construct(private CouponService $coupons)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $context = $request->query('context', 'marketing');
        $context = in_array($context, ['marketing', 'cart'], true) ? $context : 'marketing';

        $list = Coupon::query()
            ->active()
            ->notExpired()
            ->featured()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        if ($context === 'cart') {
            // Public route — resolve the optional Bearer token via
            // Sanctum's guard. Returns null when anonymous.
            $user = $request->user('sanctum');
            $cart = $user
                ? Cart::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->with(['items.service'])
                    ->first()
                : null;

            foreach ($list as $coupon) {
                if ($user === null) {
                    $coupon->setAttribute('eligible', false);
                    $coupon->setAttribute('ineligible_reason', 'Sign in to apply coupons.');
                    continue;
                }
                if ($cart === null || $cart->items->isEmpty()) {
                    $coupon->setAttribute('eligible', false);
                    $coupon->setAttribute(
                        'ineligible_reason',
                        'Add items to your cart to use this coupon.',
                    );
                    continue;
                }

                $result = $this->coupons->validate($coupon->code, $cart, $user);
                $coupon->setAttribute('eligible', (bool) $result['valid']);
                $coupon->setAttribute(
                    'ineligible_reason',
                    $result['valid'] ? null : ($result['reason'] ?? 'Not applicable.'),
                );
            }
        }

        return response()->json([
            'coupons' => CouponResource::collection($list),
        ]);
    }
}
