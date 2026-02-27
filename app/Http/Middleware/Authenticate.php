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
            return url('/tg/');
        }

        return route('login');
    }
}
