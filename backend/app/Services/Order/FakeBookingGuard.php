<?php

namespace App\Services\Order;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2.5a — anti-fake-booking heuristics per /PHASE2_CONTRACT.md
 * decision D-2.5a-8.
 *
 * Three blocking checks (raise an exception):
 *   A. is_verified_phone must be true.
 *   B. ≤3 orders / 60 min and ≤5 orders / 24 hr per phone.
 *   C. Same phone + same primary service + same slot within 30 min
 *      counts as a duplicate.
 *
 * One non-blocking flag (mutates checkoutData['is_high_risk']):
 *   - 2nd order in 60 min: high risk.
 *   - Total > 50,000 AND user younger than 24 hr: high risk.
 *
 * The flag persists onto orders.is_high_risk so Phase 4 admin
 * tooling can review without re-running the heuristics.
 *
 * Reads of cart->totals() must happen on a hydrated Cart with items
 * loaded; CheckoutService passes the cart it already holds.
 */
class FakeBookingGuard
{
    /**
     * @throws PhoneNotVerifiedException 403
     * @throws RateLimitedException      429
     * @throws DuplicateBookingException 422
     */
    public function enforce(User $user, Cart $cart, array &$checkoutData): void
    {
        // A. Verified phone gate.
        if (!$user->is_verified_phone) {
            throw new PhoneNotVerifiedException(
                'Phone verification required to place an order.'
            );
        }

        // B. Rate limit — last 60 min.
        $count60 = Order::query()
            ->where('phone_snapshot', $user->phone)
            ->where('created_at', '>', now()->subMinutes(60))
            ->count();
        if ($count60 >= 3) {
            throw new RateLimitedException(
                'Too many orders in the last hour. Please try again later.'
            );
        }

        // B. Rate limit — last 24 hr.
        $count24 = Order::query()
            ->where('phone_snapshot', $user->phone)
            ->where('created_at', '>', now()->subHours(24))
            ->count();
        if ($count24 >= 5) {
            throw new RateLimitedException(
                'Daily order limit reached. Please contact support if you need help.'
            );
        }

        // C. Duplicate detection — same phone + service + slot within 30 min.
        $primaryServiceId = $cart->items()
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->value('service_id');

        if ($primaryServiceId !== null) {
            $duplicate = Order::query()
                ->where('phone_snapshot', $user->phone)
                ->where('preferred_date', $checkoutData['preferred_date'])
                ->where('preferred_time', $checkoutData['preferred_time'])
                ->where('created_at', '>', now()->subMinutes(30))
                ->whereHas('items', function ($q) use ($primaryServiceId) {
                    $q->where('service_id', $primaryServiceId);
                })
                ->exists();

            if ($duplicate) {
                throw new DuplicateBookingException(
                    'Duplicate booking detected. You already booked this service for this slot recently.'
                );
            }
        }

        // High-risk flag (does not block; persisted on orders.is_high_risk).
        $isHighRisk = false;

        // Heuristic 1: ≥2 orders in last 60 min already.
        if ($count60 >= 2) {
            $isHighRisk = true;
        }

        // Heuristic 2: Big-ticket order from a fresh account.
        $cartTotal = (float) ($cart->totals()['total'] ?? 0);
        if ($cartTotal > 50000 && $user->created_at->gt(now()->subHours(24))) {
            $isHighRisk = true;
        }

        $checkoutData['is_high_risk'] = $isHighRisk;

        if ($isHighRisk) {
            Log::warning('High-risk order flagged', [
                'phone'         => $user->phone,
                'user_id'       => $user->id,
                'count_60min'   => $count60,
                'cart_total'    => $cartTotal,
                'user_age_hrs'  => (int) now()->diffInHours($user->created_at),
            ]);
        }
    }
}
