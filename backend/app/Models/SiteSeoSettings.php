<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Phase 4.5a — single-row site SEO defaults.
 *
 * Always exactly one row (id=1). The `current()` accessor
 * `firstOrCreate`s it so callers don't have to seed-guard.
 *
 * Fields drive the HasSeoMetadata fallback chain — see
 * PHASE4_5A_ARCHITECTURE.md §2 for the cascade order.
 */
class SiteSeoSettings extends Model
{
    protected $table = 'site_seo_settings';

    protected $fillable = [
        'default_meta_title_template',
        'default_meta_description',
        'default_og_image',
        'default_twitter_handle',
        'default_twitter_card',
        'default_robots_meta',
        'organization_jsonld',
        'google_site_verification',
        'facebook_domain_verification',
    ];

    protected $casts = [
        'organization_jsonld' => 'array',
    ];

    /**
     * Return the (always exactly one) site-wide settings row,
     * creating it on first access if the seeder hasn't run.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
