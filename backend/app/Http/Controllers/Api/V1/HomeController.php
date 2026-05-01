<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarBrandResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\CarBrand;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * GET /api/v1/home
     *
     * Returns the payload the frontend Home page needs in a single round-trip:
     * service categories, car brands, and the SEO defaults.
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
            'seo'                 => $this->seoDefault(),
        ]);
    }

    private function seoDefault(): array
    {
        return [
            'title'       => 'Auto Car Repair — Multi-Brand Service & Collision Repair',
            'description' => "India's fastest-growing self-owned multi-brand car service network.",
            'keywords'    => 'car service, car repair, collision repair, multi-brand, Delhi NCR',
            'canonical'   => null,
            'og'          => [
                'title'       => 'Auto Car Repair',
                'description' => 'Multi-brand car service & collision repair.',
                'type'        => 'website',
                'site_name'   => 'Auto Car Repair',
            ],
            'twitter'     => [
                'card' => 'summary_large_image',
            ],
        ];
    }
}
