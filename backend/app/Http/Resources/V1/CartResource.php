<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.3 — per /PHASE2_CONTRACT.md §4.3.
 *
 * Caller must eager-load `items` (and ideally `items.service` for
 * `display_title` / `image` to populate from the canonical source
 * rather than item meta).
 */
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items   = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->get();

        $totals  = $this->resource->totals();
        $count   = (int) $items->sum('quantity');

        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'currency'     => $this->currency,
            'expires_at'   => optional($this->expires_at)->toISOString(),
            'item_count'   => $count,
            'items'        => CartItemResource::collection($items),
            'totals'       => $totals,
            'is_user_cart' => $this->user_id !== null,
        ];
    }
}
