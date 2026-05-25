<?php

namespace App\Models;

use App\Traits\HasSeoMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 2.5a — service_centers (D-2.5a-2).
 *
 * Pulled forward from Phase 2.6 because orders need an FK target.
 * The 4 seeded rows mirror the LOCATIONS constant in
 * src/data/businessData.ts. Phase 2.6 will likely extend this table
 * (opening hours, photos, amenities) — not done here to keep the
 * schema diff for 2.5a small.
 */
class ServiceCenter extends Model
{
    use HasFactory, HasSeoMetadata;

    protected $fillable = [
        'slug',
        'name',
        'address',
        'phone',
        'email',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Phase 4.5c — Filament ServiceCenterResource needs to gate
     * delete actions on whether any order still points at this
     * center. The FK lives on orders.service_center_id (see
     * 2026_05_04_120002_create_orders_table.php).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
