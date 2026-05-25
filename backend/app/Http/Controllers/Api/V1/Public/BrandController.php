<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\ModelResource;
use App\Models\CarBrand;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;

/**
 * Sub-phase L1 — public read-only brand endpoints.
 *
 * Visibility rule (D-L1-4): active rows where include_in_sitemap=true
 * OR is_auto_created=false. That hides auto-bootstrap entities created
 * by Phase 4.3.5's AutoBootstrapResolver until an operator marks them
 * reviewed and SEO-enriched (which flips include_in_sitemap=true).
 */
class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = CarBrand::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => BrandResource::collection($brands),
            'meta' => ['count' => $brands->count()],
        ]);
    }

    public function models(string $slug): JsonResponse
    {
        $brand = CarBrand::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->first();

        if (! $brand) {
            return $this->notFound('brand_not_found', "No brand found with slug '{$slug}'");
        }

        $models = CarModel::query()
            ->where('brand_id', $brand->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => ModelResource::collection($models),
            'meta' => ['count' => $models->count()],
        ]);
    }

    private function notFound(string $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message],
        ], 404);
    }
}
