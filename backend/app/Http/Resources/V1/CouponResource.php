<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.5b — coupon resource per /PHASE2_CONTRACT.md §4.5 +
 * decision D-2.5b-1.
 *
 * Eligibility flags (`eligible`, `ineligible_reason`) are stamped
 * onto the model as dynamic attributes by CouponsController::index
 * when called with ?context=cart and a usable cart in scope. The
 * resource emits them only when present so /coupons (marketing
 * context) and /coupons?context=cart (with cart eligibility) share
 * the same payload shape, with the cart-only fields appearing
 * conditionally.
 */
class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'description'     => $this->description,
            'discount_type'   => $this->discount_type,
            'discount_value'  => round((float) $this->discount_value, 2),
            'max_discount'    => $this->max_discount !== null
                ? round((float) $this->max_discount, 2)
                : null,
            'min_order_value' => round((float) $this->min_order_value, 2),
            'expiry_date'     => optional($this->expiry_date)->format('Y-m-d'),
            'badge'           => $this->badge,
        ];

        // Eligibility — stamped as dynamic attributes by the controller
        // when ?context=cart. Eloquent allows arbitrary attribute access;
        // when unset, both reads return null and the keys are omitted.
        $eligible = $this->resource->getAttribute('eligible');
        if ($eligible !== null) {
            $payload['eligible'] = (bool) $eligible;
        }
        $reason = $this->resource->getAttribute('ineligible_reason');
        if ($reason !== null) {
            $payload['ineligible_reason'] = (string) $reason;
        }

        return $payload;
    }
}
