<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Экран «Подключить уведомления»: обязательный шаг для учеников, у которых
 * ещё нет настоящего telegram chat_id (вошли через Яндекс/Google или OIDC).
 */
class TelegramLinkController extends Controller
{
    public function __construct(
        private readonly TelegramLinkService $links,
    ) {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->telegram_chat_id) {
            return redirect('https://' . $request->getHost() . '/');
        }

        return view('pwa.student.link-telegram', [
            'botUsername' => (string) config('services.telegram.bot_username', 'palomatika_auth_bot'),
        ]);
    }

    /** Выдаёт одноразовый код и диплинк в бота. */
    public function start(Request $request): JsonResponse
    {
        return response()->json($this->links->issueCode($request->user()));
    }

    /** «Напомнить позже» — сутки не показываем экран. */
    public function snooze(Request $request)
    {
        $request->user()->update(['telegram_link_snoozed_until' => now()->addDay()]);

        return redirect('https://' . $request->getHost() . '/');
    }

    /**
     * Опрос со страницы: бот уже получил /start?
     *
     * Если по дороге аккаунты слились, текущая сессия могла остаться на доноре —
     * перелогиниваем в канонический, иначе ученик увидит пустой кабинет.
     */
    public function status(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));
        $result = $code !== '' ? $this->links->result($code) : null;

        // Код — секрет владельца сессии; чужой сюда не подставишь.
        if (!$result || !in_array((int) $request->user()->id, $result['participants'] ?? [], true)) {
            return response()->json(['linked' => false]);
        }

        $canonical = User::find((int) $result['user_id']);
        if ($canonical && $request->user()->id !== $canonical->id) {
            Auth::login($canonical, true);
            $request->session()->regenerate();
        }

        return response()->json([
            'linked' => true,
            'merged' => (bool) $result['merged'],
        ]);
    }
}
