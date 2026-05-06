<?php

use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('applies FIRST10 to an authenticated cart and quotes the percent discount capped at max_discount', function () {
    // FIRST10 is seeded by the coupons migration: 10% off, max ₹500, min order ₹500.
    expect(Coupon::where('code', 'FIRST10')->exists())->toBeTrue();

    $user = User::factory()->create(['phone' => '9999911111']);
    Sanctum::actingAs($user);

    $category = ServiceCategory::factory()->create();
    // base_price = 2000 means 10% = 200 (under the ₹500 cap, so we exercise the percent path).
    $service  = Service::factory()->create([
        'category_id' => $category->id,
        'base_price'  => 2000.00,
    ]);

    // Seed the cart by adding an item under the user's session.
    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->postJson('/api/v1/cart/coupon', ['code' => 'FIRST10']);

    $resp->assertStatus(200)
        ->assertJsonPath('cart.totals.coupon.code', 'FIRST10');

    // Float-coerce because SQLite returns ints for whole numbers.
    expect((float) $resp->json('cart.totals.subtotal'))->toBe(2000.0);
    expect((float) $resp->json('cart.totals.discount'))->toBe(200.0);
    expect((float) $resp->json('cart.totals.total'))->toBe(1800.0);
});

it('rejects FIRST10 when subtotal is below min_order_value (₹500)', function () {
    $user = User::factory()->create(['phone' => '9999911112']);
    Sanctum::actingAs($user);

    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create([
        'category_id' => $category->id,
        'base_price'  => 200.00,
    ]);

    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $resp = $this->postJson('/api/v1/cart/coupon', ['code' => 'FIRST10']);

    $resp->assertStatus(422);
});
