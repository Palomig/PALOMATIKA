<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TelegramAuthToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TelegramBotAuthController extends Controller
{
    /**
     * Generate a new auth token and return deep link
     */
    public function generateToken(Request $request)
    {
        // Clean up expired tokens
        TelegramAuthToken::where('expires_at', '<', now())->delete();

        // Generate unique token
        $token = Str::random(32);

        TelegramAuthToken::create([
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);

        $botUsername = config('services.telegram.bot_username');
        $deepLink = "https://t.me/{$botUsername}?start={$token}";

        return response()->json([
            'token' => $token,
            'deep_link' => $deepLink,
            'expires_in' => 300, // 5 minutes
        ]);
    }

    /**
     * Check auth token status (API - just returns status, no login)
     */
    public function checkToken(Request $request, string $token)
    {
        $authToken = TelegramAuthToken::where('token', $token)->first();

        if (!$authToken) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($authToken->isExpired()) {
            return response()->json(['status' => 'expired']);
        }

        if ($authToken->isAuthenticated()) {
            // Return authenticated status with login URL
            // Frontend will redirect to this URL for actual login
            return response()->json([
                'status' => 'authenticated',
                'login_url' => route('telegram.login', ['token' => $token]),
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Perform actual login (Web route with session)
     */
    public function login(string $token)
    {
        $authToken = TelegramAuthToken::where('token', $token)
            ->where('status', 'authenticated')
            ->first();

        if (!$authToken || $authToken->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'Сессия авторизации истекла. Попробуйте снова.');
        }

        // Create or find user
        $user = $this->findOrCreateUser($authToken);

        // Mark token as used
        $authToken->update(['status' => 'used']);

        // Log in the user with session
        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }

    /**
     * Handle Telegram bot webhook
     */
    public function webhook(Request $request)
    {
        $update = $request->all();

        \Log::info('Telegram webhook received', $update);

        // Verify webhook secret (optional but recommended)
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $expectedSecret = config('services.telegram.webhook_secret');

        if ($expectedSecret && $secretToken !== $expectedSecret) {
            \Log::warning('Invalid webhook secret');
            return response()->json(['ok' => false]);
        }

        // Handle /start command with token
        if (isset($update['message']['text'])) {
            $text = $update['message']['text'];
            $from = $update['message']['from'] ?? null;

            if (preg_match('/^\/start\s+(.+)$/', $text, $matches)) {
                $token = $matches[1];
                $this->handleStartCommand($token, $from);
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle /start command with auth token
     */
    private function handleStartCommand(string $token, ?array $from): void
    {
        if (!$from) {
            return;
        }

        $authToken = TelegramAuthToken::pending()
            ->where('token', $token)
            ->first();

        if (!$authToken) {
            \Log::info('Token not found or expired', ['token' => $token]);
            // Send message to user that token is invalid
            $this->sendTelegramMessage(
                $from['id'],
                "❌ Ссылка для входа устарела или недействительна.\n\nПожалуйста, вернитесь на сайт и попробуйте снова."
            );
            return;
        }

        // Update token with user data
        $authToken->update([
            'telegram_id' => $from['id'],
            'first_name' => $from['first_name'] ?? null,
            'last_name' => $from['last_name'] ?? null,
            'username' => $from['username'] ?? null,
            'status' => 'authenticated',
        ]);

        \Log::info('Token authenticated', [
            'token' => $token,
            'telegram_id' => $from['id'],
        ]);

        // Send confirmation message
        $name = $from['first_name'] ?? 'пользователь';
        $this->sendTelegramMessage(
            $from['id'],
            "✅ Вход выполнен успешно!\n\nПривет, {$name}! Теперь вернитесь в браузер — вы будете автоматически перенаправлены на сайт."
        );
    }

    /**
     * Send message via Telegram Bot API
     */
    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $botToken = config('services.telegram.bot_token');

        if (!$botToken) {
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            $client = new \GuzzleHttp\Client();
            $client->post($url, [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ],
                'timeout' => 5,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find or create user from auth token data
     */
    private function findOrCreateUser(TelegramAuthToken $authToken): User
    {
        // Check if user exists with this Telegram ID
        $user = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $authToken->telegram_id)
            ->first();

        if ($user) {
            return $user;
        }

        // Create new user
        $name = trim(($authToken->first_name ?? '') . ' ' . ($authToken->last_name ?? ''));
        if (empty($name)) {
            $name = $authToken->username ?? 'User';
        }

        return User::create([
            'name' => $name,
            'oauth_provider' => 'telegram',
            'oauth_id' => $authToken->telegram_id,
            'avatar' => $authToken->photo_url,
            'trial_ends_at' => now()->addDays(7),
        ]);
    }
}
