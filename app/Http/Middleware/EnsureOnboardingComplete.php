<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->onboarding_completed_at) {
            // Don't redirect if already on onboarding page
            if (!$request->is('tg/onboarding*')) {
                return redirect('/tg/onboarding');
            }
        }

        return $next($request);
    }
}
