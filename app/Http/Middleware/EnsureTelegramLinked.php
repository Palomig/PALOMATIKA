<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Привязка телеграма перед анкетой у свежего аккаунта: вернувшийся ученик,
 * которого OIDC не узнал, иначе успевает «зарегистрироваться заново» до того,
 * как привязка склеит его со старым аккаунтом.
 *
 * Ученикам, уже прошедшим анкету, экран больше не всплывает (решение Стаса
 * 2026-10-14) — привязать телеграм можно и с самой страницы /link-telegram.
 * Не блокирует: есть «Напомнить позже», фоновые запросы не трогаем.
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

        if ($user->onboarding_completed_at !== null) {
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
