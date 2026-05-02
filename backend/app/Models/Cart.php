<?php

namespace App\Models;

use App\Services\Cart\CartService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2.3 — server-authoritative cart per /PHASE2_CONTRACT.md §2.4.
 *
 * A cart belongs to a user OR a guest session UUID. CartSession
 * middleware resolves which one for every cart-route request and
 * attaches the model to the request.
 */
class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_uuid',
        'currency',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /**
     * Scope to a single owner — exactly one of $userId / $sessionUuid
     * must be set.
     */
    public function scopeForOwner(Builder $q, ?int $userId, ?string $sessionUuid): Builder
    {
        if ($userId === null && $sessionUuid === null) {
            throw new \InvalidArgumentException('forOwner requires user_id or session_uuid');
        }
        return $userId !== null
            ? $q->where('user_id', $userId)
            : $q->where('session_uuid', $sessionUuid);
    }

    /**
     * Server-computed totals. Always pre-tax (Decision D-B): the cart
     * surfaces a subtotal + discount; tax is calculated at order
     * placement, not here. Coupon application is gated to Phase 2.6
     * (the coupons table doesn't exist yet) so discount is always 0.
     */
    public function totals(): array
    {
        return app(CartService::class)->totalsFor($this);
    }
}
