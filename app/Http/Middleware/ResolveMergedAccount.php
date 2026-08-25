<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Переводит сессию, оставшуюся на слитом аккаунте, на канонический.
 *
 * Слияние дублей (см. {@see \App\Services\AccountMergeService}) запускается из
 * вебхука бота или из консоли — то есть в чужом процессе, который до куки
 * браузера не дотягивается. Донор при этом обнуляется: oauth-ключи, chat_id и
 * почта снимаются, все данные уезжают на канонического. Ученик, который в этот
 * момент сидит в приложении, продолжает ходить под донором и видит пустой
 * кабинет — «мой старый аккаунт пропал».
 *
 * Страница привязки умеет перелогинивать сама (TelegramLinkController::status),
 * но только пока открыта и опрашивает статус: ученик уходит в бота, возвращается
 * не туда или закрывает вкладку — и опрос не срабатывает. Поэтому чиним здесь,
 * на первом же запросе такой сессии, независимо от того, как случилось слияние.
 */
class ResolveMergedAccount
{
    /** Страховка от кольца в merged_into_id: нормальная цепочка — один шаг. */
    private const MAX_HOPS = 5;

    public function __construct(
        private readonly AuditLogger $audit,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User || $user->merged_into_id === null) {
            return $next($request);
        }

        $canonical = $this->canonicalFor($user);

        if ($canonical === null) {
            // Донор указывает в никуда (аккаунт удалён или цепочка закольцована) —
            // оставаться в такой сессии нельзя, она гарантированно пустая.
            // Своего редиректа не строим: дальше по стеку `auth` сам отправит
            // гостя на нужный /login — у каждого поддомена он свой.
            Auth::logout();

            return $next($request);
        }

        Auth::login($canonical, true);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->audit->log([
            'event_type'    => 'merged_session_redirected',
            'category'      => 'auth',
            'severity'      => 'info',
            'actor_user_id' => $canonical->id,
            'actor_role'    => $canonical->role,
            'subject_type'  => 'user',
            'subject_id'    => $user->id,
            'ip'            => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'payload_json'  => ['from_user_id' => $user->id, 'to_user_id' => $canonical->id],
        ]);

        return $next($request);
    }

    /**
     * Идёт по цепочке merged_into_id до живого аккаунта.
     */
    private function canonicalFor(User $user): ?User
    {
        $seen = [$user->id => true];
        $current = $user;

        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            $nextId = $current->merged_into_id;

            if ($nextId === null) {
                return $current->is($user) ? null : $current;
            }

            if (isset($seen[$nextId])) {
                return null;
            }

            $seen[$nextId] = true;
            $next = User::find($nextId);

            if ($next === null) {
                return null;
            }

            $current = $next;
        }

        return null;
    }
}
