<?php

namespace App\Http\Middleware;

use App\Services\LessonSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Лок урока: ученик, вошедший в урок по коду, на время лока (60 минут,
 * либо до завершения урока / ручного release учителем) не может пользоваться
 * другими страницами student PWA — его редиректит на страницу урока.
 */
class EnforceLessonLock
{
    /** Роуты, доступные под локом (сам урок + выход из аккаунта). */
    private const ALLOWED_ROUTES = [
        'pwa.student.lessons.show',
        'pwa.student.lessons.state',
        'pwa.student.lessons.answer',
        'pwa.student.lessons.active',
        'pwa.student.lessons.join',
        'pwa.student.lessons.activity',
        'pwa.student.lessons.event',
        'pwa.student.logout',
        'pwa.student.bug-report',
    ];

    public function __construct(private readonly LessonSessionService $sessions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->isStudent()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        $lock = $this->sessions->activeLockFor($user);
        if (!$lock) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error'     => 'lesson_lock',
                'lesson_id' => $lock->lesson_session_id,
            ], 423);
        }

        return redirect()->route('pwa.student.lessons.show', ['id' => $lock->lesson_session_id]);
    }
}
