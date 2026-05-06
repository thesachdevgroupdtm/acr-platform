<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Phase 2.6d edge cases — cart merge protocol.
 *
 * The cart-merge contract is "last cart wins" per
 * App\Services\Cart\CartMergeService — guest items REPLACE the user's
 * pre-existing items on merge. Empty guest cart preserves the user
 * cart untouched. Confirmed by reading
 * backend/app/Services/Cart/CartMergeService.php (Phase 2.5.1
 * D-2.5.1-2).
 */

it('replaces user cart items with guest cart items on merge (last cart wins)', function () {
    $category = ServiceCategory::factory()->create();
    $userOnly  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 500]);
    $guestOnly = Service::factory()->create(['category_id' => $category->id, 'base_price' => 800]);

    $user = User::factory()->create(['phone' => '9999944401']);

    // Pre-existing user cart with one item.
    $userCart = Cart::create([
        'user_id'    => $user->id,
        'status'     => 'active',
        'currency'   => 'INR',
        'expires_at' => now()->addDays(30),
    ]);
    CartItem::create([
        'cart_id'             => $userCart->id,
        'service_id'          => $userOnly->id,
        'quantity'            => 1,
        'unit_price_snapshot' => 500.00,
    ]);

    // Guest cart with a different item.
    $guestUuid = (string) Str::uuid();
    $guestCart = Cart::create([
        'session_uuid' => $guestUuid,
        'status'       => 'active',
        'currency'     => 'INR',
        'expires_at'   => now()->addDays(30),
    ]);
    CartItem::create([
        'cart_id'             => $guestCart->id,
        'service_id'          => $guestOnly->id,
        'quantity'            => 2,
        'unit_price_snapshot' => 800.00,
    ]);

    Sanctum::actingAs($user);

    $resp = $this->postJson('/api/v1/cart/merge', [
        'guest_session_uuid' => $guestUuid,
    ]);

    $resp->assertStatus(200);

    // After merge: user cart contains ONLY the guest item; original
    // user item was deleted.
    $userCart->refresh()->load('items');
    expect($userCart->items)->toHaveCount(1);
    expect($userCart->items->first()->service_id)->toBe($guestOnly->id);
    expect((int) $userCart->items->first()->quantity)->toBe(2);

    // Guest cart is marked converted.
    $guestCart->refresh();
    expect($guestCart->status)->toBe('converted');
});

it('preserves the user cart when the guest cart is empty', function () {
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 750]);

    $user = User::factory()->create(['phone' => '9999944402']);

    $userCart = Cart::create([
        'user_id'    => $user->id,
        'status'     => 'active',
        'currency'   => 'INR',
        'expires_at' => now()->addDays(30),
    ]);
    CartItem::create([
        'cart_id'             => $userCart->id,
        'service_id'          => $service->id,
        'quantity'            => 1,
        'unit_price_snapshot' => 750.00,
    ]);

    // Empty guest cart — exists but has no items.
    $guestUuid = (string) Str::uuid();
    Cart::create([
        'session_uuid' => $guestUuid,
        'status'       => 'active',
        'currency'     => 'INR',
        'expires_at'   => now()->addDays(30),
    ]);

    Sanctum::actingAs($user);

    $resp = $this->postJson('/api/v1/cart/merge', [
        'guest_session_uuid' => $guestUuid,
    ]);

    $resp->assertStatus(200);

    $userCart->refresh()->load('items');
    expect($userCart->items)->toHaveCount(1);
    expect($userCart->items->first()->service_id)->toBe($service->id);
});

it('is idempotent: re-merging a converted guest cart returns the user cart unchanged', function () {
    $category = ServiceCategory::factory()->create();
    $svc      = Service::factory()->create(['category_id' => $category->id, 'base_price' => 600]);

    $user = User::factory()->create(['phone' => '9999944403']);

    $guestUuid = (string) Str::uuid();
    $guestCart = Cart::create([
        'session_uuid' => $guestUuid,
        'status'       => 'active',
        'currency'     => 'INR',
        'expires_at'   => now()->addDays(30),
    ]);
    CartItem::create([
        'cart_id'             => $guestCart->id,
        'service_id'          => $svc->id,
        'quantity'            => 1,
        'unit_price_snapshot' => 600.00,
    ]);

    Sanctum::actingAs($user);

    // First merge — guest cart becomes 'converted'.
    $this->postJson('/api/v1/cart/merge', [
        'guest_session_uuid' => $guestUuid,
    ])->assertStatus(200);

    // Second merge with the same UUID — no active guest cart left to
    // merge, so the service falls into the "guestCart not found" branch
    // and returns the user cart untouched.
    $resp = $this->postJson('/api/v1/cart/merge', [
        'guest_session_uuid' => $guestUuid,
    ]);
    $resp->assertStatus(200);

    // User cart still has the single item from the first merge.
    expect($resp->json('cart.items'))->toHaveCount(1);
});
