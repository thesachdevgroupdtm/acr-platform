<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2.5a — auto-confirm pending orders older than N minutes
 * (D-2.5a-6). Default 120 minutes (2 hours). After this window the
 * customer can no longer self-cancel; that's the explicit business
 * intent — operators have committed staff/parts and a same-day
 * cancellation is a phone call to support.
 *
 * Scheduling: registered in App\Console\Kernel::schedule() with
 * everyMinute()->withoutOverlapping(60). On Hostinger production a
 * single cron entry runs `php artisan schedule:run` every minute,
 * which dispatches everything registered in the schedule.
 */
class AutoConfirmOrdersCommand extends Command
{
    protected $signature = 'orders:auto-confirm {--minutes=120}';

    protected $description = 'Auto-confirm pending orders older than N minutes (default 120).';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff  = now()->subMinutes($minutes);
        $now     = now();

        $count = Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->update([
                'status'       => Order::STATUS_CONFIRMED,
                'confirmed_at' => $now,
                'updated_at'   => $now,
            ]);

        Log::info("Auto-confirmed {$count} pending orders > {$minutes}min old.");
        $this->info("Auto-confirmed {$count} orders.");

        return self::SUCCESS;
    }
}
