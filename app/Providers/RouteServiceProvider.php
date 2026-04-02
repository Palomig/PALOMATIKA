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
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('oge-variants-v1', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // PWA routes must be registered before web.php so domain-specific routes
            // take precedence over catch-all routes like Route::get('/').
            Route::middleware('web')
                ->withoutMiddleware([\App\Http\Middleware\CaptureTelegramStartParam::class])
                ->group(base_path('routes/pwa.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
