<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActiveAt
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->role === 'student' && $this->shouldTouch($user)) {
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }

    private function shouldTouch(User $user): bool
    {
        return $user->last_active_at === null
            || $user->last_active_at->lessThanOrEqualTo(now()->subMinute());
    }
}
