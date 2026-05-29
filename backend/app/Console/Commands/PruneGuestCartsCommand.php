<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * B6 — Prune stale guest carts (D-B6-1).
 *
 * Deletes guest carts (user_id IS NULL) that haven't been touched in
 * the last N days AND have no items touched in the last N days.
 * cart_items rows cascade via the existing FK (`cart_id` → cascadeOnDelete
 * in 2026_05_03_120004_create_cart_items_table).
 *
 * Never touches user carts (user_id IS NOT NULL) — those represent
 * signed-in users and may be revived on next login. Pruning them would
 * lose work the customer expects to find.
 *
 * Idempotent: a second run with no eligible carts is a no-op (0 deletes,
 * returns SUCCESS). --dry-run produces counts only, no writes.
 *
 * Scheduling: registered in App\Console\Kernel::schedule() with
 * dailyAt('03:00'). The cron entry that runs `php artisan schedule:run`
 * once a minute (see Kernel.php docstring) dispatches it.
 */
class PruneGuestCartsCommand extends Command
{
    protected $signature = 'carts:prune {--days=14} {--dry-run}';

    protected $description = 'Delete stale guest carts (user_id NULL) older than N days with no recent items (default 14).';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $guestTotalBefore = Cart::query()->whereNull('user_id')->count();
        $userTotalBefore  = Cart::query()->whereNotNull('user_id')->count();

        $eligibleQuery = Cart::query()
            ->whereNull('user_id')
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('items', fn ($q) => $q->where('updated_at', '>=', $cutoff));

        $eligibleCount = $eligibleQuery->count();

        $this->info("Carts: {$guestTotalBefore} guest · {$userTotalBefore} user (total " . ($guestTotalBefore + $userTotalBefore) . ")");
        $this->info("Cutoff: {$cutoff->toDateTimeString()} (N={$days} days)");
        $this->info("Eligible for prune: {$eligibleCount} guest carts");

        if ($dryRun) {
            $this->warn('--dry-run set: no deletes performed.');
            return self::SUCCESS;
        }

        if ($eligibleCount === 0) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        $deleted = DB::transaction(function () use ($eligibleQuery) {
            $ids = $eligibleQuery->pluck('id')->all();
            return Cart::query()->whereIn('id', $ids)->delete();
        });

        $guestTotalAfter = Cart::query()->whereNull('user_id')->count();
        $userTotalAfter  = Cart::query()->whereNotNull('user_id')->count();

        Log::info("carts:prune deleted {$deleted} stale guest carts (N={$days}d).");
        $this->info("Deleted: {$deleted} carts.");
        $this->info("After: {$guestTotalAfter} guest · {$userTotalAfter} user (total " . ($guestTotalAfter + $userTotalAfter) . ")");

        return self::SUCCESS;
    }
}
