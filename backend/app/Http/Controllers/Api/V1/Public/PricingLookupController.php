<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sub-phase L1 — public read-only price lookup.
 *
 * Resolves the (brand, model, fuel, service) → price row from the
 * service_prices table. Each slug failure returns a 404 with a
 * specific code so the frontend can render meaningful errors
 * ("brand not found" vs "price not configured for this combination").
 *
 * Named PricingLookupController (not PricingController) so it
 * doesn't collide with the existing
 * `App\Http\Controllers\Api\V1\PricingController` (POST /pricing
 * quote endpoint) sitting one namespace level up.
 */
class PricingLookupController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'brand_slug'   => 'required|string',
                'model_slug'   => 'required|string',
                'fuel_slug'    => 'required|string',
                'service_slug' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => [
                    'code'    => 'validation_failed',
                    'message' => 'brand_slug, model_slug, fuel_slug and service_slug are all required',
                    'fields'  => $e->errors(),
                ],
            ], 422);
        }

        $brand = $this->findActive(CarBrand::class, $validated['brand_slug']);
        if (! $brand) {
            return $this->notFound('brand_not_found', "No brand found with slug '{$validated['brand_slug']}'");
        }

        $model = CarModel::query()
            ->where('brand_id', $brand->id)
            ->where('slug', $validated['model_slug'])
            ->where('is_active', true)
            ->first();
        if (! $model) {
            return $this->notFound(
                'model_not_found',
                "No model '{$validated['model_slug']}' found under brand '{$validated['brand_slug']}'",
            );
        }

        $fuel = $this->findActive(FuelType::class, $validated['fuel_slug']);
        if (! $fuel) {
            return $this->notFound('fuel_not_found', "No fuel type found with slug '{$validated['fuel_slug']}'");
        }

        $service = $this->findActive(Service::class, $validated['service_slug']);
        if (! $service) {
            return $this->notFound('service_not_found', "No service found with slug '{$validated['service_slug']}'");
        }

        $priceRow = DB::table('service_prices')
            ->where('service_id', $service->id)
            ->where('brand_id',    $brand->id)
            ->where('model_id',    $model->id)
            ->where('fuel_type_id', $fuel->id)
            ->first();

        if (! $priceRow) {
            return $this->notFound(
                'price_not_available',
                'Price not available for this combination',
            );
        }

        $estimatedTime = ! empty($service->time_takes)
            ? trim(($service->time_takes ?? '') . ' ' . ($service->time_unit ?? ''))
            : null;

        return response()->json([
            'data' => [
                'price'          => (float) $priceRow->price,
                'currency'       => 'INR',
                'estimated_time' => $estimatedTime,
                'service'        => ['slug' => $service->slug, 'name' => $service->name],
                'vehicle'        => [
                    'brand' => ['slug' => $brand->slug, 'name' => $brand->name],
                    'model' => ['slug' => $model->slug, 'name' => $model->name],
                    'fuel'  => ['slug' => $fuel->slug,  'name' => $fuel->name],
                ],
            ],
        ]);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function findActive(string $modelClass, string $slug): mixed
    {
        return $modelClass::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    private function notFound(string $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => ['code' => $code, 'message' => $message],
        ], 404);
    }
}
