<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2.3 — cart line item per /PHASE2_CONTRACT.md §2.5.
 *
 * Exactly one of (service_id, package_id, product_id) MUST be
 * non-null. Enforced by the saving event below since
 * service_packages and products tables don't exist until 2.6 — the
 * FK constraints to those tables are deferred to that commit.
 *
 * unit_price_snapshot is server-trusted: every write recomputes it
 * from service_prices (or the service's base_price fallback) per
 * contract §6.6. Clients may not pass unit_price_snapshot.
 */
class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'service_id',
        'package_id',
        'product_id',
        'brand_id',
        'model_id',
        'fuel_id',
        'quantity',
        'unit_price_snapshot',
        'meta',
    ];

    protected $casts = [
        'meta'                => 'array',
        'quantity'            => 'integer',
        'unit_price_snapshot' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CartItem $item) {
            $set = collect([$item->service_id, $item->package_id, $item->product_id])
                ->filter(fn ($v) => $v !== null)
                ->count();
            if ($set !== 1) {
                throw new \DomainException(
                    'CartItem must have exactly one of service_id, package_id, product_id set; got ' . $set
                );
            }
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'brand_id');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    public function fuel(): BelongsTo
    {
        return $this->belongsTo(FuelType::class, 'fuel_id');
    }

    /** Derived `kind` per CartItemResource — service in 2.3, package/product land in 2.6. */
    public function kind(): string
    {
        if ($this->service_id) return 'service';
        if ($this->package_id) return 'package';
        if ($this->product_id) return 'product';
        return 'unknown';
    }

    public function refId(): ?int
    {
        return $this->service_id ?? $this->package_id ?? $this->product_id;
    }
}
