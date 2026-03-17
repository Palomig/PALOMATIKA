<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
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

            if (!$user->onboarding_completed_at) {
                return redirect('/tg/onboarding');
            }

            $target = '/tg/dashboard';
            if ($startapp !== '') {
                $target .= '?startapp=' . rawurlencode($startapp);
            }

            return redirect($target);
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

        $user = $this->tgMiniAuth->findOrCreateUser($telegramUser);

        // Login with remember token + regenerate session for security
        Auth::login($user, true);
        $request->session()->regenerate();

        // Build redirect target
        $startParam = trim((string) ($authFields['start_param'] ?? $request->input('startParam', '')));
        if ($startParam !== '') {
            $request->session()->put('telegram_start_param', $startParam);
        }

        // Track referral for newly created users
        if ($user->wasRecentlyCreated) {
            $referrerId = null;

            if (preg_match('/^ref_(\d+)$/', $startParam, $refMatch)) {
                // ref_{user_id} — direct internal ID
                $referrerId = (int) $refMatch[1];
            } elseif (preg_match('/^ref_tg_(\d+)$/', $startParam, $refMatch)) {
                // ref_tg_{telegram_id} — lookup by Telegram oauth_id
                $referrer = User::where('oauth_provider', 'telegram')
                    ->where('oauth_id', $refMatch[1])
                    ->first();
                $referrerId = $referrer?->id;
            }

            if ($referrerId && $referrerId !== $user->id && User::where('id', $referrerId)->exists()) {
                $user->update(['referred_by_user_id' => $referrerId]);
            }
        }

        $redirectTo = !$user->onboarding_completed_at ? '/tg/onboarding' : '/tg/dashboard';
        if ($startParam !== '') {
            $redirectTo .= '?startapp=' . rawurlencode($startParam);
        }

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

                // Render onboarding inline to avoid yet another redirect hop
                if (!$user->onboarding_completed_at) {
                    return view('miniapp.onboarding', [
                        'onboardingToken' => $this->issueOnboardingToken($user->id),
                    ]);
                }

                $target = (string) ($handoff['redirect_to'] ?? '/tg/dashboard');
                return response()->view('miniapp.auth-bridge-final', ['target' => $target]);
            }
        }

        // Fallback: session cookie survived the redirect — use existing auth
        $user = Auth::user();
        if (!$user) {
            return redirect('/tg')->with('error', 'Сессия входа не сохранилась. Попробуйте ещё раз.');
        }

        $target = !$user->onboarding_completed_at ? '/tg/onboarding' : '/tg/dashboard';
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
            'name' => 'required|string|min:2|max:100',
            'grade_num' => 'required|integer|in:9',
            'grade_letter' => 'required|string|in:А,Б,В,Г,Д',
            'school_number' => 'required|string|max:20',
            'city' => 'nullable|string|max:80',
            'onboarding_token' => 'nullable|string|max:128',
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

        $user->update([
            'name' => $data['name'],
            'grade_num' => $data['grade_num'],
            'grade_letter' => $data['grade_letter'],
            'school_number' => $data['school_number'],
            'city' => $data['city'] ?: 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function switchMode(Request $request, string $role, AuditLogger $audit)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless(in_array($role, ['student', 'teacher'], true), 404);

        $request->session()->put('view_as_role', $role);

        $audit->log([
            'event_type' => 'view_as_set',
            'category' => 'admin',
            'severity' => 'info',
            'actor_user_id' => $request->user()->id,
            'actor_role' => 'admin',
            'subject_type' => 'view_as_role',
            'subject_id' => $role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => ['source' => 'miniapp'],
        ]);

        return redirect($role === 'teacher' ? '/tg/teacher/dashboard' : '/tg/dashboard');
    }
}
