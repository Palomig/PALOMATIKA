<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class RequireVerifiedPhone
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('services.zvonok.id') || !config('services.zvonok.key')
            || $request->is('phone-login', 'logout', 'api/auth/logout', 'sw.js', 'manifest.json')) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user || DB::table('phone_login_numbers')->where('user_id', $user->id)->exists()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'phone_verification_required',
                'verification_url' => '/phone-login',
            ], 403)->header('Cache-Control', 'no-store');
        }

        return redirect('/phone-login', 303)->header('Cache-Control', 'no-store');
    }
}
