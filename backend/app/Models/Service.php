<?php

namespace App\Models;

use App\Traits\HasSeoMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory, HasSeoMetadata;
    use \App\Models\Concerns\CleansOldImage;

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
        'interval_info',
        'note',
        'is_active',
        'is_auto_created',
        'auto_created_from',
        'auto_created_import_id',
        'reviewed_at',
        'reviewed_by',
        'include_in_sitemap',
        'seo_enriched_at',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'base_price'         => 'decimal:2',
        'is_auto_created'    => 'boolean',
        'include_in_sitemap' => 'boolean',
        'reviewed_at'        => 'datetime',
        'seo_enriched_at'    => 'datetime',
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

    /**
     * Service-pages redesign Phase 1 (D-P1-1) — "what's included"
     * line items, ordered by their `position`. Eager-load this for the
     * detail API; list endpoints stay lean and skip it.
     */
    public function inclusions(): HasMany
    {
        return $this->hasMany(ServiceInclusion::class, 'service_id')
            ->orderBy('position')
            ->orderBy('id');
    }
}
