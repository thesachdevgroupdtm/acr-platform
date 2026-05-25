<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarBrandResource;
use App\Http\Resources\CarModelResource;
use App\Http\Resources\FuelTypeResource;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * GET /api/v1/vehicle/brands
     */
    public function brands(): JsonResponse
    {
        $brands = CarBrand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'brands'  => CarBrandResource::collection($brands),
        ]);
    }

    /**
     * GET /api/v1/vehicle/models?brand_id={id}
     */
    public function models(Request $request): JsonResponse
    {
        // Phase BS-3 backend perf pass — dropped the `exists:car_brands,id`
        // rule. It fired a redundant COUNT(*) on every call; the
        // subsequent `where('brand_id', X)` already returns an empty
        // result if X is bogus, so the response shape is preserved.
        $validated = $request->validate([
            'brand_id' => ['required', 'integer'],
        ]);

        $models = CarModel::query()
            ->where('brand_id', $validated['brand_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'models'  => CarModelResource::collection($models),
        ]);
    }

    /**
     * GET /api/v1/vehicle/fuels
     *
     * The fuel list is global; when model_id is passed, narrow to the
     * fuels that the chosen model actually supports per the
     * car_model_fuel_types pivot (seeded from the car-catalog import).
     * brand_id is accepted for forward-compat with the old contract
     * but model_id alone is enough for the filter to fire.
     */
    public function fuels(Request $request): JsonResponse
    {
        // Phase BS-3 backend perf pass — same `exists:` dedupe. Two
        // redundant COUNT(*) queries gone; the whereExists below
        // already filters correctly against the pivot for any
        // model_id, valid or not.
        $validated = $request->validate([
            'brand_id' => ['nullable', 'integer'],
            'model_id' => ['nullable', 'integer'],
        ]);

        $query = FuelType::query()->where('is_active', true);

        if (!empty($validated['model_id'])) {
            $modelId = (int) $validated['model_id'];
            $query->whereExists(function ($q) use ($modelId) {
                $q->select(\DB::raw(1))
                  ->from('car_model_fuel_types')
                  ->whereColumn('car_model_fuel_types.fuel_type_id', 'fuel_types.id')
                  ->where('car_model_fuel_types.car_model_id', $modelId);
            });
        }

        $fuels = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'fuels'   => FuelTypeResource::collection($fuels),
        ]);
    }
}
