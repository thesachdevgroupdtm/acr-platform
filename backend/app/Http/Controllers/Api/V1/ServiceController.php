<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarBrandResource;
use App\Http\Resources\CarModelResource;
use App\Http\Resources\FuelTypeResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Http\Resources\ServiceResource;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * GET /api/v1/services
     *
     * Returns the list of active service categories.
     * Optional vehicle context (brand_id, model_id, fuel_id) narrows the
     * `available_category_ids` to those that have priced services.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_id'  => ['nullable', 'integer', 'exists:car_brands,id'],
            'model_id'  => ['nullable', 'integer', 'exists:car_models,id'],
            'fuel_id'   => ['nullable', 'integer', 'exists:fuel_types,id'],
        ]);

        // Same nested-services eager-load as HomeController@index — the
        // /services list page consumes the same shape. (Phase 1.6.)
        $categories = ServiceCategory::query()
            ->with(['services' => function ($q) {
                $q->where('is_active', true)->orderBy('id');
            }])
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $availableIds = [];
        $brand        = null;
        $model        = null;
        $fuel         = null;

        if (!empty($validated['brand_id']) && !empty($validated['model_id']) && !empty($validated['fuel_id'])) {
            $brand = CarBrand::find($validated['brand_id']);
            $model = CarModel::find($validated['model_id']);
            $fuel  = FuelType::find($validated['fuel_id']);

            $availableIds = ServicePrice::query()
                ->where('brand_id', $brand->id)
                ->where('model_id', $model->id)
                ->where('fuel_type_id', $fuel->id)
                ->join('services', 'services.id', '=', 'service_prices.service_id')
                ->pluck('services.category_id')
                ->unique()
                ->values()
                ->all();

            // Phase 2.6a — bulk-resolve per-service prices and stash them
            // on each Service instance via the transient
            // `resolvedVehiclePrice` property. SubServiceResource reads
            // it and emits `vehicle_price` + `effective_price`. Replaces
            // the frontend's parallel POST /pricing call on this page.
            $allServiceIds = $categories->flatMap(fn ($c) => $c->services->pluck('id'))->all();

            $priceMap = ServicePrice::query()
                ->whereIn('service_id', $allServiceIds)
                ->where('brand_id', $brand->id)
                ->where('model_id', $model->id)
                ->where('fuel_type_id', $fuel->id)
                ->pluck('price', 'service_id')
                ->all();

            foreach ($categories as $cat) {
                foreach ($cat->services as $service) {
                    $service->resolvedVehiclePrice = array_key_exists($service->id, $priceMap)
                        ? (float) $priceMap[$service->id]
                        : null;
                }
            }
        }

        return response()->json([
            'success'                  => true,
            'categories'               => ServiceCategoryResource::collection($categories),
            'available_category_ids'   => $availableIds,
            'brand'                    => $brand ? new CarBrandResource($brand) : null,
            'model'                    => $model ? new CarModelResource($model) : null,
            'fuel'                     => $fuel ? new FuelTypeResource($fuel)  : null,
            'seo'                      => [
                'title'       => 'Our Services — Auto Car Repair',
                'description' => 'Browse our comprehensive list of multi-brand car services.',
            ],
        ]);
    }

    /**
     * GET /api/v1/services/{slug}
     *
     * Category detail with sub-services. When brand/model/fuel slugs are
     * supplied, attaches per-service `price` from service_prices.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'fuel'  => ['nullable', 'string'],
        ]);

        $category = ServiceCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => "Category '{$slug}' not found.",
            ], 404);
        }

        $services = Service::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $brand = !empty($validated['brand'])
            ? CarBrand::where('slug', $validated['brand'])->first()
            : null;
        $model = !empty($validated['model'])
            ? CarModel::where('slug', $validated['model'])->first()
            : null;
        $fuel  = !empty($validated['fuel'])
            ? FuelType::where('slug', $validated['fuel'])->first()
            : null;

        $priceMap = [];
        $priceShow = 0;
        if ($brand && $model && $fuel) {
            $priceMap = ServicePrice::query()
                ->whereIn('service_id', $services->pluck('id'))
                ->where('brand_id', $brand->id)
                ->where('model_id', $model->id)
                ->where('fuel_type_id', $fuel->id)
                ->pluck('price', 'service_id')
                ->all();
            $priceShow = !empty($priceMap) ? 1 : 0;
        }

        $servicesPayload = $services->map(function (Service $s) use ($priceMap) {
            $resource = new ServiceResource($s);
            if (array_key_exists($s->id, $priceMap)) {
                $resource->withVehiclePrice(['price' => (float) $priceMap[$s->id]]);
            }
            return $resource;
        });

        return response()->json([
            'success'    => true,
            'category'   => new ServiceCategoryResource($category),
            'services'   => $servicesPayload,
            'price_show' => $priceShow,
            'price_list' => null,
            'brand'      => $brand ? new CarBrandResource($brand) : null,
            'model'      => $model ? new CarModelResource($model) : null,
            'fuel'       => $fuel ? new FuelTypeResource($fuel)   : null,
            'faqs'       => [],
            'faq_contents' => null,
            'seo'        => [
                'title'       => $category->name . ' — Auto Car Repair',
                'description' => $category->description,
            ],
        ]);
    }

    /**
     * GET /api/v1/services/{categorySlug}/{serviceSlug}
     */
    public function detail(Request $request, string $categorySlug, string $serviceSlug): JsonResponse
    {
        $validated = $request->validate([
            'brand_id'  => ['nullable', 'integer', 'exists:car_brands,id'],
            'model_id'  => ['nullable', 'integer', 'exists:car_models,id'],
            'fuel_id'   => ['nullable', 'integer', 'exists:fuel_types,id'],
        ]);

        $category = ServiceCategory::where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => "Category '{$categorySlug}' not found.",
            ], 404);
        }

        $service = Service::query()
            ->where('category_id', $category->id)
            ->where('slug', $serviceSlug)
            ->where('is_active', true)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => "Service '{$serviceSlug}' not found in '{$categorySlug}'.",
            ], 404);
        }

        $vehiclePrice = null;
        $brand = null;
        $model = null;
        $fuel  = null;
        $priceShow = 0;

        if (!empty($validated['brand_id']) && !empty($validated['model_id']) && !empty($validated['fuel_id'])) {
            $brand = CarBrand::find($validated['brand_id']);
            $model = CarModel::find($validated['model_id']);
            $fuel  = FuelType::find($validated['fuel_id']);

            $price = ServicePrice::query()
                ->where('service_id', $service->id)
                ->where('brand_id', $brand->id)
                ->where('model_id', $model->id)
                ->where('fuel_type_id', $fuel->id)
                ->first();

            if ($price) {
                $vehiclePrice = (float) $price->price;
                $priceShow = 1;
            }
        }

        $serviceResource = new ServiceResource($service);
        if ($vehiclePrice !== null) {
            $serviceResource->withVehiclePrice(['price' => $vehiclePrice]);
        }

        $related = Service::query()
            ->where('category_id', $category->id)
            ->where('id', '!=', $service->id)
            ->where('is_active', true)
            ->limit(6)
            ->get();

        return response()->json([
            'success'           => true,
            'service'           => $serviceResource,
            'category'          => new ServiceCategoryResource($category),
            'related'           => ServiceResource::collection($related),
            'price_show'        => $priceShow,
            'vehicle_price'     => $vehiclePrice,
            'vehicle_package_id'=> null,
            'brand'             => $brand ? new CarBrandResource($brand) : null,
            'model'             => $model ? new CarModelResource($model) : null,
            'fuel'              => $fuel ? new FuelTypeResource($fuel)   : null,
            'seo'               => [
                'title'       => $service->name . ' — ' . $category->name,
                'description' => $service->description,
            ],
        ]);
    }
}
