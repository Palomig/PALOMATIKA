<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Мягкое напоминание ученику привязать телеграм: без chat_id бот не может
 * прислать ни ДЗ, ни напоминание о сроке.
 *
 * Не блокирует: с экрана есть «Напомнить позже» (сутки тишины), а фоновые
 * запросы приложения не трогаем вовсе. Учителей и родителей это не касается.
 */
class EnsureTelegramLinked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($this->shouldRemind($user, $request)) {
            return redirect('https://' . $request->getHost() . '/link-telegram');
        }

        return $next($request);
    }

    private function shouldRemind(?object $user, Request $request): bool
    {
        if (!$user || $user->role !== 'student' || $user->telegram_chat_id !== null) {
            return false;
        }

        // Напоминаем только на обычной навигации: JSON-ручки и polling должны
        // работать, иначе «мягкое» напоминание тихо ломает страницы урока.
        if ($request->expectsJson() || !$request->isMethod('GET')) {
            return false;
        }

        $snoozedUntil = $user->telegram_link_snoozed_until;

        return $snoozedUntil === null || $snoozedUntil->isPast();
    }
}
