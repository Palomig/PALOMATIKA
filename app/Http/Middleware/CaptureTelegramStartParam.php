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

            $targetVariantHash = $this->resolveVariantHashFromStartParam($startParam);
            if ($targetVariantHash !== null) {
                $path = trim($request->path(), '/');
                $targetPath = 'oge/' . $targetVariantHash;

                // If Telegram opens Mini App at landing with startapp, forward to actual variant page.
                if ($path === '' || $path === 'login' || $path === 'register') {
                    if ($path !== $targetPath) {
                        return redirect('/' . $targetPath);
                    }
                }
            }
        }

        return $next($request);
    }

    private function resolveVariantHashFromStartParam(string $startParam): ?string
    {
        if (preg_match('/^oge_variant_hash_([a-z0-9]{8,32})$/i', $startParam, $matches)) {
            return strtolower($matches[1]);
        }

        // Accept tolerant payload if hash was sent under oge_variant_{hash}
        if (preg_match('/^oge_variant_([a-z0-9]{8,32})$/i', $startParam, $matches) && !ctype_digit($matches[1])) {
            return strtolower($matches[1]);
        }

        return null;
    }
}
