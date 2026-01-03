<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ReferralClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $provider): JsonResponse
    {
        if (!in_array($provider, ['vkontakte', 'telegram'])) {
            return response()->json(['message' => 'Неподдерживаемый провайдер'], 400);
        }

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['redirect_url' => $url]);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['vkontakte', 'telegram'])) {
            return response()->json(['message' => 'Неподдерживаемый провайдер'], 400);
        }

        try {
            if ($provider === 'telegram') {
                $oauthUser = $this->handleTelegramCallback($request);
            } else {
                $oauthUser = Socialite::driver($provider)->stateless()->user();
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка авторизации: ' . $e->getMessage()
            ], 400);
        }

        $providerName = $provider === 'vkontakte' ? 'vk' : $provider;

        // Find existing user
        $user = User::where('oauth_provider', $providerName)
            ->where('oauth_id', $oauthUser->getId())
            ->first();

        if (!$user) {
            // Check referral code from session/cookie
            $referrer = null;
            $referralCode = $request->cookie('referral_code') ?? $request->input('referral_code');
            if ($referralCode) {
                $referrer = User::where('referral_code', $referralCode)->first();

                // Track referral conversion
                ReferralClick::where('referral_code', $referralCode)
                    ->whereNull('registered_user_id')
                    ->orderByDesc('clicked_at')
                    ->first()
                    ?->update(['registered_user_id' => null]); // Will update after user creation
            }

            // Create new user
            $user = User::create([
                'name' => $oauthUser->getName() ?? $oauthUser->getNickname() ?? 'Пользователь',
                'email' => $oauthUser->getEmail(),
                'password' => Hash::make(Str::random(32)),
                'oauth_provider' => $providerName,
                'oauth_id' => $oauthUser->getId(),
                'avatar' => $oauthUser->getAvatar(),
                'role' => 'student',
                'referred_by_user_id' => $referrer?->id,
                'trial_ends_at' => now()->addDays(3),
            ]);

            // Update referral click
            if ($referralCode) {
                ReferralClick::where('referral_code', $referralCode)
                    ->whereNull('registered_user_id')
                    ->orderByDesc('clicked_at')
                    ->first()
                    ?->update(['registered_user_id' => $user->id]);
            }
        } else {
            // Update existing user info
            $user->update([
                'name' => $oauthUser->getName() ?? $user->name,
                'avatar' => $oauthUser->getAvatar() ?? $user->avatar,
                'last_active_at' => now(),
            ]);
        }

        $token = $user->createToken('oauth')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'is_new_user' => $user->wasRecentlyCreated,
        ]);
    }

    /**
     * Handle Telegram Login Widget callback
     */
    private function handleTelegramCallback(Request $request)
    {
        $data = $request->all();

        // Verify Telegram hash
        $botToken = config('services.telegram.client_secret');
        $checkHash = $data['hash'] ?? null;
        unset($data['hash']);

        ksort($data);
        $dataCheckString = implode("\n", array_map(
            fn($k, $v) => "$k=$v",
            array_keys($data),
            $data
        ));

        $secretKey = hash('sha256', $botToken, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if ($hash !== $checkHash) {
            throw new \Exception('Неверная подпись Telegram');
        }

        // Check auth_date (valid for 24 hours)
        if (time() - ($data['auth_date'] ?? 0) > 86400) {
            throw new \Exception('Авторизация устарела');
        }

        return new class($data) {
            private array $data;

            public function __construct(array $data) {
                $this->data = $data;
            }

            public function getId() {
                return $this->data['id'];
            }

            public function getName() {
                $name = $this->data['first_name'] ?? '';
                if (!empty($this->data['last_name'])) {
                    $name .= ' ' . $this->data['last_name'];
                }
                return $name;
            }

            public function getNickname() {
                return $this->data['username'] ?? null;
            }

            public function getEmail() {
                return null; // Telegram doesn't provide email
            }

            public function getAvatar() {
                return $this->data['photo_url'] ?? null;
            }
        };
    }

    /**
     * Track referral click
     */
    public function trackReferral(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|exists:users,referral_code',
        ]);

        ReferralClick::create([
            'referral_code' => $request->code,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Клик зарегистрирован']);
    }
}
