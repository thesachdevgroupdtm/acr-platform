<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Phase 2.1 — User model extended for OTP-based auth.
 *
 * Phone is the primary identifier. Email is optional and tracked
 * separately by `is_verified_email`. `password` field remains in the
 * skeleton schema but is unused in the OTP-only flow (Phase 2 ships
 * with no password endpoints; see contract §11 Assumption 15).
 *
 * Relations to Address / Cart / Order land in later Phase 2 commits
 * (2.2 / 2.3 / 2.5) — intentionally omitted here to avoid forward-
 * declared dead relations.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_verified_phone',
        'is_verified_email',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_verified_phone' => 'boolean',
        'is_verified_email' => 'boolean',
        'password'          => 'hashed',
    ];

    /**
     * History of OTP verifications for this user (any channel).
     */
    public function otps(): HasMany
    {
        return $this->hasMany(OtpVerification::class);
    }

    /**
     * Phase 2.2 — addresses owned by this user.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Phase 2.2 — eager-loadable single default address.
     * "Exactly one is_default=true per user" is enforced by
     * AddressController; this hasOne returns the first match if that
     * invariant ever broke.
     */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }
}
