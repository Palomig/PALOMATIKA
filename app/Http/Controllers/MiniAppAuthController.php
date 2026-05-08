<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramMiniAppAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MiniAppAuthController extends Controller
{
    use Traits\MiniAppHelpers;

    public function __construct(
        private readonly TelegramMiniAppAuthService $tgMiniAuth,
    ) {
    }

    /**
     * Home page — session-first entrypoint for Mini App.
     * If user already has a valid session, skip landing and open target screen immediately.
     */
    public function home(Request $request)
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $startapp = trim((string) ($request->query('startapp', $request->query('tgWebAppStartParam', ''))));

            return redirect($this->tgMiniAuth->pwaLandingUrl($user, $startapp));
        }

        return view('miniapp.home');
    }

    /**
     * Server-side Telegram WebApp authentication via form POST.
     * Verifies initData HMAC, logs in user, and redirects (302).
     * This avoids the session cookie loss issue with client-side fetch + JS redirect.
     */
    public function authenticate(Request $request)
    {
        $initData = trim((string) $request->input('initData', ''));

        if ($initData === '') {
            return redirect('/tg')->with('error', 'Нет данных Telegram для входа');
        }

        try {
            [$authFields, $telegramUser] = $this->tgMiniAuth->extractAndVerify($initData);
        } catch (\Throwable $e) {
            Log::warning('tg_auth_verify_failed', [
                'reason' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return redirect('/tg')->with('error', 'Данные Telegram недействительны. Перезапустите mini app.');
        }

        try {
            $user = $this->tgMiniAuth->findOrCreateUser($telegramUser);

            // Login with remember token + regenerate session for security
            Auth::login($user, true);
            $request->session()->regenerate();

            // Build redirect target
            $startParam = trim((string) ($authFields['start_param'] ?? $request->input('startParam', '')));
            if ($startParam !== '') {
                $request->session()->put('telegram_start_param', $startParam);
            }

            // Auto-link referrals (sets referred_by_user_id + teacher_students when applicable)
            $this->tgMiniAuth->linkReferralFromStartParam($user, $startParam);

            $redirectTo = $this->tgMiniAuth->pwaLandingUrl($user, $startParam);

            // Use a one-time handoff token to survive Telegram WebView cookie quirks.
            // The auth-bridge page will navigate to /tg/auth/continue?token=... which
            // re-establishes the session if the cookie was lost during the redirect hop.
            $handoffToken = Str::random(40);
            Cache::put('tg_auth_handoff:' . $handoffToken, [
                'user_id' => $user->id,
                'redirect_to' => $redirectTo,
            ], now()->addMinutes(2));

            return response()->view('miniapp.auth-bridge', [
                'redirectTo' => $redirectTo,
                'handoffToken' => $handoffToken,
            ]);
        } catch (\Throwable $e) {
            Log::error('tg_auth_login_failed', [
                'reason' => $e->getMessage(),
                'telegram_user_id' => $telegramUser['id'] ?? null,
                'ip' => $request->ip(),
            ]);

            return redirect('/tg')->with('error', 'Не удалось завершить вход через Telegram. Попробуйте ещё раз.');
        }
    }

    public function authBridgePing(Request $request)
    {
        return response()->json(['ok' => true]);
    }

    public function authContinue(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $handoff = $token !== '' ? Cache::pull('tg_auth_handoff:' . $token) : null;

        // Primary path: restore session from handoff token (survives WebView cookie loss)
        if (is_array($handoff) && !empty($handoff['user_id'])) {
            $user = User::find((int) $handoff['user_id']);
            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();

                $target = (string) ($handoff['redirect_to'] ?? $this->tgMiniAuth->pwaLandingUrl($user));
                return response()->view('miniapp.auth-bridge-final', ['target' => $target]);
            }
        }

        // Fallback: session cookie survived the redirect — use existing auth
        $user = Auth::user();
        if (!$user) {
            return redirect('/tg')->with('error', 'Сессия входа не сохранилась. Попробуйте ещё раз.');
        }

        $startParam = trim((string) $request->session()->get('telegram_start_param', ''));
        $target = $this->tgMiniAuth->pwaLandingUrl($user, $startParam);
        return response()->view('miniapp.auth-bridge-final', ['target' => $target]);
    }

    /**
     * Onboarding form (GET).
     */
    public function onboarding(Request $request)
    {
        $user = $request->user();
        return view('miniapp.onboarding', [
            'onboardingToken' => $user ? $this->issueOnboardingToken($user->id) : null,
        ]);
    }

    /**
     * Save onboarding data (POST).
     */
    public function saveOnboarding(Request $request)
    {
        $data = $request->validate([
            'first_name'      => 'required|string|min:2|max:100|regex:/^[А-Яа-яЁёA-Za-z\-\']{2,100}$/u',
            'last_name'       => 'required|string|min:2|max:100|regex:/^[А-Яа-яЁёA-Za-z\-\']{2,100}$/u',
            'name_unverified' => 'nullable|boolean',
            'grade_num'       => 'required|integer|in:5,6,7,8,9,10,11',
            'grade_letter'    => 'required|string|in:А,Б,В,Г,Д,Е,К,М',
            'school_number'   => 'required|integer|min:1|max:9999',
            'city'            => 'nullable|string|max:80',
            'onboarding_token' => 'nullable|string|max:128',
        ], [
            'first_name.regex' => 'Имя может содержать только буквы, дефис и апостроф.',
            'last_name.regex'  => 'Фамилия может содержать только буквы, дефис и апостроф.',
        ]);

        $user = $request->user();

        // Fallback: restore auth via onboarding token if session was lost
        if (!$user && !empty($data['onboarding_token'])) {
            $payload = Cache::pull('tg_onb_token:' . $data['onboarding_token']);
            if (is_array($payload) && !empty($payload['user_id'])) {
                $user = User::find((int) $payload['user_id']);
                if ($user) {
                    Auth::login($user, true);
                    $request->session()->regenerate();
                }
            }
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $names = app(\App\Services\NameDictionaryService::class);
        $first = $names->capitalize($data['first_name']);
        $last  = $names->capitalize($data['last_name']);
        $unverifiedFlag = (bool) ($data['name_unverified'] ?? false);

        if (!$unverifiedFlag && !$names->isKnownName($first)) {
            return response()->json([
                'message' => 'Имя «' . $first . '» не найдено в списке. Если вы ввели имя правильно, отметьте «моё имя отсутствует в списке».',
                'errors' => [
                    'first_name' => ['Имя не найдено в списке.'],
                ],
            ], 422);
        }

        $user->update([
            'first_name' => $first,
            'last_name' => $last,
            'name' => trim("{$first} {$last}"),
            'name_unverified' => $unverifiedFlag,
            'grade_num' => $data['grade_num'],
            'grade_letter' => $data['grade_letter'],
            'school_number' => $data['school_number'],
            'city' => $data['city'] ?: 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

}
