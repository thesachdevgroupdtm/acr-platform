<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'slug'            => $this->slug,
            'title'           => $this->title,
            'seo_title'       => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords'    => $this->seo_keywords,
            'sections'        => SectionResource::collection(
                $this->whenLoaded('sections')
            ),
        ];
    }
}
