<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns an empty paginated list for a user with no orders', function () {
    $user = User::factory()->create(['phone' => '9999933333']);
    Sanctum::actingAs($user);

    $resp = $this->getJson('/api/v1/user/orders');

    $resp->assertStatus(200)
        ->assertJsonStructure([
            'orders',
            'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);

    expect($resp->json('orders'))->toBe([]);
    expect($resp->json('pagination.total'))->toBe(0);
});

it('rejects unauthenticated /user/orders with 401', function () {
    $resp = $this->getJson('/api/v1/user/orders');
    $resp->assertStatus(401);
});
