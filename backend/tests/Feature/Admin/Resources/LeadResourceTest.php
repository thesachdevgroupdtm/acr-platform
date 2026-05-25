<?php

use App\Filament\Resources\LeadResource;
use App\Models\User;

it('lets an admin access LeadResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(LeadResource::getUrl('index'))
        ->assertSuccessful();
});

it('blocks a non-admin user from LeadResource list page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(LeadResource::getUrl('index'))
        ->assertForbidden();
});
