<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->isMethod('GET') && $request->hasSession()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        // Mini App routes: redirect to /tg/ home (not /login which hangs in WebView)
        if ($request->is('tg/*') || $request->is('tg')) {
            return url('/tg');
        }

        // PWA subdomain routes — redirect to /login on same subdomain
        $host = $request->getHost();
        $baseDomain = config('app.base_domain', 'palomatika.ru');
        if (str_ends_with($host, '.' . $baseDomain)) {
            return 'http://' . $host . '/login';
        }

        return route('login');
    }
}
