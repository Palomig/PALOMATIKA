<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production — hosting terminates SSL at the reverse proxy,
        // so Laravel sees HTTP. Without this, Secure cookies won't work.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
