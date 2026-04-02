<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePwaOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->onboarding_completed_at) {
            $hasProfile = !empty($user->name)
                && !empty($user->grade_num)
                && !empty($user->grade_letter)
                && !empty($user->school_number)
                && !empty($user->city);

            if ($hasProfile) {
                $user->forceFill(['onboarding_completed_at' => now()])->save();
            } else {
                // Redirect to onboarding on same subdomain
                $host = $request->getHost();
                return redirect('http://' . $host . '/onboarding');
            }
        }

        return $next($request);
    }
}
