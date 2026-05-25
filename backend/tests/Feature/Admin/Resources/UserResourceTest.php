<?php

use App\Filament\Resources\UserResource;
use App\Models\User;

it('lets an admin access UserResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(UserResource::getUrl('index'))
        ->assertSuccessful();
});

it('blocks a non-admin user from UserResource list page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();
});
