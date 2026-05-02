<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Default 'api' bucket — applies to any route inside the 'api'
        // middleware group that doesn't override with a named limiter.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Phase 2.1 named limiters per /PHASE2_CONTRACT.md §8.
        RateLimiter::for('auth-public', fn (Request $r) =>
            Limit::perMinute(5)->by($r->ip())
        );
        RateLimiter::for('auth-verify', fn (Request $r) =>
            Limit::perMinute(10)->by($r->ip())
        );
        RateLimiter::for('cart-write', fn (Request $r) =>
            Limit::perMinute(60)->by($r->user()?->id ?: $r->ip())
        );
        RateLimiter::for('user-read', fn (Request $r) =>
            Limit::perMinute(120)->by($r->user()?->id ?: $r->ip())
        );
        RateLimiter::for('user-write', fn (Request $r) =>
            Limit::perMinute(60)->by($r->user()?->id ?: $r->ip())
        );
        RateLimiter::for('public-read', fn (Request $r) =>
            Limit::perMinute(120)->by($r->ip())
        );

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
