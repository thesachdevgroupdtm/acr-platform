<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Livewire\Livewire;

/**
 * Phase 4.2 — Admin form security boundary tests.
 *
 * D-4.2-4: Password is NOT exposed in the User form.
 * D-4.2-5: Phone is read-only when is_verified_phone === true,
 *          editable otherwise.
 */

it('does not expose a password field on the User edit form', function () {
    $admin  = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin);

    $component = Livewire::test(EditUser::class, ['record' => $target->getRouteKey()]);

    $fieldNames = array_keys(
        $component->instance()->getForm('form')->getFlatFields(withHidden: true)
    );

    expect($fieldNames)->not->toContain('password');
});

it('marks the phone field read-only when the user is phone-verified', function () {
    $admin    = User::factory()->admin()->create();
    $verified = User::factory()->create(['is_verified_phone' => true]);

    $this->actingAs($admin);
    Livewire::test(EditUser::class, ['record' => $verified->getRouteKey()])
        ->assertFormFieldIsDisabled('phone');
});

it('keeps the phone field editable when the user is unverified', function () {
    $admin      = User::factory()->admin()->create();
    $unverified = User::factory()->create(['is_verified_phone' => false]);

    $this->actingAs($admin);
    Livewire::test(EditUser::class, ['record' => $unverified->getRouteKey()])
        ->assertFormFieldIsEnabled('phone');
});
