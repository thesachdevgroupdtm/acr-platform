<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 2.5a — order line item per /PHASE2_CONTRACT.md §3.
 *
 * Pricing snapshots (unit_price_snapshot, line_total_snapshot,
 * service_title_snapshot) are frozen at order creation. The related
 * Service / CarBrand / CarModel / FuelType rows can change later
 * without disturbing the historical record.
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_id',
        'package_id',
        'product_id',
        'brand_id',
        'model_id',
        'fuel_id',
        'service_title_snapshot',
        'quantity',
        'unit_price_snapshot',
        'line_total_snapshot',
        'meta',
    ];

    protected $casts = [
        'meta'                => 'array',
        'quantity'            => 'integer',
        'unit_price_snapshot' => 'decimal:2',
        'line_total_snapshot' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
}
