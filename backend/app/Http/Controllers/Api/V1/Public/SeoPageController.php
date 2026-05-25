<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SeoPageCardResource;
use App\Models\SeoPage;
use App\Models\SeoPageCategory;
use App\Models\UrlRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4.5b — customer-facing SEO page endpoints.
 *
 * Three actions:
 *   - show($slug): single SEO page payload + resolved SEO + related.
 *     Checks url_redirects FIRST so the frontend can client-navigate
 *     to the new path with replace:true (preserves SPA history).
 *   - explore(): paginated published-page list with category / tag /
 *     search filters.
 *   - categories(): distinct list for the /explore filter dropdown.
 */
class SeoPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        // Active redirect wins. The frontend reads `redirect` and
        // calls navigate(to, {replace:true}) — keeps the address bar
        // correct without a hard browser-level 301.
        $redirect = UrlRedirect::findActiveFor('/' . $slug);
        if ($redirect) {
            return response()->json([
                'redirect' => [
                    'to'     => $redirect->to_path,
                    'status' => $redirect->status_code,
                ],
            ]);
        }

        $page = SeoPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->with(['seoMetadata'])
            ->first();

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $seoData = $page->getSeoData();
        $related = $page->getRelatedPages(3);

        return response()->json([
            'page' => [
                'id'        => $page->id,
                'slug'      => $page->slug,
                'title'     => $page->title,
                'excerpt'   => $page->excerpt,
                'body'      => $page->body,
                'category'  => $page->category,
                'tags'      => $page->tags ?? [],
                'layout'    => $page->layout,
                'cta'       => [
                    'title'       => $page->cta_title,
                    'button_text' => $page->cta_button_text,
                    'button_url'  => $page->cta_button_url,
                ],
                'published_at' => $page->published_at?->toIso8601String(),
            ],
            'seo'           => $seoData,
            'related_pages' => $related->map(fn (SeoPage $p) => [
                'slug'     => $p->slug,
                'title'    => $p->title,
                'excerpt'  => $p->excerpt,
                'category' => $p->category,
            ])->all(),
            'redirect' => null,
        ]);
    }

    public function explore(Request $request): JsonResponse
    {
        $query = SeoPage::query()
            ->where('is_published', true)
            ->whereNotNull('published_at');

        // Phase 4.5b-polish — featured-only filter for the
        // /explore Hero + Trending sections.
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $tags = $request->query('tags');
        if ($tags) {
            $tags = is_array($tags) ? $tags : [$tags];
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Phase 4.5b-fix — relevance-ranked search.
        // Match against title, category, and the searchable_text
        // payload (which folds in excerpt + tags + body). Score:
        //   4  title hit
        //   3  exact tag hit (MySQL JSON_CONTAINS; SQLite skips)
        //   2  category hit
        //   1  body / excerpt / tag-text hit (via searchable_text)
        // Tie-break by published_at desc.
        $hasSearch = (bool) ($search = $request->query('search'));
        if ($hasSearch) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

            $query->where(function ($q) use ($like, $search) {
                $q->where('title', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('searchable_text', 'like', $like)
                    ->orWhereJsonContains('tags', $search);
            });

            if (DB::getDriverName() === 'mysql') {
                $query->orderByRaw(
                    "CASE
                        WHEN title LIKE ? THEN 4
                        WHEN JSON_CONTAINS(tags, ?) THEN 3
                        WHEN category LIKE ? THEN 2
                        WHEN searchable_text LIKE ? THEN 1
                        ELSE 0
                    END DESC, published_at DESC",
                    [$like, json_encode($search), $like, $like]
                );
            } else {
                // SQLite (test env) — skip JSON_CONTAINS scoring;
                // the test fixtures get distinct relevance from
                // title + category + searchable_text alone.
                $query->orderByRaw(
                    "CASE
                        WHEN title LIKE ? THEN 4
                        WHEN category LIKE ? THEN 2
                        WHEN searchable_text LIKE ? THEN 1
                        ELSE 0
                    END DESC, published_at DESC",
                    [$like, $like, $like]
                );
            }
        } else {
            // Phase 4.5b-polish — sort param drives the editorial
            // sections on /explore. Relevance scoring above wins
            // when a search is present; the sort param only kicks
            // in for non-search listings.
            $sort = (string) $request->query('sort', 'newest');
            switch ($sort) {
                case 'popular':
                    $query->orderByDesc('view_count')->orderByDesc('published_at');
                    break;
                case 'trending':
                    // "Trending" = recent + viewed. With view_count
                    // as a placeholder (always 0 until Phase 6),
                    // this currently degrades to "newest" — by
                    // design.
                    $query->orderByDesc('published_at')->orderByDesc('view_count');
                    break;
                case 'newest':
                default:
                    $query->orderByDesc('published_at');
            }
        }

        $perPage = min((int) $request->query('per_page', 20), 50);
        $pages   = $query->with('seoMetadata')->paginate($perPage);

        return response()->json([
            'data' => collect($pages->items())->map(fn (SeoPage $p) => [
                'id'           => $p->id,
                'slug'         => $p->slug,
                'title'        => $p->title,
                'excerpt'      => $p->excerpt,
                'category'     => $p->category,
                'tags'         => $p->tags ?? [],
                // Phase 4.5b-polish — surface og_image so the
                // editorial cards (Hero, Feature, Horizontal,
                // Compact) have an image to render. Cascade fall-
                // through to site default happens server-side.
                'og_image'     => $p->seoMetadata?->og_image,
                'is_featured'  => (bool) $p->is_featured,
                'published_at' => $p->published_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page'    => $pages->lastPage(),
                'total'        => $pages->total(),
                'per_page'     => $pages->perPage(),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = SeoPage::query()
            ->where('is_published', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json(['categories' => $categories]);
    }

    /**
     * Phase 4.5 — structured Explore payload.
     *
     * Single round-trip for the editorial /explore page. Cached
     * 60s under key 'explore-payload' (busted by SeoPage save/
     * delete events — see PART J task).
     *
     * Query budget: 1 (categories) + 1 (hero) + 1 (trending) +
     * 2 (rails) + N (per-category, where N ≤ 6) ≈ 5-11 queries
     * total, all narrow scopes. Phase 6 can collapse the per-
     * category loop with a windowed query if needed.
     */
    public function payload(Request $request): JsonResponse
    {
        $category = trim((string) $request->query('category', ''));
        $category = $category !== '' ? $category : null;

        $cacheKey = 'explore-payload:' . ($category ?? 'all');

        $payload = Cache::remember($cacheKey, 60, function () use ($category) {
            return $this->buildPayload($category);
        });

        return response()->json($payload);
    }

    /**
     * Phase 4.5 — view-tracking endpoint.
     *
     * POST /api/v1/seo-pages/{slug}/track-view. Idempotency:
     * one increment per (IP + slug) per 10 minutes via Cache
     * fingerprint. Returns 200 either way; prevents bots from
     * inflating counts but doesn't punish legitimate refresh.
     */
    public function trackView(Request $request, string $slug): JsonResponse
    {
        $page = SeoPage::query()
            ->where('slug', $slug)
            ->published()
            ->first();

        if (! $page) {
            return response()->json(['ok' => false], 404);
        }

        $fingerprint = sprintf('view-track:%s:%s', $request->ip() ?: 'unknown', $slug);
        if (Cache::has($fingerprint)) {
            // Already counted this viewer in the last 10 min.
            return response()->json([
                'ok'         => true,
                'counted'    => false,
                'view_count' => (int) $page->view_count,
            ]);
        }

        Cache::put($fingerprint, true, now()->addMinutes(10));

        // Increment without firing model events (avoid sitemap
        // cache bust + searchable_text recompute on every read).
        $page->newQuery()
            ->where('id', $page->id)
            ->update([
                'view_count'     => DB::raw('view_count + 1'),
                'last_viewed_at' => now(),
            ]);

        return response()->json([
            'ok'         => true,
            'counted'    => true,
            'view_count' => (int) $page->view_count + 1,
        ]);
    }

    /* ─────────── Payload builder ─────────── */

    /**
     * Phase 4.5.1 — accepts optional `?category={slug}` filter.
     * When set, scopes trending_grid + categories[].items[] +
     * rails to that category. Hero falls back to top-N in
     * category, or pinned set when category data is sparse.
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(?string $categorySlug = null): array
    {
        $categoryRow = $categorySlug
            ? SeoPageCategory::query()->where('slug', $categorySlug)->first()
            : null;
        $catId = $categoryRow?->id;

        // Local helper: append a category_id WHERE when filtering.
        $applyCat = function ($q) use ($catId) {
            if ($catId) $q->where('category_id', $catId);
            return $q;
        };

        // 1. Hero — pinned ordered by hero_priority. When a
        // category filter is active, prefer top-4 in that
        // category (by view_count); fall back to global pinned
        // when category has < 4 candidates.
        if ($catId) {
            $hero = SeoPage::query()
                ->published()
                ->where('category_id', $catId)
                ->with('categoryRelation')
                ->orderByDesc('view_count')
                ->orderByDesc('published_at')
                ->limit(4)
                ->get();
        } else {
            $hero = SeoPage::query()
                ->published()
                ->pinned()
                ->with('categoryRelation')
                ->orderBy('hero_priority')
                ->limit(5)
                ->get();
        }

        // 2. Trending grid — exactly 8 cards (or fewer in-cat).
        $trending = $applyCat(
            SeoPage::query()
                ->published()
                ->when(!$catId, fn ($q) => $q->trending())
                ->with('categoryRelation')
                ->orderByDesc('view_count')
                ->orderByDesc('published_at')
                ->limit(8)
        )->get();

        // 3. Categories — when filter is active, render only
        // that category block (operator focus). Else top 6 active.
        $categoryQuery = SeoPageCategory::query()->active()->ordered();
        if ($catId) {
            $categoryQuery->where('id', $catId);
        } else {
            $categoryQuery->limit(6);
        }
        $categoryRows = $categoryQuery->get();

        $categories = $categoryRows->map(function (SeoPageCategory $cat) {
            $items = SeoPage::query()
                ->published()
                ->where('category_id', $cat->id)
                ->with('categoryRelation')
                ->orderByDesc('view_count')
                ->orderByDesc('published_at')
                ->limit(7)
                ->get();

            if ($items->isEmpty()) {
                return null;
            }

            return [
                'id'        => $cat->id,
                'slug'      => $cat->slug,
                'name'      => $cat->name,
                'icon_name' => $cat->icon_name,
                'featured'  => SeoPageCardResource::make($items->first())->resolve(),
                'items'     => SeoPageCardResource::collection($items->slice(1)->values())->resolve(),
            ];
        })->filter()->values();

        // 4. Rails — 12 each, also category-scoped when filter is on.
        $trendingSearches = $applyCat(
            SeoPage::query()
                ->published()
                ->with('categoryRelation')
                ->orderByDesc('view_count')
                ->orderByDesc('published_at')
                ->limit(12)
        )->get();

        $mostReadWeek = $applyCat(
            SeoPage::query()
                ->published()
                ->where(function ($q) {
                    $q->where('last_viewed_at', '>=', now()->subDays(7))
                        ->orWhereNull('last_viewed_at');
                })
                ->with('categoryRelation')
                ->orderByDesc('view_count')
                ->orderByDesc('published_at')
                ->limit(12)
        )->get();

        $totalPages = $applyCat(SeoPage::query()->published())->count();

        return [
            'hero'          => SeoPageCardResource::collection($hero)->resolve(),
            'trending_grid' => SeoPageCardResource::collection($trending)->resolve(),
            'categories'    => $categories->all(),
            'rails'         => [
                'trending_searches' => SeoPageCardResource::collection($trendingSearches)->resolve(),
                'most_read_week'    => SeoPageCardResource::collection($mostReadWeek)->resolve(),
            ],
            'meta' => [
                'total_pages'     => $totalPages,
                'last_updated_at' => now()->toIso8601String(),
            ],
        ];
    }
}
