<?php

use App\Filament\Resources\CouponResource;
use App\Models\User;

it('lets an admin access CouponResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(CouponResource::getUrl('index'))
        ->assertSuccessful();
});

it('blocks a non-admin user from CouponResource list page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(CouponResource::getUrl('index'))
        ->assertForbidden();
});
