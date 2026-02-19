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

        // Admin can switch view mode to simulate teacher/student permissions.
        $effectiveRole = $user->role;
        if ($user->role === 'admin') {
            $viewAs = $request->hasSession() ? $request->session()->get('view_as_role') : null;
            if (in_array($viewAs, ['student', 'teacher'], true)) {
                $effectiveRole = $viewAs;
            }
        }

        if (empty($roles) || in_array($effectiveRole, $roles, true)) {
            return $next($request);
        }

        abort(403);
    }
}
