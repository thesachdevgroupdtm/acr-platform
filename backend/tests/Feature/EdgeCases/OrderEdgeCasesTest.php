<?php

use App\Models\Order;
use App\Models\ServiceCenter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Phase 2.6d edge cases — order cancellation.
 *
 * Cancellation is STATUS-BASED, not time-windowed: only orders in
 * status='pending' can be cancelled by the customer (per
 * App\Models\Order::canBeCancelledBy and
 * App\Services\Checkout\CheckoutService::cancelOrder). The
 * controller returns 403 for non-pending orders with the literal
 * message "This order cannot be cancelled. Already confirmed or in
 * another state."
 *
 * (The Phase 2.6d spec template suggested a 24h time window — that
 * does not exist in the current implementation. These tests
 * document the actual contract.)
 */

function makeOrder(User $user, string $status): Order
{
    $center = ServiceCenter::query()->first();

    return Order::create([
        'order_number'      => 'ACR-EDGE-' . random_int(10000, 99999),
        'user_id'           => $user->id,
        'service_center_id' => $center->id,
        'status'            => $status,
        'payment_status'    => Order::PAYMENT_STATUS_PENDING,
        'name_snapshot'     => $user->name,
        'phone_snapshot'    => $user->phone,
        'vehicle_snapshot'  => [],
        'preferred_date'    => now()->addDays(2)->toDateString(),
        'preferred_time'    => '09:00 AM – 11:00 AM',
        'subtotal'          => 1500,
        'discount'          => 0,
        'tax'               => 270,
        'total'             => 1770,
        'placed_at'         => now()->subMinutes(30),
    ]);
}

it('allows the owner to cancel a pending order', function () {
    $user = User::factory()->create(['phone' => '9999966601']);
    Sanctum::actingAs($user);

    $order = makeOrder($user, Order::STATUS_PENDING);

    $resp = $this->postJson("/api/v1/user/orders/{$order->id}/cancel", [
        'reason' => 'Changed plans',
    ]);

    $resp->assertStatus(200);
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
    expect($order->cancelled_reason)->toBe('Changed plans');
    expect($order->cancelled_at)->not->toBeNull();
});

it('rejects (403) cancelling an order that has already been confirmed', function () {
    $user = User::factory()->create(['phone' => '9999966602']);
    Sanctum::actingAs($user);

    $order = makeOrder($user, Order::STATUS_CONFIRMED);

    $resp = $this->postJson("/api/v1/user/orders/{$order->id}/cancel");

    $resp->assertStatus(403)
        ->assertJsonFragment([
            'message' => 'This order cannot be cancelled. Already confirmed or in another state.',
        ]);

    // Status unchanged.
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CONFIRMED);
});

it('rejects (403) cancelling a completed order (terminal state)', function () {
    $user = User::factory()->create(['phone' => '9999966603']);
    Sanctum::actingAs($user);

    $order = makeOrder($user, Order::STATUS_COMPLETED);

    $resp = $this->postJson("/api/v1/user/orders/{$order->id}/cancel");

    $resp->assertStatus(403);

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_COMPLETED);
});
