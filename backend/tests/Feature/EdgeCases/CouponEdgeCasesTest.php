<?php

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCenter;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Phase 2.6d edge cases — coupons.
 *
 * Reason strings are matched verbatim against
 * App\Services\Coupon\CouponService::validate(). If validate()
 * returns 422 with a different message, the assertions below will
 * surface the drift immediately.
 */

it('rejects FIRST10 with a "already used" reason after the user has redeemed it once', function () {
    $user = User::factory()->create(['phone' => '9999955501']);
    Sanctum::actingAs($user);

    $coupon = Coupon::where('code', 'FIRST10')->firstOrFail();

    // Simulate a prior redemption by inserting a CouponUsage row
    // directly. This is exactly how CheckoutService::placeOrder
    // writes the row at order placement, so the per-user limit
    // check in validate() sees it identically.
    //
    // Coupon usages cascade-delete from orders (the orders FK on
    // coupon_usages is cascadeOnDelete), so we do still need a
    // real Order row to anchor it. A minimal stub order is enough.
    $center = ServiceCenter::query()->first();
    $stubOrder = Order::create([
        'order_number'      => 'ACR-TEST-' . random_int(10000, 99999),
        'user_id'           => $user->id,
        'service_center_id' => $center->id,
        'coupon_id'         => $coupon->id,
        'status'            => Order::STATUS_COMPLETED,
        'payment_status'    => Order::PAYMENT_STATUS_PAID,
        'name_snapshot'     => $user->name,
        'phone_snapshot'    => $user->phone,
        'vehicle_snapshot'  => [],
        'preferred_date'    => now()->subDays(30)->toDateString(),
        'preferred_time'    => '09:00 AM – 11:00 AM',
        'subtotal'          => 1000,
        'discount'          => 100,
        'tax'               => 162,
        'total'             => 1062,
    ]);
    CouponUsage::create([
        'coupon_id'       => $coupon->id,
        'user_id'         => $user->id,
        'order_id'        => $stubOrder->id,
        'discount_amount' => 100.00,
        'used_at'         => now()->subDays(30),
    ]);

    // Now build a fresh cart that would otherwise satisfy FIRST10.
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 1500]);

    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->postJson('/api/v1/cart/coupon', ['code' => 'FIRST10']);

    $resp->assertStatus(422)
        ->assertJson(['message' => 'You have already used this coupon.']);
});

it('auto-clears a stale coupon when the cart is loaded after deactivation', function () {
    $user = User::factory()->create(['phone' => '9999955502']);
    Sanctum::actingAs($user);

    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 2000]);

    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    // Apply FIRST10 — cart.coupon_id is now set, totals show discount.
    $this->postJson('/api/v1/cart/coupon', ['code' => 'FIRST10'])->assertStatus(200);

    // Admin deactivates the coupon out-of-band.
    Coupon::where('code', 'FIRST10')->update(['is_active' => false]);

    // GET /cart now should self-heal: Cart::reloadCoupon clears
    // coupon_id and totals.discount drops to 0.
    $resp = $this->getJson('/api/v1/cart');
    $resp->assertStatus(200);

    expect((float) $resp->json('cart.totals.discount'))->toBe(0.0);
    expect($resp->json('cart.totals.coupon'))->toBeNull();
});

it('rejects FIRST10 when subtotal is below the ₹500 minimum order value', function () {
    $user = User::factory()->create(['phone' => '9999955503']);
    Sanctum::actingAs($user);

    $category = ServiceCategory::factory()->create();
    // base_price 200 < 500 minimum.
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 200]);

    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->postJson('/api/v1/cart/coupon', ['code' => 'FIRST10']);

    $resp->assertStatus(422);
    expect($resp->json('message'))->toContain('Minimum order')->toContain('500');
});

it('rejects an active coupon with an expired expiry_date', function () {
    $user = User::factory()->create(['phone' => '9999955504']);
    Sanctum::actingAs($user);

    // Custom expired coupon — active flag still true, but expiry_date
    // is in the past so Coupon::isExpired() returns true.
    Coupon::create([
        'code'            => 'EXPIRED1',
        'name'            => 'Expired Test',
        'description'     => 'Test fixture for the expiry edge case.',
        'discount_type'   => 'flat',
        'discount_value'  => 100.00,
        'min_order_value' => 0,
        'is_active'       => true,
        'expiry_date'     => now()->subDay()->toDateString(),
    ]);

    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 1000]);

    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->postJson('/api/v1/cart/coupon', ['code' => 'EXPIRED1']);

    $resp->assertStatus(422)
        ->assertJson(['message' => 'This coupon has expired.']);
});

it('lets a GUEST (not signed in) apply FIRST10 and preview the discounted total', function () {
    // Guest = no Sanctum::actingAs; identity is purely the cart-session
    // UUID carried in the X-Cart-Session header. This proves the apply
    // gate has moved off the apply step (guest coupon preview), while
    // the existing validation (active / not-expired / min-order / global
    // limit / applicability) still runs.
    $guestUuid = (string) Str::uuid();
    $headers   = ['X-Cart-Session' => $guestUuid];

    $category = ServiceCategory::factory()->create();
    // base_price 2000 → FIRST10 = 10% = ₹200 (under the ₹500 cap).
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 2000]);

    $this->withHeaders($headers)->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'FIRST10']);

    $resp->assertStatus(200)
        ->assertJsonPath('cart.totals.coupon.code', 'FIRST10');
    expect((float) $resp->json('cart.totals.subtotal'))->toBe(2000.0);
    expect((float) $resp->json('cart.totals.discount'))->toBe(200.0);
    expect((float) $resp->json('cart.totals.total'))->toBe(1800.0);

    // The discount persists on the guest cart across a fresh GET — the
    // preview survives navigation/refresh because coupon_id is stored
    // server-side on the guest cart, not just held in the UI.
    $fresh = $this->withHeaders($headers)->getJson('/api/v1/cart');
    $fresh->assertStatus(200)->assertJsonPath('cart.totals.coupon.code', 'FIRST10');
    expect((float) $fresh->json('cart.totals.discount'))->toBe(200.0);
});

it('still rejects an invalid coupon code for a guest (validation intact)', function () {
    $guestUuid = (string) Str::uuid();
    $headers   = ['X-Cart-Session' => $guestUuid];

    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create(['category_id' => $category->id, 'base_price' => 2000]);

    $this->withHeaders($headers)->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->withHeaders($headers)->postJson('/api/v1/cart/coupon', ['code' => 'NOPE404']);

    $resp->assertStatus(422)
        ->assertJson(['message' => 'Invalid coupon code.']);
});
