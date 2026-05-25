<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarBrandResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\CarBrand;
use App\Models\ServiceCategory;
use App\Models\SiteSeoSettings;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * GET /api/v1/home
     *
     * Returns the payload the frontend Home page needs in a single round-trip:
     * service categories, car brands, and the SEO defaults.
     *
     * Phase 4.5c — `seo` key now matches the FLAT SeoFlatData shape
     * (mirrors HasSeoMetadata::getSeoData() / /api/v1/seo-pages/{slug}).
     * Dynamic values pull from SiteSeoSettings so admin overrides flow
     * through with no controller change.
     */
    public function index(): JsonResponse
    {
        // Eager-load the active sub-services per category in a single
        // additional query so the response is N+1-free. The frontend
        // home / sitemap / header mega-menu all read from this nested
        // payload instead of round-tripping /services/{slug} per
        // category. (Phase 1.6.)
        $categories = ServiceCategory::query()
            ->with(['services' => function ($q) {
                $q->where('is_active', true)->orderBy('id');
            }])
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $brands = CarBrand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success'             => true,
            'service_categories'  => ServiceCategoryResource::collection($categories),
            'car_brands'          => CarBrandResource::collection($brands),
            'car_models'          => [],
            'service_centers'     => [],
            'offer_slider'        => [],
            'tabular_offers'      => [],
            'service_packages'    => [],
            'featured_products'   => [],
            'faqs'                => [],
            'brand_logo_slider'   => [],
            'membership_package'  => [],
            'home_page_setting'   => null,
            'settings'            => [
                'site_name' => config('app.name'),
            ],
            'seo'                 => $this->homeSeoFromSiteDefaults(),
        ]);
    }

    /**
     * Phase 4.5c — flat SEO payload for the home page. Pulled from
     * SiteSeoSettings (admin-managed) so changes propagate without
     * any controller deploy. Title is hardcoded since the home page
     * doesn't have its own SeoPage record (it's the root route).
     *
     * @return array<string, mixed>
     */
    private function homeSeoFromSiteDefaults(): array
    {
        $defaults = SiteSeoSettings::current();

        return [
            'meta_title' => str_replace(
                '{{page_title}}',
                'Home',
                $defaults->default_meta_title_template
                    ?? 'ACR — Multi-Brand Car Service & Collision Repair'
            ),
            'meta_description' => $defaults->default_meta_description
                ?? "India's fastest-growing self-owned multi-brand car service network.",
            'meta_keywords'    => 'car service, car repair, collision repair, multi-brand, Delhi NCR',
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
            'schema_jsonld'    => $defaults->organization_jsonld
                ? json_encode($defaults->organization_jsonld, JSON_UNESCAPED_SLASHES)
                : null,
        ];
    }
}
