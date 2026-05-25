<?php

namespace App\Traits;

use App\Models\SeoMetadata;
use App\Models\SiteSeoSettings;
use App\Services\SchemaTemplateEngine;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Phase 4.5a — opt-in SEO support for any Eloquent model.
 *
 * Apply via `use HasSeoMetadata;` on a model. Provides:
 *   - seoMetadata()    polymorphic morphOne to App\Models\SeoMetadata
 *   - getSeoData()     resolved array via the fallback chain
 *                      (record → site defaults → page-title)
 *   - setSeoData()     upsert helper used by Filament SeoFieldGroup
 *
 * The fallback chain is documented in PHASE4_5A_ARCHITECTURE.md §2.
 * Resource-level SEO wins; site defaults fill the gaps; the resource's
 * own name/title is the last-resort literal so getSeoData() never
 * returns nulls in the visible fields.
 */
trait HasSeoMetadata
{
    /**
     * The (at most one) SEO record attached to this resource.
     * Returns null when the operator hasn't created one yet —
     * getSeoData() handles that case via the fallback chain.
     */
    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * Return the resolved SEO array for rendering. Always returns
     * non-null values for the visible fields (meta_title, og_title,
     * twitter_title) thanks to the cascade.
     *
     * @return array<string, mixed>
     */
    public function getSeoData(): array
    {
        $seo      = $this->seoMetadata;
        $defaults = SiteSeoSettings::current();

        // Use the resource's own identifier as the {{page_title}}
        // template variable. Each model exposes one of these.
        $pageTitle = $this->name ?? $this->title ?? 'Page';

        return [
            'meta_title'       => $seo?->meta_title ?? $this->renderTemplate(
                $defaults->default_meta_title_template ?? '{{page_title}}',
                ['page_title' => $pageTitle]
            ),
            'meta_description' => $seo?->meta_description
                ?? $defaults->default_meta_description,
            'meta_keywords'    => $seo?->meta_keywords ?? '',
            'canonical_url'    => $seo?->canonical_url
                ?? (function_exists('request') && request() ? request()->url() : null),
            'robots_meta'      => $seo?->robots_meta
                ?? $defaults->default_robots_meta,

            'og_title'         => $seo?->og_title
                ?? $seo?->meta_title
                ?? $pageTitle,
            'og_description'   => $seo?->og_description
                ?? $seo?->meta_description
                ?? $defaults->default_meta_description,
            'og_image'         => $seo?->og_image
                ?? $defaults->default_og_image,
            'og_keywords'      => $seo?->og_keywords ?? '',
            'og_type'          => $seo?->og_type ?? 'website',

            'twitter_card'        => $seo?->twitter_card
                ?? $defaults->default_twitter_card,
            'twitter_title'       => $seo?->twitter_title
                ?? $seo?->meta_title
                ?? $pageTitle,
            'twitter_description' => $seo?->twitter_description
                ?? $seo?->meta_description,
            'twitter_image'       => $seo?->twitter_image
                ?? $seo?->og_image
                ?? $defaults->default_og_image,

            'schema_jsonld'    => $seo
                ? app(SchemaTemplateEngine::class)->generate($seo)
                : null,

            'include_in_sitemap' => $seo?->include_in_sitemap ?? true,
            'priority'           => $seo?->priority ?? 0.5,
            'changefreq'         => $seo?->changefreq ?? 'monthly',
        ];
    }

    /**
     * Upsert SEO data on this record. Used by Filament forms via
     * SeoFieldGroup once 4.5c surfaces the group on resource forms.
     *
     * @param  array<string, mixed>  $data
     */
    public function setSeoData(array $data): SeoMetadata
    {
        return $this->seoMetadata()->updateOrCreate(
            // morph keys are auto-set by the relation; only the
            // attributes that vary per-update belong in $data.
            [],
            $data
        );
    }

    /**
     * Phase 4.5d — three-state badge driver. Returned as a column
     * accessor (`->seo_status`) so Filament's IconColumn can render
     * a quick "how complete is this record's SEO?" signal in
     * resource tables.
     *
     *   'none'      no seo_metadata row at all
     *   'partial'   row exists but missing meta_title or
     *                meta_description
     *   'complete'  both core fields present
     *
     * Not sortable on the SQL side (computed in PHP) — the column
     * is rendered for visibility, not query optimization.
     */
    public function getSeoStatusAttribute(): string
    {
        $meta = $this->seoMetadata;
        if (! $meta) {
            return 'none';
        }
        if (! $meta->meta_title || ! $meta->meta_description) {
            return 'partial';
        }
        return 'complete';
    }

    /**
     * Tiny mustache-style template substitution. Only used for
     * default_meta_title_template, which currently supports a
     * single {{page_title}} variable. Phase 4.5b may extend the
     * variable set; keep the impl trivial until then.
     */
    protected function renderTemplate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }
}
