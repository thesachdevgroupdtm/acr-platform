<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Service;
use App\Models\ServicePrice;

/**
 * Phase 2.3 — server-side cart pricing per /PHASE2_CONTRACT.md §6.6.
 *
 * Pricing rules:
 *  - If brand/model/fuel are all known: look up service_prices for
 *    that 4-tuple. If found, use it.
 *  - If no priced row exists AND service.base_price is set: use the
 *    base_price.
 *  - Otherwise throw NoPriceConfiguredException (controller → 422).
 *
 * service_prices stores the fuel column as `fuel_type_id`; the new
 * cart_items column is `fuel_id`. Mapping happens here so the rest
 * of the codebase only ever sees `fuel_id`.
 *
 * totalsFor() is intentionally simple in 2.3: subtotal is item math,
 * tax is 0 (Decision D-B / contract §4.3), discount is 0 because
 * coupons land in 2.6. /cart/coupon and DELETE /cart/coupon return
 * 501 in this commit.
 */
class CartService
{
    public function priceServiceItem(
        Service $service,
        ?int $brandId,
        ?int $modelId,
        ?int $fuelId,
    ): float {
        if ($brandId && $modelId && $fuelId) {
            $row = ServicePrice::query()
                ->where('service_id', $service->id)
                ->where('brand_id', $brandId)
                ->where('model_id', $modelId)
                ->where('fuel_type_id', $fuelId)
                ->first();
            if ($row) {
                return (float) $row->price;
            }
        }

        if ($service->base_price !== null) {
            return (float) $service->base_price;
        }

        throw new NoPriceConfiguredException(
            'No price configured for this vehicle.'
        );
    }

    public function totalsFor(Cart $cart): array
    {
        $items = $cart->relationLoaded('items') ? $cart->items : $cart->items()->get();

        $subtotal = (float) $items->sum(
            fn ($i) => (float) $i->unit_price_snapshot * (int) $i->quantity
        );

        // TODO Phase 2.6 — coupon discount math. Until the coupons
        // table lands, /cart/coupon returns 501 and totals always
        // reports coupon=null / discount=0.
        $discount = 0.0;
        $coupon   = null;
        $tax      = 0.0;        // Decision D-B — cart is pre-tax.

        $total = max(0.0, $subtotal - $discount + $tax);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'coupon'   => $coupon,
            'tax'      => round($tax, 2),
            'total'    => round($total, 2),
        ];
    }
}
