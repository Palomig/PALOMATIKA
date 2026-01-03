<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $provider)
    {
        if ($provider === 'telegram') {
            // Telegram uses widget, not redirect
            abort(400, 'Use Telegram Login Widget');
        }

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
            return redirect()->route('login')
                ->with('error', 'Ошибка авторизации. Попробуйте ещё раз.');
        }

        $user = $this->findOrCreateUser($socialUser, $provider);

        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }

    /**
     * Handle Telegram Login Widget callback
     */
    public function telegramCallback(Request $request)
    {
        \Log::info('Telegram callback received', $request->all());

        $authData = $request->only([
            'id', 'first_name', 'last_name', 'username',
            'photo_url', 'auth_date', 'hash'
        ]);

        \Log::info('Telegram auth data', $authData);

        if (!$this->verifyTelegramAuth($authData)) {
            \Log::error('Telegram auth verification failed');
            return redirect()->route('login')
                ->with('error', 'Ошибка авторизации Telegram.');
        }

        \Log::info('Telegram auth verified successfully');

        $user = $this->findOrCreateTelegramUser($authData);

        \Log::info('User found/created', ['user_id' => $user->id]);

        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }

    /**
     * Verify Telegram auth data
     */
    private function verifyTelegramAuth(array $authData): bool
    {
        if (!isset($authData['hash'])) {
            return false;
        }

        $checkHash = $authData['hash'];
        unset($authData['hash']);

        // Check auth_date (not older than 1 day)
        if (time() - $authData['auth_date'] > 86400) {
            return false;
        }

        $dataCheckArr = [];
        foreach ($authData as $key => $value) {
            if ($value !== null) {
                $dataCheckArr[] = $key . '=' . $value;
            }
        }
        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash('sha256', config('services.telegram.bot_token'), true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($hash, $checkHash);
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

    /**
     * Find or create user from Telegram data
     */
    private function findOrCreateTelegramUser(array $authData): User
    {
        // Check if user exists with this Telegram ID
        $user = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $authData['id'])
            ->first();

        if ($user) {
            // Update avatar if changed
            if (isset($authData['photo_url']) && $user->avatar !== $authData['photo_url']) {
                $user->update(['avatar' => $authData['photo_url']]);
            }
            return $user;
        }

        // Create new user
        $name = trim(($authData['first_name'] ?? '') . ' ' . ($authData['last_name'] ?? ''));
        if (empty($name)) {
            $name = $authData['username'] ?? 'User';
        }

        return User::create([
            'name' => $name,
            'oauth_provider' => 'telegram',
            'oauth_id' => $authData['id'],
            'avatar' => $authData['photo_url'] ?? null,
            'trial_ends_at' => now()->addDays(7),
        ]);
    }
}
