<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;

/**
 * Sub-phase L1 — public read-only category endpoints. Categories are
 * sorted by `position` first then `name` so operators can pin
 * marketing-critical categories to the top of the list.
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
            'meta' => ['count' => $categories->count()],
        ]);
    }

    public function services(string $slug): JsonResponse
    {
        $category = ServiceCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->first();

        if (! $category) {
            return response()->json([
                'error' => [
                    'code'    => 'category_not_found',
                    'message' => "No category found with slug '{$slug}'",
                ],
            ], 404);
        }

        $services = Service::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => ServiceResource::collection($services),
            'meta' => ['count' => $services->count()],
        ]);
    }
}
