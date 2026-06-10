<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\TelegramIdentityResolver;
use App\Services\TelegramOidcException;
use App\Services\TelegramOidcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramOidcController extends Controller
{
    public function __construct(
        private readonly TelegramOidcService $oidc,
        private readonly TelegramIdentityResolver $resolver,
        private readonly AuditLogger $audit,
    ) {}

    /** GET /auth/telegram/redirect?origin=student|teacher|parent|main */
    public function redirect(Request $request)
    {
        $origin = in_array($request->query('origin'), ['student', 'teacher', 'parent', 'main'], true)
            ? $request->query('origin') : 'main';
        return redirect()->away($this->oidc->buildAuthorizationUrl($origin));
    }

    /** GET /auth/telegram/callback */
    public function callback(Request $request)
    {
        if ($request->filled('error') || !$request->filled('code') || !$request->filled('state')
            || $request->query('state') !== session('tg_oidc.state')) {
            return $this->fail($request, 'state_or_error');
        }

        try {
            $claims = $this->oidc->exchangeAndVerify(
                (string) $request->query('code'),
                (string) session('tg_oidc.verifier'),
                (string) session('tg_oidc.nonce'),
            );
        } catch (TelegramOidcException $e) {
            return $this->fail($request, $e->getMessage());
        }

        $user = $this->resolver->resolve([
            'id'       => $claims['sub'],
            'username' => $claims['preferred_username'] ?? null,
            'name'     => $claims['name'] ?? null,
            'photo'    => $claims['picture'] ?? null,
        ]);

        $origin = session('tg_oidc.origin', 'main');
        $request->session()->forget(['tg_oidc.state', 'tg_oidc.nonce', 'tg_oidc.verifier', 'tg_oidc.origin']);

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->audit->log([
            'event_type' => 'telegram_oidc_login_success', 'category' => 'auth', 'severity' => 'info',
            'actor_user_id' => $user->id, 'actor_role' => $user->role,
            'subject_type' => 'provider', 'subject_id' => 'telegram',
            'ip' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return redirect()->to($this->originUrl($origin));
    }

    private function fail(Request $request, string $reason)
    {
        $this->audit->log([
            'event_type' => 'telegram_oidc_login_failed', 'category' => 'auth', 'severity' => 'warning',
            'subject_type' => 'provider', 'subject_id' => 'telegram',
            'ip' => $request->ip(), 'user_agent' => $request->userAgent(),
            'payload_json' => ['reason' => $reason],
        ]);
        $origin = session('tg_oidc.origin', 'main');
        return redirect()->to($this->originLoginUrl($origin))->with('error', 'Ошибка входа через Telegram.');
    }

    private function originUrl(string $origin): string
    {
        $base = config('app.base_domain');
        return match ($origin) {
            'student' => 'https://student.' . $base . '/',
            'teacher' => 'https://teacher.' . $base . '/dashboard',
            'parent'  => 'https://parent.' . $base . '/dashboard',
            default   => '/dashboard',
        };
    }

    private function originLoginUrl(string $origin): string
    {
        $base = config('app.base_domain');
        return match ($origin) {
            'student' => 'https://student.' . $base . '/login',
            'teacher' => 'https://teacher.' . $base . '/login',
            'parent'  => 'https://parent.' . $base . '/',
            default   => '/login',
        };
    }
}
