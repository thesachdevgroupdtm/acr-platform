<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sub-phase L1 — public read-only fuel-type projection.
 *
 * `hero_image_url` exposes the (now-present) fuel image as a fully-qualified
 * storage URL via ImageUrl::resolve — null when no image is set.
 */
class FuelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'hero_image_url' => ImageUrl::resolve($this->image),
        ];
    }
}
