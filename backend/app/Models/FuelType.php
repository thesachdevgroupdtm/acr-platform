<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    use HasFactory;
    use \App\Models\Concerns\CleansOldImage;

    protected $fillable = [
        'name',
        'slug',
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

    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'fuel_type_id');
    }
}
