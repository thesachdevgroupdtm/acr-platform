<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 4.5 — normalized SEO page category.
 *
 * Drives the /api/v1/explore "categories" block. The 9 default
 * rows are seeded by `SeoPageCategorySeeder`. Operators can
 * extend / re-order / disable via Filament (Phase 4.5b
 * follow-up).
 */
class SeoPageCategory extends Model
{
    protected $table = 'seo_page_categories';

    protected $fillable = [
        'slug', 'name', 'description',
        'icon_name', 'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(SeoPage::class, 'category_id');
    }

    public function publishedPages(): HasMany
    {
        return $this->hasMany(SeoPage::class, 'category_id')
            ->where('is_published', true)
            ->whereNotNull('published_at');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('position')->orderBy('id');
    }
}
