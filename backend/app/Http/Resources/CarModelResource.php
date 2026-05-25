<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'brand_id' => $this->brand_id,
            'slug'     => $this->slug,
            'title'    => $this->name,
            'name'     => $this->name,
            'segment'  => $this->segment,
            'image'    => $this->image,
        ];
    }
}
