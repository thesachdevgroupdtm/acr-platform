<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Sub-phase L1 — public read-only service projection.
 *
 * `short_description` is derived from `description` (the brief asks
 * for it but the schema has no dedicated column). Trimmed to 160
 * chars for card-style use on the frontend. Full description is
 * exposed under `description` for detail views.
 *
 * `estimated_time` joins `time_takes` + `time_unit` into a single
 * human string ("2 hours") so the consumer doesn't have to assemble
 * it; null when either piece is missing.
 *
 * `category` is included as a nested object only when the service
 * was loaded with its relation (show endpoint). Index endpoint
 * returns just `category_id` to keep the payload tight.
 */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $estimatedTime = null;
        if (! empty($this->time_takes)) {
            $estimatedTime = trim(($this->time_takes ?? '') . ' ' . ($this->time_unit ?? ''));
        }

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'category_id'       => $this->category_id,
            'hero_image_url'    => ImageUrl::resolve($this->image),
            'short_description' => $this->description !== null
                ? Str::limit(strip_tags((string) $this->description), 160)
                : null,
            'description'       => $this->description,
            'base_price'        => $this->base_price !== null ? (float) $this->base_price : null,
            'estimated_time'    => $estimatedTime,
            'category'          => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
        ];
    }
}
