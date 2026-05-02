<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.3 — per /PHASE2_CONTRACT.md §4.3.
 *
 * `kind` is derived from which target FK is non-null. In 2.3 only
 * 'service' is reachable; 'package' and 'product' light up in 2.6.
 *
 * `display_title` gives the frontend a stable label without forcing
 * it to load the related Service/Package/Product resource. Filled
 * from the eager-loaded relation when present, otherwise from
 * `meta.title` (which the frontend writes when it adds an item).
 */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $kind         = $this->resource->kind();
        $unitPrice    = (float) $this->unit_price_snapshot;
        $qty          = (int) $this->quantity;
        $displayTitle = $this->resolveDisplayTitle();

        return [
            'id'                  => $this->id,
            'kind'                => $kind,
            'ref_id'              => $this->resource->refId(),
            'display_title'       => $displayTitle,
            'category_slug'       => $this->meta['category_slug'] ?? null,
            'image'               => $this->resolveImage(),
            'unit_price_snapshot' => round($unitPrice, 2),
            'quantity'            => $qty,
            'line_total'          => round($unitPrice * $qty, 2),
            'vehicle'             => ($this->brand_id || $this->model_id || $this->fuel_id)
                ? [
                    'brand_id' => $this->brand_id,
                    'model_id' => $this->model_id,
                    'fuel_id'  => $this->fuel_id,
                ]
                : null,
            'meta'                => $this->meta,
        ];
    }

    private function resolveDisplayTitle(): string
    {
        if ($this->relationLoaded('service') && $this->service) {
            return (string) ($this->service->name ?? '');
        }
        $metaTitle = $this->meta['title'] ?? null;
        return is_string($metaTitle) ? $metaTitle : '';
    }

    private function resolveImage(): ?string
    {
        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->image ?: null;
        }
        return $this->meta['image'] ?? null;
    }
}
