<?php

namespace App\Http\Resources;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Optional vehicle context — when present, the resource attaches the
     * resolved price for that brand/model/fuel from service_prices.
     *
     * @var array{price?: float|null, brand_id?: int, model_id?: int, fuel_type_id?: int}|null
     */
    public ?array $vehiclePriceContext = null;

    public function withVehiclePrice(?array $ctx): self
    {
        $this->vehiclePriceContext = $ctx;
        return $this;
    }

    public function toArray(Request $request): array
    {
        // Phase 2.6a — three explicit price fields. The frontend used to
        // run a parallel `/pricing` query because `price` was overloaded
        // (base when no vehicle, resolved when vehicle was passed) and
        // the caller had no way to tell which it got.
        //
        //   base_price       — always the service's base price
        //   vehicle_price    — non-null only when a vehicle context was
        //                      passed AND service_prices had a row
        //   effective_price  — vehicle_price ?? base_price; the value to
        //                      display / charge
        $vehiclePrice    = $this->vehiclePriceContext['price'] ?? null;
        $effectivePrice  = $vehiclePrice ?? $this->base_price;

        return [
            'id'                => $this->id,
            'sc_id'             => $this->category_id,
            'category_id'       => $this->category_id,
            'slug'              => $this->slug,
            'title'             => $this->name,
            'name'              => $this->name,
            'description'       => $this->description,
            // D-P1-6 — full public URL via ImageUrl (was raw relative path).
            // null stays null; idempotent on already-absolute URLs.
            'image'             => ImageUrl::resolve($this->image),
            // Legacy `price` retained as alias of effective_price so any
            // un-migrated consumer keeps working until full sweep.
            'price'             => $effectivePrice,
            'base_price'        => $this->base_price,
            'vehicle_price'     => $vehiclePrice,
            'effective_price'   => $effectivePrice,
            'time_takes'        => $this->time_takes,
            'time_unit'         => $this->time_unit,
            'warrenty_info'     => $this->warrenty_info,
            'recommended_info'  => $this->recommended_info,
            // Phase 1 (D-P1-2) — service-interval display copy.
            'interval_info'     => $this->interval_info,
            'note'              => $this->note,
            // Phase 1 (D-P1-1/5) — "what's included" line items. Only
            // serialized when the relation is eager-loaded (detail
            // endpoint), so list endpoints stay lean. Each inclusion's
            // image is resolved to a full URL (D-P1-6).
            'inclusions'        => $this->whenLoaded('inclusions', fn () =>
                $this->inclusions->map(fn ($inc) => [
                    'id'         => $inc->id,
                    'label'      => $inc->label,
                    // Phase 1.5 (D-1.5-1/2) — raw bucket string or null
                    // (null = ungrouped; Phase 2 buckets it under Essential).
                    'group_name' => $inc->group_name,
                    'image'      => ImageUrl::resolve($inc->image),
                    'position'   => $inc->position,
                ])->values()
            ),
            'category_detail'   => $this->whenLoaded('category', fn () =>
                new ServiceCategoryResource($this->category)
            ),
        ];
    }
}
