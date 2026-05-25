<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sub-phase L1 — public read-only car-model projection.
 * Scoped output: `brand_id` lets the frontend resolve the parent.
 */
class ModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'brand_id'       => $this->brand_id,
            'segment'        => $this->segment,
            'hero_image_url' => ImageUrl::resolve($this->image),
        ];
    }
}
