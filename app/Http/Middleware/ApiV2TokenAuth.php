<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ApiV2TokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = trim($m[1]);
        $payload = Cache::get('tma_v2_access:' . $token);
        if (!is_array($payload) || empty($payload['user_id'])) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::find((int) $payload['user_id']);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
