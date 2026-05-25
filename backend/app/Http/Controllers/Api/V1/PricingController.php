<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServicePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * POST /api/v1/pricing
     *
     * Body: { brand_id, model_id, fuel_type_id, service_id }
     * (or service_ids: int[] to look up many at once)
     *
     * Returns the matching price(s) from service_prices.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_id'     => ['required', 'integer', 'exists:car_brands,id'],
            'model_id'     => ['required', 'integer', 'exists:car_models,id'],
            'fuel_type_id' => ['required', 'integer', 'exists:fuel_types,id'],
            'service_id'   => ['nullable', 'integer', 'exists:services,id'],
            'service_ids'  => ['nullable', 'array'],
            'service_ids.*'=> ['integer', 'exists:services,id'],
        ]);

        $serviceIds = $validated['service_ids'] ?? [];
        if (!empty($validated['service_id'])) {
            $serviceIds[] = $validated['service_id'];
        }

        if (empty($serviceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Provide service_id or service_ids[].',
            ], 422);
        }

        $rows = ServicePrice::query()
            ->whereIn('service_id', $serviceIds)
            ->where('brand_id', $validated['brand_id'])
            ->where('model_id', $validated['model_id'])
            ->where('fuel_type_id', $validated['fuel_type_id'])
            ->get();

        $prices = $rows->map(fn (ServicePrice $p) => [
            'service_id' => $p->service_id,
            'price'      => (float) $p->price,
        ])->values();

        $total = (float) $rows->sum(fn (ServicePrice $p) => (float) $p->price);

        return response()->json([
            'success'        => true,
            'brand_id'       => (int) $validated['brand_id'],
            'model_id'       => (int) $validated['model_id'],
            'fuel_type_id'   => (int) $validated['fuel_type_id'],
            'requested_ids'  => array_values(array_unique($serviceIds)),
            'matched_prices' => $prices,
            'total'          => $total,
        ]);
    }
}
