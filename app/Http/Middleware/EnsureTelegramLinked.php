<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ученик обязан привязать телеграм: без chat_id бот не может прислать ни ДЗ,
 * ни напоминание о сроке. Учителей и родителей не трогаем.
 */
class EnsureTelegramLinked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'student' && $user->telegram_chat_id === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'telegram_link_required',
                    'link_url' => 'https://' . $request->getHost() . '/link-telegram',
                ], 428);
            }

            return redirect('https://' . $request->getHost() . '/link-telegram');
        }

        return $next($request);
    }
}
