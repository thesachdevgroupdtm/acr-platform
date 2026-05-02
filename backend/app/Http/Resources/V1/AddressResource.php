<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Per /PHASE2_CONTRACT.md §4.2.
 *
 * Mirrored 1:1 by `AddressResource` in src/types/api.ts on the
 * frontend. is_default is forced to bool — the cast on the model
 * already does this, but coercing again here means callers that
 * receive a raw array survive an accidental cast change.
 */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'label'      => $this->label,
            'line1'      => $this->line1,
            'line2'      => $this->line2,
            'city'       => $this->city,
            'state'      => $this->state,
            'pincode'    => $this->pincode,
            'landmark'   => $this->landmark,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
