<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sub-phase L1 — public read-only service endpoints. Lives under the
 * V1\Public namespace; the existing App\Http\Controllers\Api\V1\
 * ServiceController (which the frontend already consumes) is left
 * untouched.
 */
class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            });

        // Optional ?category=:slug filter — scopes to a single category.
        if ($categorySlug = $request->query('category')) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        $services = $query->orderBy('name')->get();

        return response()->json([
            'data' => ServiceResource::collection($services),
            'meta' => ['count' => $services->count()],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $service = Service::query()
            ->with('category')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            })
            ->first();

        if (! $service) {
            return response()->json([
                'error' => [
                    'code'    => 'service_not_found',
                    'message' => "No service found with slug '{$slug}'",
                ],
            ], 404);
        }

        return response()->json([
            'data' => new ServiceResource($service),
        ]);
    }
}
