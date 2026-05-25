<?php

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;

/**
 * Phase 4.2 — User admin action-level tests.
 *
 * Covers Toggle Admin behavior + self-protection (D-4.2-10).
 */

it('Toggle Admin action flips is_admin from false to true', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin);
    Livewire::test(ListUsers::class)
        ->callTableAction('toggleAdmin', $target);

    expect($target->fresh()->is_admin)->toBeTrue();
});

it('Toggle Admin action prevents self-modification (self-protection)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Livewire::test(ListUsers::class)
        ->assertTableActionDisabled('toggleAdmin', $admin);
});
