<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2.5a — user-facing order endpoints per /PHASE2_CONTRACT.md §5.4.
 *
 * Ownership rule: a request that isn't from the order's user gets a
 * 404, NOT a 403. Phase 2.1 contract §6.5 — never leak the existence
 * of a record the caller doesn't own.
 */
class OrderController extends Controller
{
    public function __construct(private CheckoutService $checkout)
    {
    }

    /**
     * GET /api/v1/user/orders
     * Paginated, newest-first. Optional ?status= filter.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['nullable', 'string', 'in:pending,confirmed,in_service,completed,cancelled'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = $validated['per_page'] ?? 10;

        $q = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.service', 'items.brand', 'items.carModel', 'items.fuel', 'serviceCenter', 'payments', 'coupon'])
            ->orderByDesc('id');

        if (!empty($validated['status'])) {
            $q->where('status', $validated['status']);
        }

        $paginated = $q->paginate($perPage);

        return response()->json([
            'orders'     => OrderResource::collection($paginated),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/user/orders/{order}
     * Returns 404 to non-owners (don't leak existence).
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $order->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel', 'serviceCenter', 'payments', 'coupon']);

        return response()->json(['order' => new OrderResource($order)]);
    }

    /**
     * POST /api/v1/user/orders/{order}/cancel
     * Owner-only, status=='pending' only (D-2.5a-5).
     * 404 for non-owner; 403 for terminal/non-pending state.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$order->canBeCancelledBy($request->user())) {
            return response()->json(
                ['message' => 'This order cannot be cancelled. Already confirmed or in another state.'],
                Response::HTTP_FORBIDDEN
            );
        }

        $order = $this->checkout->cancelOrder($order, $request->user(), $validated['reason'] ?? null);

        return response()->json(['order' => new OrderResource($order)]);
    }
}
