<?php

use App\Filament\Resources\CouponResource\Pages\CreateCoupon;
use App\Filament\Resources\CouponResource\Pages\EditCoupon;
use App\Models\Coupon;
use App\Models\User;
use Livewire\Livewire;

/**
 * Phase 4.2 — Coupon admin data integrity tests.
 *
 * Covers code uppercase mutator (D-4.2-11) and the
 * description→T&C RichEditor round-trip (D-4.2-7).
 */

it('uppercases the coupon code on save', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code'            => 'lowercase10',
            'name'            => 'Lowercase Test Coupon',
            'description'     => '<p>Standard terms apply.</p>',
            'discount_type'   => 'percent',
            'discount_value'  => 10,
            'min_order_value' => 0,
            'is_active'       => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::where('name', 'Lowercase Test Coupon')->first();
    expect($coupon)->not->toBeNull();
    expect($coupon->code)->toBe('LOWERCASE10');
});

it('saves and retrieves rich-text terms in the description field', function () {
    $admin  = User::factory()->admin()->create();
    $coupon = Coupon::factory()->create([
        'description' => '<p>Old terms</p>',
    ]);

    $tcContent = '<p>This coupon is valid for <strong>first-time</strong> customers only.</p>';

    $this->actingAs($admin);
    Livewire::test(EditCoupon::class, ['record' => $coupon->getRouteKey()])
        ->fillForm(['description' => $tcContent])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $coupon->fresh();
    expect($fresh->description)->toContain('<strong>first-time</strong>');
});
