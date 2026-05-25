<?php

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

/**
 * Phase 4.2 — Order admin action-level business logic tests.
 *
 * Covers status-aware action visibility AND post-action state
 * (status, timestamp, cancelled_reason). The Order model's
 * transitionTo() flow is intentionally bypassed by the admin
 * resource (audit §10) — confirmed → completed skips
 * in_service.
 */

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('shows the Confirm action only when status is pending', function () {
    $pending = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->assertTableActionVisible('confirm', $pending);
});

it('hides the Confirm action when status is confirmed', function () {
    $confirmed = Order::factory()->create(['status' => 'confirmed']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->assertTableActionHidden('confirm', $confirmed);
});

it('Confirm action transitions pending to confirmed and stamps confirmed_at', function () {
    $order = Order::factory()->create(['status' => 'pending', 'confirmed_at' => null]);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->callTableAction('confirm', $order)
        ->assertHasNoTableActionErrors();

    $fresh = $order->fresh();
    expect($fresh->status)->toBe('confirmed');
    expect($fresh->confirmed_at)->not->toBeNull();
});

it('Cancel action requires a reason of at least 10 characters', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->callTableAction('cancel', $order, data: ['reason' => 'short'])
        ->assertHasTableActionErrors(['reason']);

    expect($order->fresh()->status)->toBe('pending');
});

it('Cancel action stores reason and stamps cancelled_at', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->callTableAction('cancel', $order, data: [
            'reason' => 'Customer requested cancellation due to schedule conflict',
        ])
        ->assertHasNoTableActionErrors();

    $fresh = $order->fresh();
    expect($fresh->status)->toBe('cancelled');
    expect($fresh->cancelled_reason)->toContain('schedule conflict');
    expect($fresh->cancelled_at)->not->toBeNull();
});

it('Mark Completed visibility requires confirmed status', function () {
    $pending   = Order::factory()->create(['status' => 'pending']);
    $confirmed = Order::factory()->create(['status' => 'confirmed']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->assertTableActionHidden('markCompleted', $pending)
        ->assertTableActionVisible('markCompleted', $confirmed);
});

it('Cancel action is allowed from confirmed status', function () {
    $order = Order::factory()->create(['status' => 'confirmed']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->assertTableActionVisible('cancel', $order)
        ->callTableAction('cancel', $order, data: [
            'reason' => 'Service center capacity exceeded — rebooking',
        ])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe('cancelled');
});

it('terminal states (completed/cancelled) hide all transition actions', function () {
    $completed = Order::factory()->create(['status' => 'completed']);
    $cancelled = Order::factory()->create(['status' => 'cancelled']);

    $this->actingAs($this->admin);
    Livewire::test(ListOrders::class)
        ->assertTableActionHidden('confirm', $completed)
        ->assertTableActionHidden('cancel', $completed)
        ->assertTableActionHidden('markCompleted', $completed)
        ->assertTableActionHidden('confirm', $cancelled)
        ->assertTableActionHidden('cancel', $cancelled)
        ->assertTableActionHidden('markCompleted', $cancelled);
});
