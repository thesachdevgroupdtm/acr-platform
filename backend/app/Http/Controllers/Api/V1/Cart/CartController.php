<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\FuelType;
use App\Models\Service;
use App\Services\Cart\CartService;
use App\Services\Cart\NoPriceConfiguredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * /api/v1/cart/* (cart-session middleware) — Phase 2.3.
 *
 * Per /PHASE2_CONTRACT.md §5.3. The cart is resolved by middleware
 * and attached as `$request->attributes->get('cart')`. Controller
 * methods never reach back to the request to figure out who owns the
 * cart — middleware is the single decision point.
 *
 * Pricing is server-trusted: every write recomputes
 * unit_price_snapshot via CartService. Clients never set it.
 *
 * Coupon endpoints are 501 in this commit — coupons table lands in
 * 2.6 (see PHASE2_3_REPORT.md deviations).
 */
class CartController extends Controller
{
    public function __construct(private CartService $pricing)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cart($request)->load([
            'items.service',
            'items.brand',
            'items.carModel',
            'items.fuel',
        ]);
        return response()->json(['cart' => new CartResource($cart)]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind'              => ['required', 'string', 'in:service,package,product'],
            'ref_id'            => ['required', 'integer', 'min:1'],
            'quantity'          => ['sometimes', 'integer', 'min:1', 'max:99'],
            'vehicle'           => ['sometimes', 'array'],
            'vehicle.brand_id'  => ['sometimes', 'integer', 'exists:car_brands,id'],
            'vehicle.model_id'  => ['sometimes', 'integer', 'exists:car_models,id'],
            'vehicle.fuel_id'   => ['sometimes', 'integer', 'exists:fuel_types,id'],
            'meta'              => ['sometimes', 'array'],
        ]);

        if ($validated['kind'] !== 'service') {
            return response()->json(
                ['message' => 'Only service items supported (Phase 2.6)'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $service = Service::query()
            ->where('id', $validated['ref_id'])
            ->where('is_active', true)
            ->first();
        if (!$service) {
            return response()->json(['message' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        $vehicle  = $validated['vehicle'] ?? [];
        $brandId  = $vehicle['brand_id'] ?? null;
        $modelId  = $vehicle['model_id'] ?? null;
        $fuelId   = $vehicle['fuel_id']  ?? null;
        $quantity = $validated['quantity'] ?? 1;
        $meta     = $validated['meta'] ?? null;

        try {
            $price = $this->pricing->priceServiceItem($service, $brandId, $modelId, $fuelId);
        } catch (NoPriceConfiguredException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cart = $this->cart($request);

        $item = DB::transaction(function () use ($cart, $service, $brandId, $modelId, $fuelId, $quantity, $price, $meta) {
            // Same-cart dedup: identical (service_id, brand_id, model_id, fuel_id)
            // bumps quantity instead of creating a parallel row. Documented
            // in PHASE2_3_REPORT.md.
            $existing = $cart->items()
                ->where('service_id', $service->id)
                ->where('brand_id', $brandId)
                ->where('model_id', $modelId)
                ->where('fuel_id',  $fuelId)
                ->first();

            if ($existing) {
                $existing->quantity            = min(99, $existing->quantity + $quantity);
                $existing->unit_price_snapshot = $price;   // re-snapshot on re-add
                if ($meta !== null) $existing->meta = $meta;
                $existing->save();
                return $existing;
            }

            return $cart->items()->create([
                'service_id'          => $service->id,
                'brand_id'            => $brandId,
                'model_id'            => $modelId,
                'fuel_id'             => $fuelId,
                'quantity'            => $quantity,
                'unit_price_snapshot' => $price,
                'meta'                => $meta,
            ]);
        });

        unset($item);

        return $this->show($request);
    }

    public function updateItem(Request $request, CartItem $item): JsonResponse
    {
        $cart = $this->cart($request);
        if ($item->cart_id !== $cart->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'quantity'         => ['sometimes', 'integer', 'min:1', 'max:99'],
            'vehicle'          => ['sometimes', 'array'],
            'vehicle.brand_id' => ['sometimes', 'integer', 'exists:car_brands,id'],
            'vehicle.model_id' => ['sometimes', 'integer', 'exists:car_models,id'],
            'vehicle.fuel_id'  => ['sometimes', 'integer', 'exists:fuel_types,id'],
        ]);

        if (empty($validated)) {
            return response()->json(['message' => 'No fields to update'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($item, $validated) {
            if (array_key_exists('quantity', $validated)) {
                $item->quantity = $validated['quantity'];
            }

            if (array_key_exists('vehicle', $validated) && $item->service_id) {
                $vehicle = $validated['vehicle'];
                $item->brand_id = $vehicle['brand_id'] ?? null;
                $item->model_id = $vehicle['model_id'] ?? null;
                $item->fuel_id  = $vehicle['fuel_id']  ?? null;

                $service = Service::find($item->service_id);
                $item->unit_price_snapshot = $this->pricing->priceServiceItem(
                    $service,
                    $item->brand_id,
                    $item->model_id,
                    $item->fuel_id,
                );
            }

            $item->save();
        });

        return $this->show($request);
    }

    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $cart = $this->cart($request);
        if ($item->cart_id !== $cart->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $item->delete();

        return $this->show($request);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        return response()->json(
            ['message' => 'Not implemented yet (Phase 2.6 — coupons table not migrated)'],
            Response::HTTP_NOT_IMPLEMENTED
        );
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        return response()->json(
            ['message' => 'Not implemented yet (Phase 2.6 — coupons table not migrated)'],
            Response::HTTP_NOT_IMPLEMENTED
        );
    }

    private function cart(Request $request): Cart
    {
        $cart = $request->attributes->get('cart');
        if (!$cart instanceof Cart) {
            // Defensive — middleware should always have set this.
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Cart not resolved by middleware');
        }
        return $cart;
    }
}
