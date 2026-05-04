<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2.5a — Order per /PHASE2_CONTRACT.md §3.
 *
 * Snapshot pattern: name/phone/email/vehicle are frozen at placement
 * time. Pricing snapshots live on related order_items rows.
 *
 * coupon_id is intentionally NOT cast here as a relation — the
 * coupons table doesn't exist until Phase 2.5b. The coupon()
 * relation is added in that commit.
 *
 * State machine (D-2.5a-5):
 *   pending     → confirmed | cancelled
 *   confirmed   → in_service | cancelled (admin-only)
 *   in_service  → completed
 *   completed   → terminal
 *   cancelled   → terminal
 *
 * Customer cancellations are gated by canBeCancelledBy(). Admin
 * transitions ship in Phase 4 (Filament).
 */
class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_IN_SERVICE = 'in_service';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';

    public const PAYMENT_STATUS_PENDING  = 'pending';
    public const PAYMENT_STATUS_PAID     = 'paid';
    public const PAYMENT_STATUS_FAILED   = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'user_id',
        'service_center_id',
        'coupon_id',
        'status',
        'payment_status',
        'name_snapshot',
        'phone_snapshot',
        'email_snapshot',
        'address',
        'vehicle_snapshot',
        'preferred_date',
        'preferred_time',
        'subtotal',
        'discount',
        'tax',
        'total',
        'notes',
        'is_high_risk',
        'placed_at',
        'confirmed_at',
        'in_service_at',
        'completed_at',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $casts = [
        'vehicle_snapshot' => 'array',
        'subtotal'         => 'decimal:2',
        'discount'         => 'decimal:2',
        'tax'              => 'decimal:2',
        'total'            => 'decimal:2',
        'is_high_risk'     => 'boolean',
        'preferred_date'   => 'date',
        'placed_at'        => 'datetime',
        'confirmed_at'     => 'datetime',
        'in_service_at'    => 'datetime',
        'completed_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /** Phase 2.5b — single applied coupon (no stacking, D-2.5b-3). */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeOlderThan(Builder $q, int $minutes): Builder
    {
        return $q->where('created_at', '<', now()->subMinutes($minutes));
    }

    /**
     * Customer-initiated cancel rule (D-2.5a-5):
     * - Only the owner can cancel.
     * - Only when status='pending'. After auto-confirm flips to
     *   'confirmed' (default 2 hours), the user must contact support.
     */
    public function canBeCancelledBy(User $user): bool
    {
        return $this->user_id === $user->id
            && $this->status === self::STATUS_PENDING;
    }

    /**
     * Apply a status transition with timestamp bookkeeping.
     * Returns false on an illegal transition; the caller should
     * surface this as a 409/422.
     *
     * Phase 2.5a only exercises pending→confirmed (auto) and
     * pending→cancelled (user). The remaining edges are wired so
     * Phase 4 admin tooling has a single API to call.
     */
    public function transitionTo(string $newStatus, ?string $reason = null): bool
    {
        $allowed = [
            self::STATUS_PENDING    => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED  => [self::STATUS_IN_SERVICE, self::STATUS_CANCELLED],
            self::STATUS_IN_SERVICE => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED  => [],
            self::STATUS_CANCELLED  => [],
        ];

        if (!in_array($newStatus, $allowed[$this->status] ?? [], true)) {
            return false;
        }

        $this->status = $newStatus;
        $now = now();
        match ($newStatus) {
            self::STATUS_CONFIRMED  => $this->confirmed_at  = $now,
            self::STATUS_IN_SERVICE => $this->in_service_at = $now,
            self::STATUS_COMPLETED  => $this->completed_at  = $now,
            self::STATUS_CANCELLED  => $this->cancelled_at  = $now,
            default                 => null,
        };
        if ($newStatus === self::STATUS_CANCELLED && $reason !== null) {
            $this->cancelled_reason = $reason;
        }
        $this->save();
        return true;
    }
}
