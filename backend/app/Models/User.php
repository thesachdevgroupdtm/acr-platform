<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
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
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_admin',
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
        'is_admin'          => 'boolean',
        'is_verified_phone' => 'boolean',
        'is_verified_email' => 'boolean',
        'password'          => 'hashed',
    ];

    /**
     * Phase 4.1 — Filament admin-panel access gate.
     *
     * Filament calls this for every panel-protected request after
     * the user has authenticated via the standard guard. Returning
     * false produces a 403 / redirect to login. The customer OTP
     * flow (Sanctum bearer tokens) is unaffected — this method is
     * only consulted when a session-authenticated user hits an
     * /admin route.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

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

    /**
     * Phase 2.3 — every cart this user has ever owned (any status).
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Phase 2.3 — the user's currently active cart, if any. The
     * CartSession middleware firstOrCreates exactly one active row
     * per (user_id, status='active'), so this hasOne is safe.
     */
    public function activeCart(): HasOne
    {
        return $this->hasOne(Cart::class)->where('status', 'active');
    }

    /**
     * Phase 2.5a — every order this user has placed.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
