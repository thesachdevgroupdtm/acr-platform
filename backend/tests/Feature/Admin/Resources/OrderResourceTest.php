<?php

use App\Filament\Resources\OrderResource;
use App\Models\User;

/**
 * Phase 4.2 — OrderResource access control tests.
 *
 * Filament redirect probe (audit §1):
 *   - Unauthenticated: 302 → /admin/login
 *   - Authenticated non-admin: 403 (assertForbidden)
 */

it('lets an admin access OrderResource list page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(OrderResource::getUrl('index'))
        ->assertSuccessful();
});

it('blocks a non-admin user from OrderResource list page', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
        ->get(OrderResource::getUrl('index'))
        ->assertForbidden();
});
