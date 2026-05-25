<?php

use App\Filament\Resources\ServiceCategoryResource;
use App\Models\User;

it('lets an admin access ServiceCategoryResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(ServiceCategoryResource::getUrl('index'))
        ->assertSuccessful();
});

it('blocks a non-admin user from ServiceCategoryResource list page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(ServiceCategoryResource::getUrl('index'))
        ->assertForbidden();
});
