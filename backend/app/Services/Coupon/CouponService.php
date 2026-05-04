<?php

namespace App\Services\Coupon;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;

/**
 * Phase 2.5b — coupon validation + apply pipeline.
 *
 * Per /PHASE2_CONTRACT.md §6 + decisions D-2.5b-5/6/7.
 *
 * The validate() method's ordering matters — earlier checks are
 * cheaper (index lookup, in-memory date compare); usage-limit
 * checks hit the coupon_usages table and run last.
 *
 * apply / remove never re-validate themselves; callers (controllers,
 * checkout pipeline) are expected to validate first when applying
 * fresh user input. The cart-stored coupon may go stale (deactivated
 * since apply); CartService::totalsFor handles that auto-clear so
 * the cart never quotes a phantom discount.
 *
 * @phpstan-type ValidationResult array{
 *   valid: bool,
 *   coupon?: Coupon|null,
 *   reason?: string|null,
 *   discount_amount?: float|null,
 * }
 */
class CouponService
{
    /**
     * @return array{valid:bool, coupon:?Coupon, reason:?string, discount_amount:?float}
     */
    public function validate(string $code, Cart $cart, ?User $user): array
    {
        $code = trim(strtoupper($code));

        $coupon = Coupon::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return $this->fail('Invalid coupon code.');
        }

        if ($coupon->isExpired()) {
            return $this->fail('This coupon has expired.');
        }

        $subtotal = $this->cartSubtotal($cart);
        if ($subtotal < (float) $coupon->min_order_value) {
            $required = number_format((float) $coupon->min_order_value, 0);
            return $this->fail("Minimum order ₹{$required} required to use this coupon.");
        }

        if ($coupon->hasReachedGlobalLimit()) {
            return $this->fail('This coupon has reached its usage limit.');
        }

        if ($user !== null && $coupon->hasReachedUserLimit($user->id)) {
            return $this->fail('You have already used this coupon.');
        }

        if (!$this->cartHasApplicableItem($cart, $coupon)) {
            return $this->fail('This coupon is not applicable to items in your cart.');
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'valid'           => true,
            'coupon'          => $coupon,
            'reason'          => null,
            'discount_amount' => $discount,
        ];
    }

    /**
     * Persist `cart.coupon_id`. Last-apply-wins (D-2.5b-3).
     * Caller MUST validate() first.
     */
    public function applyToCart(Coupon $coupon, Cart $cart): Cart
    {
        $cart->coupon_id = $coupon->id;
        $cart->save();
        return $cart->refresh()->load('coupon');
    }

    public function removeFromCart(Cart $cart): Cart
    {
        if ($cart->coupon_id === null) {
            return $cart;
        }
        $cart->coupon_id = null;
        $cart->save();
        return $cart->refresh();
    }

    /**
     * Phase 2.5b D-2.5b-7 — claim a usage row at order placement.
     * Called inside CheckoutService::placeOrder transaction so the
     * usage is atomic with the order.
     */
    public function claim(Coupon $coupon, User $user, Order $order, float $discountAmount): CouponUsage
    {
        return CouponUsage::create([
            'coupon_id'       => $coupon->id,
            'user_id'         => $user->id,
            'order_id'        => $order->id,
            'discount_amount' => round($discountAmount, 2),
            'used_at'         => now(),
        ]);
    }

    /* ─────────────── internal ─────────────── */

    /** @return array{valid:false, coupon:null, reason:string, discount_amount:null} */
    private function fail(string $reason): array
    {
        return [
            'valid'           => false,
            'coupon'          => null,
            'reason'          => $reason,
            'discount_amount' => null,
        ];
    }

    private function cartSubtotal(Cart $cart): float
    {
        $items = $cart->relationLoaded('items') ? $cart->items : $cart->items()->get();
        return (float) $items->sum(
            fn ($i) => (float) $i->unit_price_snapshot * (int) $i->quantity
        );
    }

    private function cartHasApplicableItem(Cart $cart, Coupon $coupon): bool
    {
        $serviceFilter  = $coupon->applicable_service_ids;
        $categoryFilter = $coupon->applicable_category_ids;

        if (empty($serviceFilter) && empty($categoryFilter)) {
            return true;
        }

        // Eager-load services with their category so appliesToAnyOf
        // can answer in-memory without per-row queries.
        $items = $cart->relationLoaded('items') ? $cart->items : $cart->items()->with('service')->get();

        $pairs = $items->map(function ($i) {
            return [
                'service_id'  => $i->service_id,
                'category_id' => $i->relationLoaded('service') && $i->service
                    ? $i->service->category_id
                    : null,
            ];
        })->all();

        return $coupon->appliesToAnyOf($pairs);
    }
}
