<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image',
        'base_price',
        'time_takes',
        'time_unit',
        'warrenty_info',
        'recommended_info',
        'note',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'base_price' => 'decimal:2',
    ];

    /**
     * Phase 2.6a — transient per-instance vehicle price.
     *
     * ServiceController@index pre-resolves prices for the requested
     * brand/model/fuel and stashes the result here, where
     * SubServiceResource picks it up and emits as `vehicle_price` /
     * `effective_price`. Declared as a real public property so it
     * does NOT route through Eloquent's attribute machinery (no
     * implicit serialization, no leak via toArray()).
     */
    public ?float $resolvedVehiclePrice = null;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'service_id');
    }
}
