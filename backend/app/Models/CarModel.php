<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    use HasFactory;
    use \App\Models\Concerns\CleansOldImage;

    protected $table = 'car_models';

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'segment',
        'image',
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
        'is_auto_created'    => 'boolean',
        'include_in_sitemap' => 'boolean',
        'reviewed_at'        => 'datetime',
        'seo_enriched_at'    => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'brand_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'model_id');
    }

    public function fuelTypes(): BelongsToMany
    {
        return $this->belongsToMany(FuelType::class, 'car_model_fuel_types', 'car_model_id', 'fuel_type_id')
            ->withTimestamps();
    }
}
