<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 4.5.3 — public master-data lookups for the explore-sidebar
 * lead form. All three endpoints are GET, throttled with
 * `public-read`, and cached for 1 hour (master data changes rarely).
 *
 * Response envelope is `{ data: [...] }` so the frontend hooks can
 * read `r.data.data` consistently with other JsonResource calls.
 */
class LookupController extends Controller
{
    public function brands(): JsonResponse
    {
        $rows = Cache::remember('lookups:brands', 3600, function () {
            return CarBrand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'slug', 'name']);
        });

        return response()->json(['data' => $rows]);
    }

    public function models(Request $request): JsonResponse
    {
        $request->validate([
            'brand_id' => ['required', 'integer', 'exists:car_brands,id'],
        ]);

        $brandId  = (int) $request->query('brand_id');
        $cacheKey = "lookups:models:brand:{$brandId}";

        $rows = Cache::remember($cacheKey, 3600, function () use ($brandId) {
            return CarModel::query()
                ->where('brand_id', $brandId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'brand_id']);
        });

        return response()->json(['data' => $rows]);
    }

    public function services(): JsonResponse
    {
        $rows = Cache::remember('lookups:services', 3600, function () {
            return Service::query()
                ->with('category:id,slug,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'category_id'])
                ->map(function (Service $s) {
                    return [
                        'id'       => $s->id,
                        'slug'     => $s->slug,
                        'name'     => $s->name,
                        'category' => $s->category
                            ? [
                                'id'   => $s->category->id,
                                'slug' => $s->category->slug,
                                'name' => $s->category->name,
                            ]
                            : null,
                    ];
                })
                ->values();
        });

        return response()->json(['data' => $rows]);
    }
}
