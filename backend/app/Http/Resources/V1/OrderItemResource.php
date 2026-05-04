<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.5a — order line item resource per /PHASE2_CONTRACT.md §4.4.
 *
 * Snapshots are authoritative for display (service_title_snapshot,
 * unit_price_snapshot, line_total_snapshot). The current_* fields
 * mirror the live relation when eager-loaded, useful for "what does
 * the service look like now" detail views — Phase 2.5a does not
 * surface those in the UI but keeps the field reserved.
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unitPrice = (float) $this->unit_price_snapshot;
        $lineTotal = (float) $this->line_total_snapshot;
        $qty       = (int) $this->quantity;

        return [
            'id'                     => $this->id,
            'service_id'             => $this->service_id,
            'package_id'             => $this->package_id,
            'product_id'             => $this->product_id,
            'service_title_snapshot' => $this->service_title_snapshot,
            'quantity'               => $qty,
            'unit_price_snapshot'    => round($unitPrice, 2),
            'line_total_snapshot'    => round($lineTotal, 2),
            'vehicle' => ($this->brand_id || $this->model_id || $this->fuel_id) ? [
                'brand_id'   => $this->brand_id,
                'brand_name' => $this->whenLoaded('brand', fn () => $this->brand?->name),
                'model_id'   => $this->model_id,
                'model_name' => $this->whenLoaded('carModel', fn () => $this->carModel?->name),
                'fuel_id'    => $this->fuel_id,
                'fuel_name'  => $this->whenLoaded('fuel', fn () => $this->fuel?->name),
            ] : null,
            'meta' => $this->meta,
        ];
    }
}
