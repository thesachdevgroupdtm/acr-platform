<?php

namespace App\Models;

use App\Traits\HasSeoMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Phase 4.5b — operator-managed SEO content page.
 *
 * - Uses HasSeoMetadata trait (4.5a) so SEO fields live in the
 *   polymorphic seo_metadata table, not inline here.
 * - HTML body is sanitized via strip_tags whitelist on save
 *   (D-4.5b-3: no new composer package).
 * - Sitemap cache is invalidated on save/delete via model events.
 * - reservedSlugs() lists frontend route paths that must never
 *   be claimed by an SEO page; the Filament form layer rejects
 *   them, the DB unique index is the last-resort safety net.
 */
class SeoPage extends Model
{
    use HasFactory, HasSeoMetadata;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'searchable_text',
        'category', 'category_id', 'tags', 'layout',
        'cta_title', 'cta_button_text', 'cta_button_url',
        'is_published', 'is_featured', 'is_trending', 'is_pinned',
        'hero_priority', 'view_count', 'last_viewed_at',
        'reading_time_minutes', 'hero_image_url',
        'published_at', 'created_by',
    ];

    protected $casts = [
        'tags'                 => 'array',
        'is_published'         => 'boolean',
        'is_featured'          => 'boolean',
        'is_trending'          => 'boolean',
        'is_pinned'            => 'boolean',
        'hero_priority'        => 'integer',
        'view_count'           => 'integer',
        'reading_time_minutes' => 'integer',
        'last_viewed_at'       => 'datetime',
        'published_at'         => 'datetime',
    ];

    /**
     * Allowed HTML tags in the page body. Anything outside this
     * whitelist is stripped before save. Matches the Filament
     * RichEditor toolbar in SeoPageResource so what the operator
     * sees in the editor is what survives.
     */
    public const ALLOWED_HTML_TAGS = '<p><h2><h3><h4><strong><em><ul><ol><li><a><blockquote><br><img>';

    protected static function booted(): void
    {
        static::saving(function (SeoPage $page) {
            // Sanitize HTML body before persistence (D-4.5b-3).
            if ($page->body !== null) {
                $page->body = static::sanitizeHtml($page->body);
            }

            // Phase 4.5b-fix — populate searchable_text from
            // title + excerpt + category + tags + body (stripped).
            // Always recompute on save so a body edit is reflected
            // immediately in /api/v1/explore search relevance.
            $page->searchable_text = static::generateSearchableText($page);

            // Auto-stamp published_at on first publish so the
            // operator doesn't have to remember it.
            if ($page->isDirty('is_published')
                && $page->is_published === true
                && empty($page->published_at)) {
                $page->published_at = now();
            }
        });

        // Sitemap cache + Phase 4.5 / 4.5.1 explore-payload cache
        // invalidation. Phase 4.5.1 introduced per-category cache
        // keys ('explore-payload:all' + 'explore-payload:{slug}'),
        // so we walk the categories table on bust. Both
        // regenerate on the next request.
        $bust = function () {
            cache()->forget('sitemap_xml');
            cache()->forget('explore-payload');           // legacy key
            cache()->forget('explore-payload:all');       // 4.5.1 default
            SeoPageCategory::query()
                ->pluck('slug')
                ->each(fn ($slug) => cache()->forget('explore-payload:' . $slug));
        };
        static::saved($bust);
        static::deleted($bust);
    }

    /**
     * Strip tags outside the whitelist. Pure PHP, no extra
     * package — strip_tags is sufficient for this surface
     * (operator-only RichEditor input, not free-form public
     * comments where DOMPurify would be warranted).
     */
    public static function sanitizeHtml(string $html): string
    {
        return strip_tags($html, self::ALLOWED_HTML_TAGS);
    }

    /**
     * Phase 4.5b-fix — build the searchable_text payload.
     *
     * Concatenates the indexable surface (title, excerpt,
     * category, tags, body) into one normalized string. HTML
     * tags are stripped; whitespace collapses to single spaces.
     * Hard-capped at 30k chars so MySQL FULLTEXT stays inside
     * its 65535 byte budget after charset overhead.
     */
    public static function generateSearchableText(SeoPage $page): string
    {
        $bodyText = trim((string) preg_replace(
            '/\s+/',
            ' ',
            strip_tags((string) ($page->body ?? ''))
        ));

        $tags = is_array($page->tags) ? implode(' ', $page->tags) : '';

        $parts = array_filter([
            (string) ($page->title ?? ''),
            (string) ($page->excerpt ?? ''),
            (string) ($page->category ?? ''),
            $tags,
            $bodyText,
        ]);

        return mb_substr(implode(' ', $parts), 0, 30000);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Phase 4.5 — normalized category FK relationship.
     *
     * Distinct from the legacy `category` string column (kept
     * for backwards-compat). The controller and SeoPageView
     * prefer the FK relation when present and fall back to the
     * string when null.
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(SeoPageCategory::class, 'category_id');
    }

    /**
     * Phase 4.5 — operator-curated related pages (pivot).
     * Empty until admin populates; controller falls back to
     * getRelatedPages() when this is empty.
     */
    public function curatedRelated(): BelongsToMany
    {
        return $this->belongsToMany(
            SeoPage::class,
            'seo_page_related',
            'seo_page_id',
            'related_seo_page_id',
        )
            ->withPivot('display_order')
            ->orderBy('seo_page_related.display_order');
    }

    /**
     * Phase 4.5 — convenience accessor: prefer curated related,
     * fall back to category/tag heuristic.
     */
    public function relatedPages(int $limit = 4): Collection
    {
        $curated = $this->curatedRelated()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->limit($limit)
            ->get();

        if ($curated->isNotEmpty()) {
            return $curated;
        }

        return $this->getRelatedPages($limit);
    }

    /**
     * Phase 4.5 — read-time accessor. Computes from body word
     * count at 200 wpm when the operator hasn't supplied a
     * reading_time_minutes value.
     */
    public function getReadingTimeAttribute(): int
    {
        if ($this->reading_time_minutes) {
            return (int) $this->reading_time_minutes;
        }
        $words = str_word_count(strip_tags((string) $this->body));
        return max(1, (int) ceil($words / 200));
    }

    /* ───────────── Scopes ───────────── */

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->whereNotNull('published_at');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    public function scopeTrending(Builder $q): Builder
    {
        return $q->where('is_trending', true);
    }

    public function scopePinned(Builder $q): Builder
    {
        return $q->where('is_pinned', true);
    }

    /**
     * Up to $limit related published pages, ranked by:
     * (1) shared category and (2) shared tags. Excludes self.
     * Tiebreaker is most-recent published_at.
     */
    public function getRelatedPages(int $limit = 3): Collection
    {
        $query = SeoPage::query()
            ->where('id', '!=', $this->id)
            ->where('is_published', true)
            ->whereNotNull('published_at');

        $hasCategory = !empty($this->category);
        $hasTags     = is_array($this->tags) && !empty($this->tags);

        // Match on category OR shared tag. If neither dimension
        // exists on the current page, fall back to "most recent".
        if ($hasCategory || $hasTags) {
            $query->where(function ($q) use ($hasCategory, $hasTags) {
                if ($hasCategory) {
                    $q->where('category', $this->category);
                }
                if ($hasTags) {
                    foreach ($this->tags as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                }
            });
        }

        return $query
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Frontend route paths that MUST NOT be claimed by an SEO
     * page. The Filament form's slug validator rejects any of
     * these. Keep in sync with the RESERVED_SLUGS array in
     * src/pages/SeoPageView.tsx.
     *
     * `payment` is included to preserve the Phase 2.6a-fix
     * smoke-test invariant ("/payment routes to NotFound").
     */
    public static function reservedSlugs(): array
    {
        return [
            // Auth + account
            'login', 'logout', 'register', 'forgot-password',
            'cart', 'checkout', 'orders', 'profile', 'settings',
            // Existing customer routes
            'services', 'service-centers', 'category', 'coupons',
            'offers', 'about', 'contact', 'privacy', 'terms',
            'faq', 'sitemap', 'testimonials', 'gallery',
            'insurance', 'corporate', 'cms-preview',
            'booking-history', 'my-bookings', 'order',
            'booking-confirmation', 'not-found', 'payment',
            // Backend / system
            'admin', 'api', 'storage',
            // SEO subsystem itself
            'explore', 'home', 'index', 'main',
        ];
    }
}
