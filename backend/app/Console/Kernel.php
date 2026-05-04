<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Phase 2.5a — auto-confirm pending orders > 2 hr old
        // (D-2.5a-6). withoutOverlapping(60) prevents pile-up if the
        // command takes longer than a minute on a slow host. The
        // command itself is idempotent — re-running it on
        // already-confirmed rows is a no-op (WHERE status='pending').
        //
        // Production cron (Hostinger): a single line runs every minute:
        //   * * * * * cd /home/<user>/public_html/backend && php artisan schedule:run >> /dev/null 2>&1
        $schedule->command('orders:auto-confirm')
            ->everyMinute()
            ->withoutOverlapping(60);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
