<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2.5a — order_number generator.
 *
 * Format: ACR-{YEAR}-{NNNNN} (D-2.5a-7).
 *
 * Atomicity: the lookup uses lockForUpdate() so concurrent placements
 * cannot collide on the next sequence number. Must be called from
 * within an outer transaction (CheckoutService::placeOrder wraps the
 * whole flow in DB::transaction). On a fresh year the sequence
 * resets to 1 — past-year row counts are irrelevant to the new
 * suffix.
 */
class OrderNumberService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $lastNumber = Order::query()
                ->where('order_number', 'LIKE', "ACR-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('order_number');

            $nextSeq = $lastNumber
                ? ((int) substr($lastNumber, -5)) + 1
                : 1;

            return sprintf('ACR-%d-%05d', $year, $nextSeq);
        });
    }
}
