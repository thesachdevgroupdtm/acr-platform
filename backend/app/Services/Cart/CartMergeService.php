<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2.4 — server-side cart merge protocol per
 * /PHASE2_CONTRACT.md §6.5(d) and §5.3 #18.
 *
 * Phase 2.5.1 (D-2.5.1-2) — semantics changed to LAST CART WINS.
 *
 * Why: the previous additive merge surfaced stale items from a
 * prior user-cart session whenever a guest logged in (or merged
 * across devices), often pairing the same service with two
 * different vehicles in one cart. Combined with the new
 * one-vehicle-per-cart UI rule (D-2.5.1-1), additive merge
 * produced impossible UI states. Replacement is the simplest
 * resolution — the user's most recent cart intent (the guest
 * session they just used) is the one that survives.
 *
 * Called from two places:
 *  1. VerifyOtpController — on successful OTP verify, if the
 *     request carried an X-Cart-Session header.
 *  2. MergeCartController — explicit POST /cart/merge for the
 *     re-merge / multi-device case.
 *
 * The implementation is idempotent: re-running after the first
 * merge finishes is a no-op because the guest cart is marked
 * status='converted'.
 *
 * Algorithm (transaction-locked):
 *   1. SELECT … FOR UPDATE both carts.
 *   2. If the guest cart has no items: no-op (preserve user cart).
 *   3. Otherwise:
 *        - DELETE every existing user_cart item.
 *        - REPARENT every guest item to user_cart (UPDATE cart_id).
 *   4. Mark guest cart 'converted' + expires_at = now().
 *   5. Bump user cart expires_at to now()->addDays(90).
 *
 * The previous tuple-match dedup is intentionally gone. Reparenting
 * the guest rows preserves their snapshots (price, meta, vehicle)
 * untouched.
 */
class CartMergeService
{
    public function mergeGuestIntoUser(string $guestUuid, int $userId): Cart
    {
        return DB::transaction(function () use ($guestUuid, $userId): Cart {
            $guestCart = Cart::query()
                ->where('session_uuid', $guestUuid)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            $userCart = Cart::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            // firstOrCreate equivalent inside the transaction; the
            // CartSession middleware would normally do this on the
            // request thread, but the merge is invoked from
            // VerifyOtpController BEFORE the user has hit any
            // cart-session route, so we must firstOrCreate here.
            if (!$userCart) {
                $userCart = Cart::create([
                    'user_id'      => $userId,
                    'status'       => 'active',
                    'currency'     => 'INR',
                    'expires_at'   => now()->addDays(90),
                ]);
                $userCart = Cart::query()
                    ->where('id', $userCart->id)
                    ->lockForUpdate()
                    ->first();
            }

            // No guest cart on file — nothing to merge. The user's
            // existing cart is preserved.
            if (!$guestCart) {
                return $userCart->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel']);
            }

            // Self-merge guard — should never happen because guest
            // carts have user_id NULL and user carts have
            // session_uuid NULL, but defensive programming pays
            // dividends in concurrency code.
            if ($guestCart->id === $userCart->id) {
                return $userCart->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel']);
            }

            $guestItemsCount = $guestCart->items()->count();

            // Guest cart is empty — preserve the user's cart untouched.
            if ($guestItemsCount === 0) {
                $guestCart->status     = 'converted';
                $guestCart->expires_at = now();
                $guestCart->save();

                $userCart->expires_at = now()->addDays(90);
                $userCart->save();

                Log::info('Cart merge: empty guest cart, preserving user cart', [
                    'user_id'       => $userId,
                    'guest_uuid'    => $guestUuid,
                    'guest_cart_id' => $guestCart->id,
                    'user_cart_id'  => $userCart->id,
                ]);

                return $userCart->refresh()->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel']);
            }

            // Last-cart-wins: wipe user cart, reparent guest rows.
            $deletedCount = CartItem::query()
                ->where('cart_id', $userCart->id)
                ->delete();

            $movedCount = CartItem::query()
                ->where('cart_id', $guestCart->id)
                ->update(['cart_id' => $userCart->id]);

            // Carry the guest cart's applied coupon onto the surviving
            // (user) cart. A guest can now apply a coupon and preview the
            // discount before signing in; last-cart-wins means the guest
            // cart is the user's current intent, so its coupon_id wins too
            // — otherwise the discount the guest just saw would silently
            // vanish at login. The per-user usage limit is re-checked at
            // place-order, so carrying it here can't bypass that limit.
            $userCart->coupon_id  = $guestCart->coupon_id;
            $userCart->expires_at = now()->addDays(90);
            $userCart->save();

            $guestCart->status     = 'converted';
            $guestCart->expires_at = now();
            $guestCart->save();

            Log::info('Cart merge: last-cart-wins', [
                'user_id'             => $userId,
                'guest_uuid'          => $guestUuid,
                'guest_cart_id'       => $guestCart->id,
                'user_cart_id'        => $userCart->id,
                'replaced_user_items' => $deletedCount,
                'moved_guest_items'   => $movedCount,
            ]);

            return $userCart->refresh()->load([
                'items.service',
                'items.brand',
                'items.carModel',
                'items.fuel',
            ]);
        });
    }
}
