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
            // Auto-heal legacy users: if profile fields are already filled, mark onboarding as completed.
            $hasProfile = !empty($user->name)
                && !empty($user->grade_num)
                && !empty($user->grade_letter)
                && !empty($user->school_number)
                && !empty($user->city);

            if ($hasProfile) {
                $user->forceFill(['onboarding_completed_at' => now()])->save();
            } else {
                // Don't redirect if already on onboarding page
                if (!$request->is('tg/onboarding*')) {
                    return redirect('/tg/onboarding');
                }
            }
        }

        return $next($request);
    }
}
