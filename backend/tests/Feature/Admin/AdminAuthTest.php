<?php

use App\Models\User;
use Filament\Facades\Filament;

/**
 * Phase 4.1 — Filament admin-panel access tests.
 *
 * Per locked decision D-4.1-7. Confirms the FilamentUser contract
 * implementation on App\Models\User correctly gates panel access
 * by the is_admin column. Pure unit-style — no HTTP, no Livewire
 * boot. The slower /admin login round-trip is reserved for a
 * Phase 4.2 Filament-Browser-style test if needed.
 */

it('lets an admin user access the admin panel', function () {
    $admin = User::factory()->admin()->create([
        'phone' => '9999900801',
        'email' => 'admin-test@example.com',
        'password' => bcrypt('test-password'),
    ]);

    expect($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('blocks a non-admin user from the admin panel', function () {
    $customer = User::factory()->create([
        'phone' => '9999900802',
        'is_admin' => false,
    ]);

    expect($customer->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('defaults newly created users to is_admin=false (no panel access)', function () {
    // The factory does not set is_admin; the column default in the
    // migration is false. A vanilla create() must therefore land
    // outside the panel. refresh() re-reads from the DB — Eloquent
    // returns the in-memory model with `is_admin=null` immediately
    // after create() because the attribute wasn't explicitly set,
    // even though the DB applied default(false).
    $user = User::factory()->create(['phone' => '9999900803'])->refresh();

    expect($user->is_admin)->toBeFalse();
    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});
