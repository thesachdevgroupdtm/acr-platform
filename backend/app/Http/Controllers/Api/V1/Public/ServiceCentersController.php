<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ServiceCenterResource;
use App\Models\ServiceCenter;
use App\Models\SiteSeoSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 2.5a — public read endpoint for the checkout dropdown
 * (D-2.5a-2). No auth required.
 *
 * Phase 4.5c — extended with:
 *   - eager-loaded seoMetadata so the list payload is N+1-free
 *     when consumers attach per-item flat SEO
 *   - new show($slug) method returning a single center + its
 *     cascade-resolved flat SEO payload (powers SeoHead on the
 *     forthcoming /service-centers/{slug} customer page)
 *   - flat list-level seo synthesised from SiteSeoSettings
 */
class ServiceCentersController extends Controller
{
    /**
     * B5-partial — 1-hour public-list cache (D-B5-3). Invalidated
     * automatically when any ServiceCenter row is saved or deleted,
     * via the model's static booted() hook (see ServiceCenter@booted).
     */
    public const LIST_CACHE_KEY = 'service-centers:v1:list';
    public const LIST_CACHE_TTL = 3600;

    public function index(): JsonResponse
    {
        $payload = Cache::remember(self::LIST_CACHE_KEY, self::LIST_CACHE_TTL, function () {
            $centers = ServiceCenter::query()
                ->with('seoMetadata') // Phase 4.5c — N+1-free list seo.
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return [
                'service_centers' => ServiceCenterResource::collection($centers)->resolve(),
                'seo'             => $this->listSeoFromDefaults(),
            ];
        });

        return response()->json($payload);
    }

    /**
     * GET /api/v1/service-centers/{slug}
     *
     * Phase 4.5c — single-center detail with the cascade-resolved
     * flat SEO payload. Returns 404 when the slug doesn't match
     * an active center.
     */
    public function show(string $slug): JsonResponse
    {
        $center = ServiceCenter::query()
            ->with('seoMetadata')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $center) {
            return response()->json([
                'success' => false,
                'message' => "Service center '{$slug}' not found.",
            ], 404);
        }

        return response()->json([
            'success'        => true,
            'service_center' => new ServiceCenterResource($center),
            'seo'            => $center->getSeoData(),
        ]);
    }

    /**
     * Phase 4.5c — flat seo for /service-centers (the list page —
     * no SeoPage record). Mirrors the shape used by Home / Services.
     *
     * @return array<string, mixed>
     */
    private function listSeoFromDefaults(): array
    {
        $defaults = SiteSeoSettings::current();
        return [
            'meta_title' => str_replace(
                '{{page_title}}',
                'Service Centers',
                $defaults->default_meta_title_template
                    ?? 'Service Centers | ACR'
            ),
            'meta_description' => $defaults->default_meta_description
                ?? 'Find your nearest ACR service center.',
            'meta_keywords'    => 'service center, car service, ACR locations, Delhi NCR',
            'canonical_url'    => null,
            'robots_meta'      => $defaults->default_robots_meta ?? 'index,follow',
            'og_title'         => null,
            'og_description'   => null,
            'og_image'         => $defaults->default_og_image,
            'og_type'          => 'website',
            'twitter_card'     => $defaults->default_twitter_card ?? 'summary_large_image',
            'twitter_title'    => null,
            'twitter_description' => null,
            'twitter_image'    => null,
            'schema_jsonld'    => null,
        ];
    }
}
