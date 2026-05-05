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
        'coupon_id',
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

    /** Phase 2.5b — single applied coupon (no stacking, D-2.5b-3). */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
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

    /**
     * Phase 2.6a — single source of truth for "is the applied coupon
     * still valid, and what's its discount on this subtotal."
     *
     * Auto-clears (and persists) the cart's coupon_id when the
     * referenced coupon has been deactivated or expired since the
     * user applied it — so neither CartService::totalsFor nor
     * CheckoutService::quote can ever quote a phantom discount.
     *
     * Returns null when no coupon is applied OR the applied coupon is
     * stale and was just cleared. Otherwise returns a 3-key payload:
     *   [
     *     'coupon'   => Coupon model instance,
     *     'discount' => float (raw, unrounded),
     *     'meta'     => ['code', 'name', 'discount_amount' (rounded)],
     *   ]
     */
    public function reloadCoupon(float $subtotal): ?array
    {
        if ($this->coupon_id === null) {
            return null;
        }

        $coupon = $this->relationLoaded('coupon') ? $this->coupon : $this->coupon()->first();

        if ($coupon && $coupon->is_active && !$coupon->isExpired()) {
            $discount = (float) $coupon->calculateDiscount($subtotal);
            return [
                'coupon'   => $coupon,
                'discount' => $discount,
                'meta'     => [
                    'code'            => $coupon->code,
                    'name'            => $coupon->name,
                    'discount_amount' => round($discount, 2),
                ],
            ];
        }

        // Stale ref (deactivated or expired since apply). Self-heal.
        $this->coupon_id = null;
        $this->save();
        return null;
    }
}
