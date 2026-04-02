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
            return redirect('http://student.' . config('app.base_domain') . '/');
        }
        return view('pwa.shared.login', ['context' => 'student']);
    }

    public function showTeacherLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect('http://teacher.' . config('app.base_domain') . '/dashboard');
        }
        return view('pwa.shared.login', ['context' => 'teacher']);
    }

    /**
     * Redirect to OAuth provider (student subdomain).
     */
    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);
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
            return redirect('http://student.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);
        Auth::login($user, true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        if ($intended && str_contains($intended, 'student.' . config('app.base_domain'))) {
            return redirect($intended);
        }

        $base = 'http://student.' . config('app.base_domain');
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
            return redirect('http://teacher.' . config('app.base_domain') . '/login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('http://teacher.' . config('app.base_domain') . '/dashboard');
    }

    public function logout(Request $request)
    {
        $host = $request->getHost();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('http://' . $host . '/login');
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

    private function findOrCreateUser($socialUser, string $provider): User
    {
        // Normalise provider name: Socialite uses 'vkontakte', store as 'vk'
        $storedProvider = match($provider) {
            'vkontakte' => 'vk',
            default => $provider,
        };

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
