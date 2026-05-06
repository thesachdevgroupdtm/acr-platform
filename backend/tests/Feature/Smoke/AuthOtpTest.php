<?php

use App\Models\User;

it('sends an OTP for an existing user (dev driver leaks code in debug)', function () {
    $user = User::factory()->create([
        'phone' => '9999900001',
    ]);

    $resp = $this->postJson('/api/v1/auth/send-otp', [
        'channel'     => 'phone',
        'destination' => '9999900001',
    ]);

    $resp->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($resp->json('expires_at'))->not->toBeEmpty();
});

it('returns 404 when sending OTP to a phone that does not exist', function () {
    $resp = $this->postJson('/api/v1/auth/send-otp', [
        'channel'     => 'phone',
        'destination' => '9999900099',
    ]);

    $resp->assertStatus(404);
});

it('verifies OTP via dev bypass and returns a sanctum token', function () {
    $user = User::factory()->create([
        'phone' => '9999900002',
    ]);

    $resp = $this->postJson('/api/v1/auth/verify-otp', [
        'channel'     => 'phone',
        'destination' => '9999900002',
        'code'        => '123456',
    ]);

    $resp->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'phone']]);

    expect($resp->json('token'))->toBeString()->not->toBeEmpty();
    expect($resp->json('user.phone'))->toBe('9999900002');
});
