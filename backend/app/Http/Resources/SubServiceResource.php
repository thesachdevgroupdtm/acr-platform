<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight sub-service shape for list endpoints (/home, /services).
 *
 * This is intentionally a subset of ServiceResource. Vehicle-resolved
 * pricing, faqs, warranty/recommended/notes, and full description are
 * scoped to the per-slug detail endpoint (/api/v1/services/{slug}) and
 * to /api/v1/pricing — list endpoints stay vehicle-agnostic and carry
 * base_price only.
 *
 * NOTE: the spec listed `position` in the shape, but the `services`
 * table has no `position` column (only `service_categories` does).
 * Adding one would require a migration, which is out-of-scope. Field
 * is omitted; consumers should rely on the API's `orderBy('id')` for
 * stable ordering.
 */
class SubServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'base_price' => $this->base_price,
            'image'      => $this->image,
            'time_takes' => $this->time_takes,
            'time_unit'  => $this->time_unit,
        ];
    }
}
