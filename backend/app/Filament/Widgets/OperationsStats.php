<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Phase 4.2 — OperationsStats dashboard widget.
 *
 * Four KPI tiles for the daily admin scan, polled every 60s
 * (D-4.2-12) so the dashboard stays fresh without hammering
 * the DB.
 *
 * Query plans (expected on production data volume):
 *   - Pending Orders     : uses orders_status_created_at_index
 *                          (status = 'pending') — index range
 *                          scan, < 5 ms.
 *   - Today's Bookings   : whereDate('created_at', today())
 *                          — full table scan today only;
 *                          MySQL converts to BETWEEN if the
 *                          column has an index on created_at.
 *                          Currently relies on the compound
 *                          status+created_at index for partial
 *                          range. If volume grows, consider a
 *                          standalone created_at index.
 *   - This Week's Revenue: same compound index, narrowed to
 *                          status='completed' AND week range.
 *                          < 20 ms typical.
 *   - Active Customers   : EXISTS subquery on orders.user_id
 *                          AND orders.created_at >= 30 days.
 *                          Uses orders_user_id_status_index +
 *                          users PK. < 50 ms on ~10K users.
 */
class OperationsStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $pending = Order::where('status', 'pending')->count();

        $today = Order::whereDate('created_at', today())->count();

        $weekRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->sum('total');

        $activeCustomers = User::whereHas('orders', function ($q) {
            $q->where('created_at', '>=', now()->subDays(30));
        })->count();

        return [
            Stat::make('Pending Orders', (string) $pending)
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => ['status' => ['values' => ['pending']]],
                ])),

            Stat::make("Today's Bookings", (string) $today)
                ->description('Created today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make("This Week's Revenue", '₹' . number_format((float) $weekRevenue))
                ->description('Completed orders this week')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Active Customers', (string) $activeCustomers)
                ->description('Ordered in last 30 days')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
