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
            $hasProfile = !empty($user->name)
                && !empty($user->first_name)
                && !empty($user->last_name)
                && !empty($user->grade_num)
                && !empty($user->grade_letter)
                && !empty($user->school_number)
                && !empty($user->city);

            if ($hasProfile) {
                $user->forceFill(['onboarding_completed_at' => now()])->save();
            } elseif (!$request->is('tg/onboarding*')) {
                return redirect('/tg/onboarding');
            }
        }

        return $next($request);
    }
}
