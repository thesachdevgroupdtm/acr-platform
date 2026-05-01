<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'slug'        => $this->slug,
            'title'       => $this->name,
            'name'        => $this->name,
            'description' => $this->description,
            'image'       => $this->image,
            'image_1'     => $this->image,
            'icon_image'  => $this->icon_image,
            'position'    => $this->position,

            // Nested sub-services — only present when the caller eager-loads
            // the `services` relation (HomeController@index and
            // ServiceController@index do; the per-slug detail endpoint and
            // the `service.category` nesting in service_detail do not).
            // Eliminates the /services/{slug} N+1 on list pages.
            'services'    => SubServiceResource::collection(
                $this->whenLoaded('services')
            ),
        ];
    }
}
