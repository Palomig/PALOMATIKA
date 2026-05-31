<?php

namespace App\Http\Controllers;

use App\Services\LessonSessionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Обработчик инвайт-ссылки /lesson/join/{token}.
 *
 * Сценарии:
 * - Гость → редирект на student PWA login с intended URL.
 * - Student → joinByToken + редирект в student PWA на страницу урока.
 * - Teacher/parent/admin → 403 (не ученик).
 * - Несуществующий/завершённый токен → 404.
 */
class LessonJoinController extends Controller
{
    public function __construct(private readonly LessonSessionService $sessions)
    {
    }

    public function join(Request $request, string $token): RedirectResponse
    {
        $studentLoginUrl = 'https://student.' . config('app.base_domain') . '/login';
        $studentPwaBase  = 'https://student.' . config('app.base_domain');

        $user = $request->user();

        if (!$user) {
            // Сохраняем intended в сессии для редиректа после login
            session(['url.intended' => $request->fullUrl()]);
            return redirect()->away($studentLoginUrl);
        }

        if (($user->role ?? null) !== 'student') {
            abort(403, 'Только ученики могут присоединяться к уроку по ссылке');
        }

        try {
            $session = $this->sessions->joinByToken($token, $user);
        } catch (DomainException) {
            abort(404, 'Урок не найден или уже завершён');
        }

        return redirect()->away("{$studentPwaBase}/lessons/{$session->id}");
    }
}
