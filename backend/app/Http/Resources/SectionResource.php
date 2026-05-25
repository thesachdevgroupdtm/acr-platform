<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'page_id'  => $this->page_id,
            'type'     => $this->type,
            'content'  => $this->content,
            'position' => $this->position,
        ];
    }
}
