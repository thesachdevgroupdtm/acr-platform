<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'slug'  => $this->slug,
            'title' => $this->name,
            'name'  => $this->name,
            'image' => $this->image,
        ];
    }
}
