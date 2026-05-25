<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FuelResource;
use App\Models\CarModel;
use App\Models\FuelType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sub-phase L1 — public read-only fuel-type list. Same visibility
 * rule as the rest of the public catalog.
 *
 * Optional `?model_id=N` narrows the list to only fuel-types that
 * the given car_model supports (via the car_model_fuel_types pivot
 * seeded from the car_list import). Without the param the full
 * catalog is returned for backwards compatibility.
 */
class FuelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $modelId = $request->integer('model_id');

        $query = FuelType::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            });

        if ($modelId > 0) {
            $query->whereExists(function ($sub) use ($modelId) {
                $sub->select(\DB::raw(1))
                    ->from('car_model_fuel_types')
                    ->whereColumn('car_model_fuel_types.fuel_type_id', 'fuel_types.id')
                    ->where('car_model_fuel_types.car_model_id', $modelId);
            });
        }

        $fuels = $query->orderBy('name')->get();

        return response()->json([
            'data' => FuelResource::collection($fuels),
            'meta' => ['count' => $fuels->count(), 'model_id' => $modelId ?: null],
        ]);
    }

    /**
     * MODEL_FUEL_SCOPE — fuels valid for ONE car model, resolved by slug.
     *
     * Returns only the fuel-types that have at least one pricing row
     * (service_prices) for the given model — i.e. the fuels we can
     * actually quote and book for that car. Same {data, meta} shape and
     * FuelResource mapping as index() so the frontend adapter is reused.
     *
     * Source = pricing data (service_prices.model_id + fuel_type_id),
     * NOT the car_model_fuel_types pivot index() uses: a fuel only belongs
     * in the booking flow if it is priceable, so the catalog of bookable
     * fuels is authoritative here. (Audit: the two sources agree for all
     * 314 models, so this is also consistent with the pivot.)
     *
     * Fallback (D-FUEL-4): if the model has NO pricing rows at all, return
     * the full active-fuel catalog so the booking flow never dead-ends.
     * `meta.fallback` flags when this path is taken. (Audit: 0/314 models
     * currently hit it — pure safety net.)
     */
    public function forModel(string $slug): JsonResponse
    {
        $model = CarModel::where('slug', $slug)->firstOrFail();

        $priced = $this->visibleFuels()
            ->whereExists(function ($sub) use ($model) {
                $sub->select(DB::raw(1))
                    ->from('service_prices')
                    ->whereColumn('service_prices.fuel_type_id', 'fuel_types.id')
                    ->where('service_prices.model_id', $model->id);
            })
            ->orderBy('name')
            ->get();

        $fallback = $priced->isEmpty();
        $fuels = $fallback
            ? $this->visibleFuels()->orderBy('name')->get()
            : $priced;

        return response()->json([
            'data' => FuelResource::collection($fuels),
            'meta' => [
                'count' => $fuels->count(),
                'model_id' => $model->id,
                'model_slug' => $model->slug,
                'fallback' => $fallback,
            ],
        ]);
    }

    /**
     * Fresh query of publicly-visible fuel-types — same rule index() uses.
     */
    private function visibleFuels()
    {
        return FuelType::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('include_in_sitemap', true)
                  ->orWhere('is_auto_created', false);
            });
    }
}
