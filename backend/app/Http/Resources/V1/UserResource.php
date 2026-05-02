<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Per /PHASE2_CONTRACT.md §4.1.
 *
 * `default_address` is left as null in Phase 2.1 — Address model
 * lands in 2.2. Once it does, this resource gains an
 * AddressResource alongside; the `whenLoaded` guard means callers
 * that don't eager-load the relation get null back, not a missing
 * key.
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
            'default_address'   => null,                              // Phase 2.2
            'created_at'        => optional($this->created_at)->toISOString(),
            'last_login_at'     => optional($this->last_login_at)->toISOString(),
        ];
    }
}
