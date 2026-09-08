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
        // VPR services — grade-specific, bound with factory
        $this->app->bind(\App\Services\VprTaskDataService::class, function ($app, $params) {
            $grade = $params['grade'] ?? (auth()->user()?->grade_num ?? 5);
            return new \App\Services\VprTaskDataService((int) $grade);
        });
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
