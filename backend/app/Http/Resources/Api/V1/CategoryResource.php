<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sub-phase L1 — public read-only service-category projection.
 * `position` is exposed so the frontend can render category lists
 * in operator-controlled order without an extra sort key.
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'hero_image_url' => ImageUrl::resolve($this->image),
            'position'       => $this->position,
        ];
    }
}
