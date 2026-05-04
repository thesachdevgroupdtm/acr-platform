<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Phase 2.5a — service center resource per /PHASE2_CONTRACT.md §4.4.
 *
 * Used by GET /service-centers (checkout dropdown) and embedded into
 * OrderResource so the frontend can render the chosen center on the
 * confirmation / detail pages without a second roundtrip.
 */
class ServiceCenterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'slug'      => $this->slug,
            'name'      => $this->name,
            'address'   => $this->address,
            'phone'     => $this->phone,
            'email'     => $this->email,
            'city'      => $this->city,
            'state'     => $this->state,
            'pincode'   => $this->pincode,
            'latitude'  => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
        ];
    }
}
