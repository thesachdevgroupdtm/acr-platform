<?php

namespace App\Http\Resources\V1;

use App\Models\SeoPage;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 4.5 — compact card payload for /api/v1/explore.
 *
 * Smaller than the full SeoPage payload (no body, no
 * searchable_text, no SEO record cascade). Sized for the
 * editorial card surfaces on /explore.
 *
 * The category block prefers the FK relationship; falls back
 * to the legacy `category` string when no FK is set so legacy
 * pages still render with a category badge.
 *
 * @mixin SeoPage
 */
class SeoPageCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $cat = $this->whenLoaded('categoryRelation', fn () => $this->categoryRelation, function () {
            return null;
        });

        return [
            'id'                   => $this->id,
            'slug'                 => $this->slug,
            'title'                => $this->title,
            'excerpt'              => $this->excerpt,
            'hero_image_url'       => $this->hero_image_url,
            'category'             => $cat
                ? ['slug' => $cat->slug, 'name' => $cat->name, 'icon_name' => $cat->icon_name]
                : ($this->category ? ['slug' => null, 'name' => $this->category, 'icon_name' => null] : null),
            'reading_time_minutes' => $this->reading_time,
            'is_featured'          => (bool) $this->is_featured,
            'is_trending'          => (bool) $this->is_trending,
            'view_count'           => (int) $this->view_count,
            'published_at'         => $this->published_at?->toIso8601String(),
        ];
    }
}
