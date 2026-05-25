<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sub-phase L1 — public read-only brand projection.
 *
 * Hides internal audit fields (is_auto_created, auto_created_*,
 * reviewed_*, seo_enriched_at, include_in_sitemap) — those drive
 * back-of-house workflow and aren't part of the public contract.
 *
 * Field rename per L1 operator decision: schema column `image` is
 * exposed under the API key `hero_image_url` so the frontend reads
 * a stable public name independent of the underlying DB column.
 */
class BrandResource extends JsonResource
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
