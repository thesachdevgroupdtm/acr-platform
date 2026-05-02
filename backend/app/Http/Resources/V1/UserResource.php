<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Per /PHASE2_CONTRACT.md §4.1.
 *
 * `default_address` resolves to an AddressResource when the caller
 * eager-loads the relation (ProfileController does this). Endpoints
 * that don't eager-load get null — never a missing key — so the TS
 * shape `default_address: AddressResource | null` always holds.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'phone'             => $this->phone,
            'email'             => $this->email,
            'is_verified_phone' => (bool) $this->is_verified_phone,
            'is_verified_email' => (bool) $this->is_verified_email,
            'role'              => $this->role,
            'default_address'   => $this->whenLoaded(
                'defaultAddress',
                fn () => $this->defaultAddress
                    ? new AddressResource($this->defaultAddress)
                    : null,
                null,
            ),
            'created_at'        => optional($this->created_at)->toISOString(),
            'last_login_at'     => optional($this->last_login_at)->toISOString(),
        ];
    }
}
