<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $provider)
    {
        if (!in_array($provider, ['vkontakte'])) {
            abort(404, 'Provider not supported');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider)
    {
        if (!in_array($provider, ['vkontakte'])) {
            abort(404, 'Provider not supported');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            $this->auditLogger->log([
                'event_type' => 'oauth_login_failed',
                'category' => 'auth',
                'severity' => 'warning',
                'subject_type' => 'provider',
                'subject_id' => $provider,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return redirect()->route('login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);

        Auth::login($user, true);

        $this->auditLogger->log([
            'event_type' => 'oauth_login_success',
            'category' => 'auth',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $user->role,
            'subject_type' => 'provider',
            'subject_id' => $provider,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->intended('/dashboard');
    }

    /**
     * Find or create user from OAuth data
     */
    private function findOrCreateUser($socialUser, string $provider): User
    {
        $providerField = $provider === 'vkontakte' ? 'vk' : $provider;

        // Check if user exists with this OAuth
        $user = User::where('oauth_provider', $providerField)
            ->where('oauth_id', $socialUser->getId())
            ->first();

        if ($user) {
            return $user;
        }

        // Check if user exists with this email
        if ($socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link OAuth to existing account
                $user->update([
                    'oauth_provider' => $providerField,
                    'oauth_id' => $socialUser->getId(),
                    'avatar' => $user->avatar ?? $socialUser->getAvatar(),
                ]);

                return $user;
            }
        }

        // Create new user
        return User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'oauth_provider' => $providerField,
            'oauth_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'email_verified_at' => $socialUser->getEmail() ? now() : null,
            'trial_ends_at' => now()->addDays(7),
        ]);
    }
}
