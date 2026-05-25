<?php

use App\Filament\Widgets\OperationsStats;
use App\Models\Order;

/**
 * Phase 4.2 — OperationsStats widget data-integrity tests.
 *
 * Confirms each Stat row computes its value from the seeded
 * facts and that the polling interval matches D-4.2-12 (60s).
 *
 * Uses spatie/invade to reach the protected getStats() method
 * (Filament StatsOverviewWidget convention — stats are not
 * exposed publicly because they're rendered by the parent
 * widget via blade partial).
 */

it('computes pending orders correctly and uses 60s polling', function () {
    Order::factory()->count(3)->create(['status' => 'pending']);
    Order::factory()->count(2)->create(['status' => 'confirmed']);
    Order::factory()->count(1)->create(['status' => 'completed']);

    $widget = new OperationsStats();
    $stats  = invade($widget)->getStats();

    $byLabel = collect($stats)->keyBy(fn ($s) => $s->getLabel());

    expect((int) $byLabel['Pending Orders']->getValue())->toBe(3);

    $reflection = new ReflectionClass(OperationsStats::class);
    expect($reflection->getStaticPropertyValue('pollingInterval'))->toBe('60s');
});

it('computes today bookings count correctly', function () {
    Order::factory()->count(2)->create(['created_at' => today()->setHour(10)]);
    Order::factory()->count(3)->create(['created_at' => now()->subDay()]);

    $widget = new OperationsStats();
    $stats  = invade($widget)->getStats();

    $byLabel = collect($stats)->keyBy(fn ($s) => $s->getLabel());

    expect((int) $byLabel["Today's Bookings"]->getValue())->toBe(2);
});
