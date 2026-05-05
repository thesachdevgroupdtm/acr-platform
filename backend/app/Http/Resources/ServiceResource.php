<?php

namespace App\Http\Resources;

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
            'image'             => $this->image,
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
            'note'              => $this->note,
            'category_detail'   => $this->whenLoaded('category', fn () =>
                new ServiceCategoryResource($this->category)
            ),
        ];
    }
}
