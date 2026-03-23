<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramMiniAppAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ParentAuthController extends Controller
{
    public function __construct(
        private readonly TelegramMiniAppAuthService $tgMiniAuth,
    ) {}

    public function home(Request $request)
    {
        if (Auth::check() && Auth::user()->isParent()) {
            return redirect('/parent/dashboard');
        }

        return view('parent.home');
    }

    /**
     * Авторизация родителя через Telegram Mini App initData.
     * Использует токен РОДИТЕЛЬСКОГО бота (настраивается отдельно).
     */
    public function authenticate(Request $request)
    {
        $initData = trim((string) $request->input('initData', ''));

        if ($initData === '') {
            return redirect('/parent')->with('error', 'Нет данных Telegram');
        }

        try {
            // Верификация через токен родительского бота
            [$authFields, $telegramUser] = $this->tgMiniAuth->extractAndVerify(
                $initData,
                config('services.telegram.parent_bot_token')
            );
        } catch (\Throwable $e) {
            return redirect('/parent')->with('error', 'Данные Telegram недействительны');
        }

        // Найти или создать пользователя-родителя
        $user = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $telegramUser['id'])
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? '')),
                'oauth_provider' => 'telegram',
                'oauth_id' => $telegramUser['id'],
                'tg_username' => $telegramUser['username'] ?? null,
                'role' => 'parent',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $handoffToken = Str::random(40);
        Cache::put('parent_auth_handoff:' . $handoffToken, [
            'user_id' => $user->id,
        ], now()->addMinutes(2));

        return response()->view('parent.auth-bridge', [
            'redirectTo' => '/parent/dashboard',
            'handoffToken' => $handoffToken,
        ]);
    }

    public function authContinue(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $handoff = $token !== '' ? Cache::pull('parent_auth_handoff:' . $token) : null;

        if (is_array($handoff) && !empty($handoff['user_id'])) {
            $user = User::find((int) $handoff['user_id']);
            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();
            }
        }

        if (!Auth::check()) {
            return redirect('/parent')->with('error', 'Сессия истекла');
        }

        return redirect('/parent/dashboard');
    }
}
