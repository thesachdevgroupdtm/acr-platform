<?php

use App\Filament\Resources\ServiceResource;
use App\Models\User;

it('lets an admin access ServiceResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(ServiceResource::getUrl('index'))
        ->assertSuccessful();
});

it('blocks a non-admin user from ServiceResource list page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(ServiceResource::getUrl('index'))
        ->assertForbidden();
});
