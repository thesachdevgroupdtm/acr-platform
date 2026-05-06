<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;

it('adds a service to a guest cart and returns server-computed totals', function () {
    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create([
        'category_id' => $category->id,
        'base_price'  => 1200.00,
    ]);

    $sessionUuid = (string) Str::uuid();

    $resp = $this->withHeaders(['X-Cart-Session' => $sessionUuid])
        ->postJson('/api/v1/cart/items', [
            'kind'     => 'service',
            'ref_id'   => $service->id,
            'quantity' => 2,
        ]);

    $resp->assertStatus(200);

    // Use float casts because SQLite may return integers for whole-number totals;
    // assertJsonPath uses strict (===) equality and would reject 2400 vs 2400.0.
    expect((float) $resp->json('cart.totals.subtotal'))->toBe(2400.0);
    expect((float) $resp->json('cart.totals.discount'))->toBe(0.0);
    expect((float) $resp->json('cart.totals.total'))->toBe(2400.0);

    expect($resp->json('cart.items'))->toHaveCount(1);
    expect((float) $resp->json('cart.items.0.unit_price_snapshot'))->toBe(1200.0);
    expect($resp->json('cart.items.0.quantity'))->toBe(2);
});

it('rejects a cart write without an auth/session identifier', function () {
    $resp = $this->postJson('/api/v1/cart/items', [
        'kind'   => 'service',
        'ref_id' => 1,
    ]);

    $resp->assertStatus(400);
});
