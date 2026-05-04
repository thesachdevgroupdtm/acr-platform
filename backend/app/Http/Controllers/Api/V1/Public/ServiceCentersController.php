<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ServiceCenterResource;
use App\Models\ServiceCenter;
use Illuminate\Http\JsonResponse;

/**
 * Phase 2.5a — public read endpoint for the checkout dropdown
 * (D-2.5a-2). No auth required.
 */
class ServiceCentersController extends Controller
{
    public function index(): JsonResponse
    {
        $centers = ServiceCenter::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'service_centers' => ServiceCenterResource::collection($centers),
        ]);
    }
}
