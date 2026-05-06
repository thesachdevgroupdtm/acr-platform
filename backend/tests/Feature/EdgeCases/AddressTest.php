<?php

use App\Models\Address;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Phase 2.6d edge cases — address CRUD invariants.
 *
 * AddressController enforces "exactly one default per user". The
 * first address auto-promotes regardless of input. Promoting a
 * second address auto-demotes the first. Deleting the default
 * auto-promotes the most-recent surviving row.
 *
 * Validation: pincode must match /^\d{6}$/.
 */

it('lists all of a user\'s addresses, default first then by recency', function () {
    $user = User::factory()->create(['phone' => '9999977701']);
    Sanctum::actingAs($user);

    $payloads = [
        ['label' => 'Home',   'line1' => 'A 1', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110001'],
        ['label' => 'Office', 'line1' => 'B 2', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110002'],
        ['label' => 'Other',  'line1' => 'C 3', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110003'],
    ];

    foreach ($payloads as $p) {
        $this->postJson('/api/v1/user/addresses', $p)->assertStatus(200);
    }

    $resp = $this->getJson('/api/v1/user/addresses');
    $resp->assertStatus(200);
    expect($resp->json('addresses'))->toHaveCount(3);

    // The first address ever created is auto-promoted to default and
    // must surface first under the orderByDesc('is_default') sort.
    expect($resp->json('addresses.0.label'))->toBe('Home');
    expect($resp->json('addresses.0.is_default'))->toBeTrue();
});

it('demotes the previous default when a new address is created with is_default=true', function () {
    $user = User::factory()->create(['phone' => '9999977702']);
    Sanctum::actingAs($user);

    // First address auto-promotes to default.
    $first = $this->postJson('/api/v1/user/addresses', [
        'label' => 'Home', 'line1' => 'A 1', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110001',
    ])->assertStatus(200)->json('address');

    // Second with is_default=true takes over.
    $second = $this->postJson('/api/v1/user/addresses', [
        'label' => 'Office', 'line1' => 'B 2', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110002',
        'is_default' => true,
    ])->assertStatus(200)->json('address');

    // First must be demoted to non-default; second is the default.
    expect((bool) Address::find($first['id'])->is_default)->toBeFalse();
    expect((bool) Address::find($second['id'])->is_default)->toBeTrue();
});

it('auto-promotes a surviving address when the default is deleted (one-default invariant holds)', function () {
    $user = User::factory()->create(['phone' => '9999977703']);
    Sanctum::actingAs($user);

    // Three addresses; first is default by auto-promotion.
    $a1 = $this->postJson('/api/v1/user/addresses', [
        'label' => 'A1', 'line1' => 'A 1', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110001',
    ])->json('address');
    $a2 = $this->postJson('/api/v1/user/addresses', [
        'label' => 'A2', 'line1' => 'A 2', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110002',
    ])->json('address');
    $a3 = $this->postJson('/api/v1/user/addresses', [
        'label' => 'A3', 'line1' => 'A 3', 'city' => 'Delhi', 'state' => 'DL', 'pincode' => '110003',
    ])->json('address');

    expect((bool) Address::find($a1['id'])->is_default)->toBeTrue();

    // Delete the default. The controller's destroy() picks the
    // most recently created surviving address by orderByDesc('created_at').
    // In tests the three rows are inserted within the same second
    // (timestamps tie at 1s precision), so the actual winner between
    // a2 and a3 is implementation-defined. The hard invariant we
    // assert here is "exactly one default survives, it is not a1".
    $this->deleteJson("/api/v1/user/addresses/{$a1['id']}")->assertStatus(200);

    expect(Address::find($a1['id']))->toBeNull();

    $remaining = Address::where('user_id', $user->id)->get();
    expect($remaining)->toHaveCount(2);

    $defaults = $remaining->where('is_default', true);
    expect($defaults)->toHaveCount(1);
    expect(in_array($defaults->first()->id, [$a2['id'], $a3['id']], true))->toBeTrue();
});
