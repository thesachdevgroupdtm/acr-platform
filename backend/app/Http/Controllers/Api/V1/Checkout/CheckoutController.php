<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Cart;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2.5a — checkout pipeline.
 *
 * Per /PHASE2_CONTRACT.md §5.4. Both endpoints require auth:sanctum
 * and the cart-session middleware (so the user's active cart is
 * already attached as $request->attributes['cart']).
 *
 * PREFERRED_TIME_OPTIONS is the locked 6-slot list per D-2.5a-1. The
 * frontend mirrors this exact list in src/types/api.ts so the
 * client-side picker can validate before the round-trip.
 */
class CheckoutController extends Controller
{
    public const PREFERRED_TIME_OPTIONS = [
        '09:00 AM – 11:00 AM',
        '11:00 AM – 01:00 PM',
        '01:00 PM – 03:00 PM',
        '03:00 PM – 05:00 PM',
        '05:00 PM – 07:00 PM',
        '07:00 PM – 09:00 PM',
    ];

    public function __construct(private CheckoutService $checkout)
    {
    }

    /**
     * POST /api/v1/checkout/quote
     * Read-only: compute totals (subtotal/GST/total) for the
     * authenticated user's active cart. Used to refresh the order
     * summary when the user edits address/coupon/etc.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $this->validateQuote($request);
        $cart = $this->cart($request);

        return response()->json([
            'quote' => $this->checkout->quote($cart, $validated),
        ]);
    }

    /**
     * POST /api/v1/checkout/place-order
     * Transactional: runs the fake-booking guard, persists order +
     * items + payment, marks the cart 'converted'.
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validated = $this->validatePlaceOrder($request);
        $cart      = $this->cart($request);
        $user      = $request->user();

        if ($cart->items()->count() === 0) {
            return response()->json(
                ['message' => 'Cart is empty.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $order = $this->checkout->placeOrder($cart, $validated, $user);

        return response()->json(
            ['order' => new OrderResource($order)],
            Response::HTTP_CREATED
        );
    }

    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'preferred_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'preferred_time'    => ['required', 'string', Rule::in(self::PREFERRED_TIME_OPTIONS)],
            'service_center_id' => ['required', 'integer', 'exists:service_centers,id'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'name'              => ['nullable', 'string', 'max:255'],
            'phone'             => ['nullable', 'string', 'regex:/^\d{10}$/'],
            'email'             => ['nullable', 'string', 'email', 'max:255'],
            'coupon_code'       => ['nullable', 'string', 'max:60'],   // accepted but ignored in 2.5a
        ]);
    }

    private function validatePlaceOrder(Request $request): array
    {
        $user = $request->user();

        return $request->validate([
            'preferred_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'preferred_time'    => ['required', 'string', Rule::in(self::PREFERRED_TIME_OPTIONS)],
            'service_center_id' => ['required', 'integer', 'exists:service_centers,id'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'name'              => ['required', 'string', 'min:2', 'max:255'],
            // Phone must match the authenticated user (auth identity).
            'phone'             => ['required', 'string', 'regex:/^\d{10}$/', Rule::in([$user->phone])],
            'email'             => ['nullable', 'string', 'email', 'max:255'],
            'coupon_code'       => ['nullable', 'string', 'max:60'],
        ]);
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
