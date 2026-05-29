<?php

namespace App\Http\Resources;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight sub-service shape for list endpoints (/home, /services).
 *
 * Phase 2.6a — list endpoints can now optionally attach vehicle-
 * resolved pricing inline (was: a parallel POST /pricing call from
 * the frontend). Three explicit price fields:
 *
 *   base_price       — always the service's base price
 *   vehicle_price    — non-null only when the controller passed a
 *                      vehicle context AND service_prices had a row
 *   effective_price  — vehicle_price ?? base_price; the value to use
 *
 * NOTE: the spec listed `position` in the shape, but the `services`
 * table has no `position` column (only `service_categories` does).
 * Adding one would require a migration, which is out-of-scope. Field
 * is omitted; consumers should rely on the API's `orderBy('id')` for
 * stable ordering.
 */
class SubServiceResource extends JsonResource
{
    /**
     * Optional vehicle context — when present, the resource attaches the
     * resolved price for that brand/model/fuel from service_prices.
     *
     * @var array{price?: float|null}|null
     */
    public ?array $vehiclePriceContext = null;

    public function withVehiclePrice(?array $ctx): self
    {
        $this->vehiclePriceContext = $ctx;
        return $this;
    }

    public function toArray(Request $request): array
    {
        // Two ways to inject vehicle pricing:
        //   1. `withVehiclePrice([...])` chained directly (per-instance)
        //   2. `Service::$resolvedVehiclePrice` set by the controller
        //      before this resource wraps the model (used by list
        //      endpoints that build a bulk price map).
        $vehiclePrice   = $this->vehiclePriceContext['price']
            ?? $this->resource->resolvedVehiclePrice
            ?? null;
        $effectivePrice = $vehiclePrice ?? $this->base_price;

        return [
            'id'              => $this->id,
            'slug'            => $this->slug,
            'name'            => $this->name,
            // `title` is an alias of `name`, matching every other Resource
            // in this app (ServiceCategoryResource, CarBrandResource, etc.).
            // Existing frontend consumers access .title; aliasing here
            // avoids forcing a 30+ site rename for no semantic gain.
            'title'           => $this->name,
            'base_price'      => $this->base_price,
            'vehicle_price'   => $vehiclePrice,
            'effective_price' => $effectivePrice,
            // D-P1-6 — full public URL via ImageUrl (was raw relative path).
            'image'           => ImageUrl::resolve($this->image),
            'time_takes'      => $this->time_takes,
            'time_unit'       => $this->time_unit,
            // Phase 1 (D-P1-2) — service-interval display copy. Inclusions
            // are intentionally omitted from the lean list shape (D-P1-5);
            // they ship on the per-service detail endpoint only.
            'interval_info'   => $this->interval_info,
            // Phase 2 (PART A) — lean inclusions preview ({labels, total}).
            // Empty default unless a controller bulk-populates the transient
            // property (the in-scope category page uses ServiceResource, not
            // this lean shape; kept here for consistency across list shapes).
            'inclusions_preview' => $this->resource->inclusionsPreview,
        ];
    }
}
