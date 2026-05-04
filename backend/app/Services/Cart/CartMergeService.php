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
 * Called from two places:
 *  1. VerifyOtpController — on successful OTP verify, if the
 *     request carried an X-Cart-Session header.
 *  2. MergeCartController — explicit POST /cart/merge for the
 *     re-merge / multi-device case.
 *
 * Both paths converge here. The implementation is idempotent:
 * re-running the same merge after the first one finishes is a
 * no-op because the guest cart is marked status='converted'.
 *
 * Algorithm (transaction-locked):
 *   1. SELECT … FOR UPDATE both carts.
 *   2. For each guest item:
 *        - tuple match on user cart → bump matched item's quantity.
 *        - no match → reparent the row (UPDATE cart_id).
 *   3. Mark guest cart 'converted' + expires_at = now().
 *   4. Bump user cart expires_at to now()->addDays(90).
 *
 * Tuple key: (kind, ref_id, brand_id, model_id, fuel_id) — the
 * same dedup key CartController::addItem uses for same-cart re-add.
 * Quantities are summed; the user-cart's unit_price_snapshot wins
 * (the authenticated session has been on the platform longer and
 * may have older prices honored). Metadata is preserved on the
 * surviving row.
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

            // No guest cart on file — nothing to merge.
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

            $guestItems = $guestCart->items()->get();
            $userItems  = $userCart->items()->get()->keyBy(
                fn (CartItem $i) => $this->tupleKey($i)
            );

            $merged = 0;
            $moved  = 0;

            foreach ($guestItems as $guestItem) {
                $key = $this->tupleKey($guestItem);
                if ($userItems->has($key)) {
                    /** @var CartItem $userItem */
                    $userItem = $userItems->get($key);
                    $userItem->quantity = min(99, $userItem->quantity + $guestItem->quantity);
                    $userItem->save();
                    $guestItem->delete();
                    $merged++;
                } else {
                    $guestItem->cart_id = $userCart->id;
                    $guestItem->save();
                    $userItems->put($key, $guestItem);
                    $moved++;
                }
            }

            $guestCart->status     = 'converted';
            $guestCart->expires_at = now();
            $guestCart->save();

            $userCart->expires_at = now()->addDays(90);
            $userCart->save();

            Log::info('Cart merge completed', [
                'user_id'        => $userId,
                'guest_uuid'     => $guestUuid,
                'guest_cart_id'  => $guestCart->id,
                'user_cart_id'   => $userCart->id,
                'merged_items'   => $merged,
                'moved_items'    => $moved,
            ]);

            return $userCart->refresh()->load([
                'items.service',
                'items.brand',
                'items.carModel',
                'items.fuel',
            ]);
        });
    }

    /**
     * Same dedup key CartController::addItem uses. Nulls are stable
     * (a no-vehicle item matches another no-vehicle item).
     */
    private function tupleKey(CartItem $item): string
    {
        return implode('|', [
            $item->kind(),
            $item->refId() ?? 'null',
            $item->brand_id ?? 'null',
            $item->model_id ?? 'null',
            $item->fuel_id  ?? 'null',
        ]);
    }
}
