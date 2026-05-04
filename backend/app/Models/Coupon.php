<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2.5b — Coupon model.
 *
 * Per /PHASE2_CONTRACT.md §3 + decisions D-2.5b-1, D-2.5b-5, D-2.5b-6.
 *
 * Validation primitives are exposed as instance methods so
 * CouponService::validate() can compose them in the canonical
 * order without re-implementing each rule. The expensive checks
 * (usage limits) hit the coupon_usages count, so callers should
 * eager-load the relation when looping.
 *
 * Stacking is forbidden: at most one Coupon row applies to a Cart
 * or Order at a time (carts.coupon_id, orders.coupon_id are single
 * FKs). The hasMany relations here go the other way — each Coupon
 * has many usages / orders / carts.
 */
class Coupon extends Model
{
    use HasFactory;

    public const DISCOUNT_TYPE_PERCENT = 'percent';
    public const DISCOUNT_TYPE_FLAT    = 'flat';

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_value',
        'applicable_service_ids',
        'applicable_category_ids',
        'usage_limit',
        'usage_per_user',
        'expiry_date',
        'is_active',
        'is_featured',
        'badge',
        'display_order',
    ];

    protected $casts = [
        'applicable_service_ids'  => 'array',
        'applicable_category_ids' => 'array',
        'discount_value'          => 'decimal:2',
        'max_discount'            => 'decimal:2',
        'min_order_value'         => 'decimal:2',
        'is_active'               => 'boolean',
        'is_featured'             => 'boolean',
        'expiry_date'             => 'date',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /* ─────────────── Scopes ─────────────── */

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeNotExpired(Builder $q): Builder
    {
        return $q->where(function (Builder $sub) {
            $sub->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', today()->toDateString());
        });
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    /* ─────────────── Predicates ─────────────── */

    public function isExpired(): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->isPast();
    }

    public function hasReachedGlobalLimit(): bool
    {
        if ($this->usage_limit === null) return false;
        return $this->usages()->count() >= $this->usage_limit;
    }

    public function hasReachedUserLimit(int $userId): bool
    {
        if ($this->usage_per_user === null) return false;
        return $this->usages()->where('user_id', $userId)->count()
            >= $this->usage_per_user;
    }

    /**
     * Returns true if the coupon's category/service filters allow at
     * least one of the given (serviceId, categoryId) pairs.
     *
     * Logic (D-2.5b-1):
     *   - applicable_service_ids null AND applicable_category_ids null
     *     → applies to everything.
     *   - applicable_service_ids set: serviceId must be in the list.
     *   - applicable_category_ids set: categoryId must be in the list.
     *   - When both set: either match qualifies (OR).
     *
     * @param array<int,array{service_id:?int, category_id:?int}> $pairs
     */
    public function appliesToAnyOf(array $pairs): bool
    {
        $serviceFilter  = $this->applicable_service_ids;
        $categoryFilter = $this->applicable_category_ids;

        // Unfiltered coupon — always applies.
        if (empty($serviceFilter) && empty($categoryFilter)) {
            return true;
        }

        foreach ($pairs as $p) {
            $sid = $p['service_id']  ?? null;
            $cid = $p['category_id'] ?? null;
            if (!empty($serviceFilter) && $sid !== null && in_array($sid, $serviceFilter, true)) {
                return true;
            }
            if (!empty($categoryFilter) && $cid !== null && in_array($cid, $categoryFilter, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compute the discount this coupon would apply to the given
     * subtotal. Caller should still call validate() before applying;
     * this method is pure math.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_FLAT) {
            return round(min((float) $this->discount_value, $subtotal), 2);
        }

        // percent
        $raw = $subtotal * ((float) $this->discount_value / 100.0);
        if ($this->max_discount !== null) {
            $raw = min($raw, (float) $this->max_discount);
        }
        return round(min($raw, $subtotal), 2);
    }
}
