<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureTelegramStartParam
{
    public function handle(Request $request, Closure $next): Response
    {
        $startParam = trim((string) $request->query('startapp', ''));

        if ($startParam === '') {
            // Some Telegram clients expose tgWebAppStartParam in URL.
            $startParam = trim((string) $request->query('tgWebAppStartParam', ''));
        }

        if ($startParam !== '') {
            $request->session()->put('telegram_start_param', $startParam);
        }

        return $next($request);
    }
}
