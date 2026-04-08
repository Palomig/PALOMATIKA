<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Restrict access by user role.
     *
     * Usage: ->middleware('role:teacher,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Admin always passes any role check — view_as_role only affects UI rendering, not API access.
        if ($user->role === 'admin') {
            return $next($request);
        }

        if (empty($roles) || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403);
    }
}
