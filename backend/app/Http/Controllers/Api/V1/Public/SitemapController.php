<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCenter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5b — sitemap.xml generator.
 * Phase 4.5c — extended to include ServiceCenter rows (the 4 seeded
 * centers + any new ones the admin adds via /admin/service-centers).
 * Phase 4.5c sitemap-fix — route binding moved to /sitemap.xml at
 * the application root (routes/web.php) so search engines find it
 * at the conventional URL. Controller location unchanged.
 *
 * Generates a single Sitemap-protocol-0.9 document covering:
 *   - Static landing routes (home, services, service-centers,
 *     coupons, explore).
 *   - Every published SeoPage (Phase 4.5b).
 *   - Every active ServiceCategory (linked via /category/{slug}).
 *   - Every active Service (linked via /services/{cat}/{svc}).
 *   - Every active ServiceCenter (linked via /service-centers/{slug}).
 *
 * Each SEO-aware row honors `include_in_sitemap`: when the
 * operator unchecks that toggle, the URL is omitted entirely.
 *
 * Cached for 1 hour with the cache key `sitemap_xml`. Both
 * SeoPage and SeoMetadata model events bust this key on save +
 * delete (D-4.5b-9), so operator edits surface immediately.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap_xml', 3600, fn () => $this->generate());

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function generate(): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [];

        // Static / hard-coded routes
        $urls[] = $this->urlEntry($base . '/',                 '1.0', 'daily');
        $urls[] = $this->urlEntry($base . '/services',         '0.9', 'weekly');
        $urls[] = $this->urlEntry($base . '/service-centers',  '0.8', 'weekly');
        $urls[] = $this->urlEntry($base . '/coupons',          '0.7', 'weekly');
        $urls[] = $this->urlEntry($base . '/explore',          '0.7', 'weekly');

        // SEO Pages — chunked so a 10k-page seed doesn't OOM.
        SeoPage::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->with('seoMetadata')
            ->chunk(100, function ($pages) use (&$urls, $base) {
                foreach ($pages as $page) {
                    $seo = $page->seoMetadata;
                    if ($seo && ! $seo->include_in_sitemap) {
                        continue;
                    }
                    $urls[] = $this->urlEntry(
                        $base . '/' . $page->slug,
                        (string) ($seo?->priority ?? 0.5),
                        $seo?->changefreq ?? 'monthly',
                        $page->updated_at?->toAtomString()
                    );
                }
            });

        // Service categories
        ServiceCategory::query()
            ->where('is_active', true)
            ->with('seoMetadata')
            ->chunk(100, function ($cats) use (&$urls, $base) {
                foreach ($cats as $cat) {
                    $seo = $cat->seoMetadata;
                    if ($seo && ! $seo->include_in_sitemap) {
                        continue;
                    }
                    $urls[] = $this->urlEntry(
                        $base . '/category/' . $cat->slug,
                        (string) ($seo?->priority ?? 0.8),
                        $seo?->changefreq ?? 'monthly',
                        $cat->updated_at?->toAtomString()
                    );
                }
            });

        // Services
        Service::query()
            ->where('is_active', true)
            ->with(['seoMetadata', 'category'])
            ->chunk(100, function ($services) use (&$urls, $base) {
                foreach ($services as $svc) {
                    if (! $svc->category) {
                        continue;
                    }
                    $seo = $svc->seoMetadata;
                    if ($seo && ! $seo->include_in_sitemap) {
                        continue;
                    }
                    $urls[] = $this->urlEntry(
                        $base . '/services/' . $svc->category->slug . '/' . $svc->slug,
                        (string) ($seo?->priority ?? 0.7),
                        $seo?->changefreq ?? 'monthly',
                        $svc->updated_at?->toAtomString()
                    );
                }
            });

        // Phase 4.5c — service centers. URL pattern matches the
        // frontend route `/service-centers/{slug}` (the customer
        // page wiring landed in Phase 4.5c PART H). Inactive
        // centers are excluded so we don't expose deactivated
        // locations to search engines. Priority 0.7 — same tier
        // as a service detail (each center is a real-world place
        // worth its own indexable page).
        ServiceCenter::query()
            ->where('is_active', true)
            ->with('seoMetadata')
            ->chunk(100, function ($centers) use (&$urls, $base) {
                foreach ($centers as $center) {
                    $seo = $center->seoMetadata;
                    if ($seo && ! $seo->include_in_sitemap) {
                        continue;
                    }
                    $urls[] = $this->urlEntry(
                        $base . '/service-centers/' . $center->slug,
                        (string) ($seo?->priority ?? 0.7),
                        $seo?->changefreq ?? 'monthly',
                        $center->updated_at?->toAtomString()
                    );
                }
            });

        $body = implode("\n", $urls);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$body}
</urlset>
XML;
    }

    protected function urlEntry(
        string $loc,
        string $priority,
        string $changefreq,
        ?string $lastmod = null
    ): string {
        $loc        = htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $lastmodTag = $lastmod ? "\n        <lastmod>{$lastmod}</lastmod>" : '';

        return <<<XML
    <url>
        <loc>{$loc}</loc>{$lastmodTag}
        <changefreq>{$changefreq}</changefreq>
        <priority>{$priority}</priority>
    </url>
XML;
    }
}
