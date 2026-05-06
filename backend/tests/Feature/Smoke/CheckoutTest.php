<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceCenter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('quotes checkout totals for an authenticated cart', function () {
    $user = User::factory()->create(['phone' => '9999922222']);
    Sanctum::actingAs($user);

    $category = ServiceCategory::factory()->create();
    $service  = Service::factory()->create([
        'category_id' => $category->id,
        'base_price'  => 1500.00,
    ]);

    $this->postJson('/api/v1/cart/items', [
        'kind'     => 'service',
        'ref_id'   => $service->id,
        'quantity' => 1,
    ])->assertStatus(200);

    $center = ServiceCenter::query()->first();
    expect($center)->not->toBeNull(); // seeded by service_centers migration

    $resp = $this->postJson('/api/v1/checkout/quote', [
        'preferred_date'    => now()->addDay()->toDateString(),
        'preferred_time'    => '09:00 AM – 11:00 AM',
        'service_center_id' => $center->id,
    ]);

    $resp->assertStatus(200)
        ->assertJsonStructure(['quote' => ['subtotal', 'discount', 'tax', 'total']]);

    expect((float) $resp->json('quote.subtotal'))->toBe(1500.0);
    expect((float) $resp->json('quote.total'))->toBeGreaterThanOrEqual(1500.0);
});
