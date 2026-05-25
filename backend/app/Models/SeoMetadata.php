<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Phase 4.5a — polymorphic SEO record.
 *
 * One row per resource (enforced by the seo_metadata_seoable_unique
 * index). Holds the 20 SEO fields described in
 * PHASE4_5A_ARCHITECTURE.md §1. The `seoable()` morphTo lets the
 * SchemaTemplateEngine reach back to the parent (Service /
 * ServiceCategory / ServiceCenter / SeoPage) to auto-fill fields
 * like name/address/dates without operators having to retype them.
 */
class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = [
        // Basic
        'meta_title', 'meta_description', 'meta_keywords',
        'canonical_url', 'robots_meta',
        // Open Graph
        'og_title', 'og_description', 'og_image',
        'og_keywords', 'og_type',
        // Twitter Cards
        'twitter_card', 'twitter_title', 'twitter_description',
        'twitter_image',
        // Schema.org
        'schema_type', 'schema_data', 'custom_jsonld',
        // Sitemap
        'include_in_sitemap', 'priority', 'changefreq',
    ];

    protected $casts = [
        'schema_data'        => 'array',
        'include_in_sitemap' => 'boolean',
        'priority'           => 'float',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Phase 4.5b — sitemap cache invalidation. Whenever any SEO
     * record changes, the cached sitemap.xml may be stale.
     */
    protected static function booted(): void
    {
        $bust = function () {
            cache()->forget('sitemap_xml');
        };
        static::saved($bust);
        static::deleted($bust);
    }
}
