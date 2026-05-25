<?php

namespace App\Models;

use App\Traits\HasSeoMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasFactory, HasSeoMetadata;
    use \App\Models\Concerns\CleansOldImage;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'icon_image',
        'position',
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
        'position'           => 'integer',
        'is_auto_created'    => 'boolean',
        'include_in_sitemap' => 'boolean',
        'reviewed_at'        => 'datetime',
        'seo_enriched_at'    => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    public function activeServices(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id')->where('is_active', true);
    }
}
