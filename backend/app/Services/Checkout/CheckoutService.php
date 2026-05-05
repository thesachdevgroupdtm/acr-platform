<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Coupon\CouponService;
use App\Services\Order\FakeBookingGuard;
use App\Services\Order\OrderNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Phase 2.5a — checkout pipeline per /PHASE2_CONTRACT.md §6.7.
 *
 * Three responsibilities:
 *   - quote(): pure computation — no DB writes — used by frontend
 *     to refresh totals when user edits the form.
 *   - placeOrder(): the full transactional flow:
 *       fake-booking guard → quote → number → order → items →
 *       payment_transactions → cart 'converted'.
 *   - cancelOrder(): user-initiated cancel, gated by
 *     Order::canBeCancelledBy().
 *
 * Coupon math is OUT OF SCOPE for 2.5a (D-2.5a-4). Phase 2.5b will
 * extend quote() with discount logic; the field already exists on
 * orders so the placeOrder() schema doesn't need a re-migration.
 */
class CheckoutService
{
    public function __construct(
        private OrderNumberService $orderNumber,
        private FakeBookingGuard $guard,
        private CouponService $coupons,
    ) {
    }

    /**
     * Pure compute. Used by POST /checkout/quote.
     * Does NOT run the fake-booking guard — quote is read-only.
     */
    public function quote(Cart $cart, array $checkoutData): array
    {
        $items = $cart->relationLoaded('items')
            ? $cart->items
            : $cart->items()->with('service')->get();

        $subtotal = (float) $items->sum(
            fn ($i) => (float) $i->unit_price_snapshot * (int) $i->quantity
        );

        // Phase 2.6a — single source of truth on the model. Same
        // helper backs CartService::totalsFor so cart and checkout
        // can never disagree on discount math.
        $reloaded  = $cart->reloadCoupon($subtotal);
        $discount   = $reloaded['discount'] ?? 0.0;
        $couponMeta = $reloaded['meta']     ?? null;

        $gstPct = (int) config('services.gst_percentage', 18);
        $tax    = round(($subtotal - $discount) * ($gstPct / 100), 2);
        $total  = round(max(0.0, $subtotal - $discount + $tax), 2);

        $preview = $items->map(function (CartItem $i) {
            $title = $i->relationLoaded('service') && $i->service
                ? (string) $i->service->name
                : (string) ($i->meta['title'] ?? '');
            return [
                'service_id' => $i->service_id,
                'title'      => $title,
                'quantity'   => (int) $i->quantity,
                'unit_price' => round((float) $i->unit_price_snapshot, 2),
                'line_total' => round((float) $i->unit_price_snapshot * (int) $i->quantity, 2),
            ];
        })->values()->all();

        $breakdown = [
            ['label' => 'Subtotal', 'value' => round($subtotal, 2)],
        ];
        if ($couponMeta) {
            $breakdown[] = [
                'label' => "Coupon ({$couponMeta['code']})",
                'value' => -round($discount, 2),
            ];
        }
        $breakdown[] = ['label' => "GST ({$gstPct}%)", 'value' => $tax];
        $breakdown[] = ['label' => 'Total', 'value' => $total];

        return [
            'subtotal'        => round($subtotal, 2),
            'discount'        => round($discount, 2),
            'coupon'          => $couponMeta,
            'tax'             => $tax,
            'total'           => $total,
            'gst_pct'         => $gstPct,
            'items'           => $preview,
            'breakdown_lines' => $breakdown,
        ];
    }

    /**
     * @throws \App\Services\Order\PhoneNotVerifiedException 403
     * @throws \App\Services\Order\RateLimitedException      429
     * @throws \App\Services\Order\DuplicateBookingException 422
     * @throws RuntimeException                              empty cart
     */
    public function placeOrder(Cart $cart, array $checkoutData, User $user): Order
    {
        $cart->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel', 'coupon']);

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Cart is empty.');
        }

        // Fake-booking guard — by-ref so it can stash is_high_risk.
        $this->guard->enforce($user, $cart, $checkoutData);

        $totals = $this->quote($cart, $checkoutData);

        return DB::transaction(function () use ($cart, $checkoutData, $user, $totals) {
            $orderNumber = $this->orderNumber->generate();

            // Vehicle snapshot from the first cart item that has a vehicle.
            $vehicleSnapshot = $this->buildVehicleSnapshot($cart);

            // Phase 2.5b — pin the cart's applied coupon onto the order.
            $couponId = $cart->coupon_id;

            $order = Order::create([
                'order_number'      => $orderNumber,
                'user_id'           => $user->id,
                'service_center_id' => $checkoutData['service_center_id'] ?? null,
                'coupon_id'         => $couponId,
                'status'            => Order::STATUS_PENDING,
                'payment_status'    => Order::PAYMENT_STATUS_PENDING,
                'name_snapshot'     => $checkoutData['name'] ?? $user->name,
                'phone_snapshot'    => $checkoutData['phone'] ?? $user->phone,
                'email_snapshot'    => $checkoutData['email'] ?? $user->email,
                'address'           => $checkoutData['address'] ?? null,
                'vehicle_snapshot'  => $vehicleSnapshot,
                'preferred_date'    => $checkoutData['preferred_date'],
                'preferred_time'    => $checkoutData['preferred_time'],
                'subtotal'          => $totals['subtotal'],
                'discount'          => $totals['discount'],
                'tax'               => $totals['tax'],
                'total'             => $totals['total'],
                'notes'             => $checkoutData['notes'] ?? null,
                'is_high_risk'      => (bool) ($checkoutData['is_high_risk'] ?? false),
                'placed_at'         => now(),
            ]);

            // Phase 2.5b D-2.5b-7 — claim coupon usage atomically.
            if ($couponId !== null && $cart->coupon !== null) {
                $this->coupons->claim(
                    $cart->coupon,
                    $user,
                    $order,
                    (float) $totals['discount'],
                );
            }

            foreach ($cart->items as $item) {
                $title = $item->service?->name ?? ($item->meta['title'] ?? '');
                OrderItem::create([
                    'order_id'               => $order->id,
                    'service_id'             => $item->service_id,
                    'package_id'             => $item->package_id,
                    'product_id'             => $item->product_id,
                    'brand_id'               => $item->brand_id,
                    'model_id'               => $item->model_id,
                    'fuel_id'                => $item->fuel_id,
                    'service_title_snapshot' => (string) $title,
                    'quantity'               => $item->quantity,
                    'unit_price_snapshot'    => $item->unit_price_snapshot,
                    'line_total_snapshot'    => round((float) $item->unit_price_snapshot * (int) $item->quantity, 2),
                    'meta'                   => $item->meta,
                ]);
            }

            PaymentTransaction::create([
                'order_id' => $order->id,
                'method'   => PaymentTransaction::METHOD_CASH_AT_CENTER,
                'status'   => PaymentTransaction::STATUS_PENDING,
                'amount'   => $order->total,
            ]);

            // Convert the cart so it can't be re-checked-out.
            $cart->status = 'converted';
            $cart->expires_at = now();
            $cart->save();

            $order->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel', 'serviceCenter', 'payments', 'coupon']);

            Log::info('Order placed', [
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'user_id'      => $user->id,
                'total'        => $order->total,
                'is_high_risk' => $order->is_high_risk,
            ]);

            return $order;
        });
    }

    public function cancelOrder(Order $order, User $user, ?string $reason = null): Order
    {
        if (!$order->canBeCancelledBy($user)) {
            // Surfaces as 403 in the controller via abort().
            throw new RuntimeException('This order cannot be cancelled.');
        }

        DB::transaction(function () use ($order, $reason) {
            $order->transitionTo(Order::STATUS_CANCELLED, $reason);
            // Phase 2.5a payments are method='cash_at_center', status='pending'
            // — nothing to refund. Real-gateway refund logic lands when the
            // gateway lands (Phase 4+).
        });

        $order->load(['items.service', 'items.brand', 'items.carModel', 'items.fuel', 'serviceCenter', 'payments', 'coupon']);

        Log::info('Order cancelled by user', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'user_id'      => $user->id,
            'reason'       => $reason,
        ]);

        return $order;
    }

    /**
     * Build a fully-denormalised vehicle snapshot from the cart's
     * first vehicle-bearing item. In Phase 2.5a all items in a cart
     * share the same vehicle (Quick-Estimate flow); when 2.6+ ships
     * cross-vehicle carts, this becomes a per-item field on
     * order_items and this top-level snapshot moves to "primary
     * vehicle".
     */
    private function buildVehicleSnapshot(Cart $cart): array
    {
        $item = $cart->items->first(function ($i) {
            return $i->brand_id || $i->model_id || $i->fuel_id;
        });

        if (!$item) {
            return [];
        }

        return [
            'brand_id'   => $item->brand_id,
            'brand_name' => $item->brand?->name,
            'brand_slug' => $item->brand?->slug,
            'model_id'   => $item->model_id,
            'model_name' => $item->carModel?->name,
            'model_slug' => $item->carModel?->slug,
            'fuel_id'    => $item->fuel_id,
            'fuel_name'  => $item->fuel?->name,
            'fuel_slug'  => $item->fuel?->slug,
        ];
    }
}
