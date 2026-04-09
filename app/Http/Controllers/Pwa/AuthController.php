<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['vkontakte', 'google', 'yandex'];
    private const MIGRATION_SESSION_KEY = 'pwa_migration_token';

    /**
     * Determine which subdomain we're on (student|teacher).
     */
    private function appContext(Request $request): string
    {
        $host = $request->getHost();
        return str_starts_with($host, 'teacher.') ? 'teacher' : 'student';
    }

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect('https://student.' . config('app.base_domain') . '/');
        }
        return view('pwa.shared.login', ['context' => 'student']);
    }

    public function showTeacherLogin(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user && in_array($user->role, ['teacher', 'admin'], true)) {
                return redirect('https://teacher.' . config('app.base_domain') . '/dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->flash('error', 'Этот аккаунт не имеет доступа к кабинету репетитора.');

            return view('pwa.shared.login', [
                'context' => 'teacher',
            ]);
        }

        return view('pwa.shared.login', ['context' => 'teacher']);
    }

    /**
     * Redirect to OAuth provider (student subdomain).
     */
    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);
        $this->rememberMigrationToken($request);

        $callbackUrl = 'https://student.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
        return Socialite::driver($provider)->redirectUrl($callbackUrl)->redirect();
    }

    /**
     * Redirect to OAuth provider (teacher subdomain).
     */
    public function redirectTeacher(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);
        $callbackUrl = 'https://teacher.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
        return Socialite::driver($provider)->redirectUrl($callbackUrl)->redirect();
    }

    /**
     * Handle OAuth callback (student subdomain).
     */
    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        try {
            $callbackUrl = 'https://student.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
            $socialUser = Socialite::driver($provider)->redirectUrl($callbackUrl)->user();
        } catch (\Throwable $e) {
            return redirect('https://student.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $migrationToken = trim((string) $request->session()->pull(self::MIGRATION_SESSION_KEY, ''));
        $user = $this->findOrCreateUser($socialUser, $provider, $migrationToken);
        Auth::login($user, true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if ($intended && str_contains($intended, 'student.' . config('app.base_domain'))) {
            return redirect($intended);
        }

        $base = 'https://student.' . config('app.base_domain');
        return $user->onboarding_completed_at
            ? redirect($base . '/')
            : redirect($base . '/onboarding');
    }

    /**
     * Handle OAuth callback (teacher subdomain).
     */
    public function callbackTeacher(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        try {
            $callbackUrl = 'https://teacher.' . config('app.base_domain') . '/auth/' . $provider . '/callback';
            $socialUser = Socialite::driver($provider)->redirectUrl($callbackUrl)->user();
        } catch (\Throwable $e) {
            return redirect('https://teacher.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);

        if (!in_array($user->role, ['teacher', 'admin'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('https://teacher.' . config('app.base_domain') . '/login')
                ->with('error', 'Этот аккаунт не имеет доступа к кабинету репетитора.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('https://teacher.' . config('app.base_domain') . '/dashboard');
    }

    /**
     * QA login endpoint — logs in a user by email, secured by deploy secret.
     * Used exclusively for automated browser testing (Playwright/Puppeteer).
     *
     * GET /qa-login?email=qa-student@palomatika.ru&secret=<DEPLOY_WEBHOOK_SECRET>
     */
    public function qaLogin(Request $request)
    {
        $secret = config('services.deploy.webhook_secret');
        if (empty($secret) || !hash_equals($secret, (string) $request->query('secret', ''))) {
            abort(403, 'Forbidden');
        }

        $email = trim((string) $request->query('email', 'qa-student@palomatika.ru'));
        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            abort(404, "QA user not found: {$email}");
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $redirect = trim((string) $request->query('redirect', '/'));
        return redirect('https://student.' . config('app.base_domain') . $redirect);
    }

    public function logout(Request $request)
    {
        $host = $request->getHost();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('https://' . $host . '/login');
    }

    /**
     * Restore account from Telegram migration token.
     */
    public function migrateFromTelegram(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $payload = $token !== '' ? Cache::get('pwa_migration:' . $token) : null;

        return view('pwa.student.migrate', [
            'userId' => $payload['user_id'] ?? null,
            'token' => $token,
        ]);
    }

    private function rememberMigrationToken(Request $request): void
    {
        $token = trim((string) $request->query('migration_token', ''));

        if ($token !== '') {
            $request->session()->put(self::MIGRATION_SESSION_KEY, $token);
        }
    }

    private function findOrCreateUser($socialUser, string $provider, string $migrationToken = ''): User
    {
        $storedProvider = match($provider) {
            'vkontakte' => 'vk',
            default => $provider,
        };

        if ($migrationToken !== '') {
            $payload = Cache::pull('pwa_migration:' . $migrationToken);

            if (is_array($payload) && !empty($payload['user_id'])) {
                $user = User::find((int) $payload['user_id']);

                if ($user) {
                    $user->forceFill([
                        'oauth_provider' => $storedProvider,
                        'oauth_id' => (string) $socialUser->getId(),
                        'email' => $socialUser->getEmail() ?: $user->email,
                        'avatar' => $socialUser->getAvatar() ?: $user->avatar,
                    ])->save();

                    return $user;
                }
            }
        }

        $user = User::where('oauth_provider', $storedProvider)
            ->where('oauth_id', (string) $socialUser->getId())
            ->first();

        if ($user) {
            $user->update([
                'avatar' => $socialUser->getAvatar() ?? $user->avatar,
            ]);
            return $user;
        }

        return User::create([
            'name'           => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Пользователь',
            'email'          => $socialUser->getEmail(),
            'oauth_provider' => $storedProvider,
            'oauth_id'       => (string) $socialUser->getId(),
            'avatar'         => $socialUser->getAvatar(),
            'role'           => 'student',
        ]);
    }
}
