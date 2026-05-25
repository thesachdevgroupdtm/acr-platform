<?php

namespace App\Http\Resources;

use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // D-P1-6 — all category image fields resolved to full public
        // URLs via ImageUrl (were raw relative paths). null stays null.
        $imageUrl = ImageUrl::resolve($this->image);

        return [
            'id'          => $this->id,
            'slug'        => $this->slug,
            'title'       => $this->name,
            'name'        => $this->name,
            'description' => $this->description,
            'image'       => $imageUrl,
            'image_1'     => $imageUrl,
            'icon_image'  => ImageUrl::resolve($this->icon_image),
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
