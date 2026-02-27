<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OgeVariant;
use App\Models\TelegramAuthToken;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TelegramBotAuthController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * Generate a new auth token and return deep link
     */
    public function generateToken(Request $request)
    {
        $startParam = trim((string) $request->input('startParam', ''));
        if ($startParam === '' && $request->hasSession()) {
            $startParam = trim((string) $request->session()->get('telegram_start_param', ''));
        }

        // Clean up expired tokens
        TelegramAuthToken::where('expires_at', '<', now())->delete();

        // Generate unique token
        $token = Str::random(32);

        TelegramAuthToken::create([
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);

        if ($startParam !== '') {
            Cache::put($this->startParamCacheKey($token), $startParam, now()->addMinutes(10));
        }

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
            $loginUrl = route('telegram.login', ['token' => $token]);
            $startParam = Cache::get($this->startParamCacheKey($token));
            if (is_string($startParam) && $startParam !== '') {
                $separator = str_contains($loginUrl, '?') ? '&' : '?';
                $loginUrl .= $separator . 'startapp=' . rawurlencode($startParam);
            }

            return response()->json([
                'status' => 'authenticated',
                'login_url' => $loginUrl,
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Perform instant auth from Telegram Mini App WebApp.initData
     */
    public function webAppLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'initData' => ['nullable', 'string'],
            'initDataUnsafe' => ['nullable', 'array'],
            'startParam' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректные данные Telegram Mini App',
                'errors' => $validator->errors(),
            ], 422);
        }

        $initData = trim((string) $request->input('initData', ''));
        $initDataUnsafe = $request->input('initDataUnsafe');

        if ($initData === '' && !is_array($initDataUnsafe)) {
            return response()->json([
                'success' => false,
                'message' => 'Отсутствуют данные Telegram Mini App',
            ], 422);
        }

        $botToken = (string) config('services.telegram.bot_token', '');
        if ($botToken === '') {
            return response()->json([
                'success' => false,
                'message' => 'Telegram bot token not configured',
            ], 503);
        }

        $authFields = [];

        try {
            [$authFields, $telegramUser] = $this->extractAndVerifyWebAppAuthData($initData, is_array($initDataUnsafe) ? $initDataUnsafe : []);
        } catch (\InvalidArgumentException $e) {
            $this->auditLogger->log([
                'event_type' => 'telegram_webapp_login_failed',
                'category' => 'auth',
                'severity' => 'warning',
                'subject_type' => 'telegram_webapp',
                'subject_id' => 'invalid_payload',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload_json' => [
                    'reason' => $e->getMessage(),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Некорректные данные Telegram Mini App',
            ], 422);
        } catch (\RuntimeException $e) {
            $this->auditLogger->log([
                'event_type' => 'telegram_webapp_login_failed',
                'category' => 'auth',
                'severity' => 'warning',
                'subject_type' => 'telegram_webapp',
                'subject_id' => (string) ($authFields['user_id'] ?? 'unknown'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload_json' => [
                    'reason' => $e->getMessage(),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Неверная подпись Telegram',
            ], 401);
        }

        $user = $this->findOrCreateTelegramUserFromProfile($telegramUser);

        Auth::login($user, true);
        $request->session()->regenerate();

        $fallbackStartParam = trim((string) $request->input('startParam', ''));
        if ($fallbackStartParam === '') {
            $fallbackStartParam = trim((string) $request->session()->get('telegram_start_param', ''));
        }

        if ($fallbackStartParam !== '' && empty($authFields['start_param'])) {
            $authFields['start_param'] = $fallbackStartParam;
        }

        $redirectTo = $this->resolvePostLoginRedirect($authFields);

        $this->auditLogger->log([
            'event_type' => 'telegram_webapp_login_success',
            'category' => 'auth',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $user->role,
            'subject_type' => 'telegram_user',
            'subject_id' => (string) ($telegramUser['id'] ?? ''),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => [
                'auth_date' => $authFields['auth_date'] ?? null,
                'query_id' => $authFields['query_id'] ?? null,
                'method' => 'telegram_webapp_init_data',
            ],
        ]);

        return response()->json([
            'success' => true,
            'redirect_to' => $redirectTo,
            'user_id' => $user->id,
        ]);
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
            $this->auditLogger->log([
                'event_type' => 'telegram_token_login_failed',
                'category' => 'auth',
                'severity' => 'warning',
                'subject_type' => 'telegram_token',
                'subject_id' => $token,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return redirect()->route('login')
                ->with('error', 'Сессия авторизации истекла. Попробуйте снова.');
        }

        // Create or find user
        $user = $this->findOrCreateUser($authToken);

        // Mark token as used
        $authToken->update(['status' => 'used']);

        // Log in the user with session
        Auth::login($user, true);

        $startParam = trim((string) request()->query('startapp', ''));
        if ($startParam === '') {
            $cachedStartParam = Cache::get($this->startParamCacheKey($token));
            if (is_string($cachedStartParam)) {
                $startParam = trim($cachedStartParam);
            }
        }
        if ($startParam === '') {
            $startParam = trim((string) request()->session()->get('telegram_start_param', ''));
        }

        Cache::forget($this->startParamCacheKey($token));
        request()->session()->forget('telegram_start_param');

        $this->auditLogger->log([
            'event_type' => 'telegram_token_login_success',
            'category' => 'auth',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $user->role,
            'subject_type' => 'telegram_token',
            'subject_id' => $token,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if ($startParam !== '') {
            $redirectTo = $this->resolvePostLoginRedirect(['start_param' => $startParam]);
            return redirect()->to($redirectTo);
        }

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

        // Send confirmation message + explicit login button fallback for Mini App flows
        $name = $from['first_name'] ?? 'пользователь';
        $loginUrl = route('telegram.login', ['token' => $token]);
        $startParam = Cache::get($this->startParamCacheKey($token));
        if (is_string($startParam) && trim($startParam) !== '') {
            $separator = str_contains($loginUrl, '?') ? '&' : '?';
            $loginUrl .= $separator . 'startapp=' . rawurlencode(trim($startParam));
        }

        $webAppBaseUrl = trim((string) config('services.telegram.webapp_base_url', ''));
        $button = [
            'text' => 'Открыть сайт после входа',
        ];

        if ($webAppBaseUrl !== '') {
            $button['web_app'] = ['url' => $loginUrl];
        } else {
            $button['url'] = $loginUrl;
        }

        $this->sendTelegramMessage(
            $from['id'],
            "✅ Вход выполнен успешно!\n\nПривет, {$name}! Если сайт не вошёл автоматически, нажмите кнопку ниже:",
            [
                'inline_keyboard' => [[$button]],
            ]
        );
    }

    /**
     * Send message via Telegram Bot API
     */
    private function sendTelegramMessage(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $botToken = config('services.telegram.bot_token');

        if (!$botToken) {
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $client->post($url, [
                'json' => $payload,
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
        return $this->findOrCreateTelegramUserFromProfile([
            'id' => $authToken->telegram_id,
            'first_name' => $authToken->first_name,
            'last_name' => $authToken->last_name,
            'username' => $authToken->username,
            'photo_url' => $authToken->photo_url,
        ]);
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function extractAndVerifyWebAppAuthData(string $initData, array $initDataUnsafe = []): array
    {
        $fields = $this->parseTelegramWebAppFields($initData, $initDataUnsafe);

        $providedHash = $fields['hash'] ?? null;
        if (!is_string($providedHash) || $providedHash === '') {
            throw new \InvalidArgumentException('Missing hash');
        }

        $signableFields = $fields;
        unset($signableFields['hash']);

        $normalizedFields = [];
        foreach ($signableFields as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $normalizedFields[(string) $key] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                continue;
            }

            $normalizedFields[(string) $key] = (string) $value;
        }

        ksort($normalizedFields);
        $dataCheckString = collect($normalizedFields)
            ->map(fn (string $value, string $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', (string) config('services.telegram.bot_token'), 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $providedHash)) {
            throw new \RuntimeException('Invalid signature');
        }

        $userValue = $fields['user'] ?? null;
        if (is_string($userValue)) {
            $decodedUser = json_decode($userValue, true);
            if (!is_array($decodedUser)) {
                throw new \InvalidArgumentException('Invalid user payload');
            }
            $userValue = $decodedUser;
        }

        if (!is_array($userValue) || empty($userValue['id'])) {
            throw new \InvalidArgumentException('Missing user payload');
        }

        return [$normalizedFields, $userValue];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseTelegramWebAppFields(string $initData, array $initDataUnsafe = []): array
    {
        if ($initData !== '') {
            parse_str($initData, $parsed);

            if (is_array($parsed) && !empty($parsed)) {
                return $parsed;
            }
        }

        if ($initDataUnsafe === []) {
            throw new \InvalidArgumentException('Missing initData');
        }

        return $initDataUnsafe;
    }

    /**
     * @param array<string, mixed> $telegramUser
     */
    private function findOrCreateTelegramUserFromProfile(array $telegramUser): User
    {
        $telegramId = (string) ($telegramUser['id'] ?? '');
        if ($telegramId === '') {
            throw new \InvalidArgumentException('Missing Telegram user id');
        }

        $user = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $telegramId)
            ->first();

        if ($user) {
            return $user;
        }

        $name = trim(((string) ($telegramUser['first_name'] ?? '')) . ' ' . ((string) ($telegramUser['last_name'] ?? '')));
        if ($name === '') {
            $name = (string) ($telegramUser['username'] ?? 'User');
        }

        return User::create([
            'name' => $name,
            'oauth_provider' => 'telegram',
            'oauth_id' => $telegramId,
            'avatar' => $telegramUser['photo_url'] ?? null,
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    /**
     * @param array<string, string> $authFields
     */
    private function resolvePostLoginRedirect(array $authFields): string
    {
        $startParam = trim((string) ($authFields['start_param'] ?? ''));

        if ($startParam !== '') {
            // Legacy payload: oge_variant_{id}
            if (preg_match('/^oge_variant_(\d+)$/', $startParam, $matches)) {
                $variantId = (int) $matches[1];
                $variant = OgeVariant::find($variantId);
                if ($variant && !empty($variant->hash)) {
                    return url('/oge/' . $variant->hash);
                }
            }

            // New payload: oge_variant_hash_{hash}
            if (preg_match('/^oge_variant_hash_([a-z0-9]{8,32})$/i', $startParam, $matches)) {
                $hash = strtolower($matches[1]);
                $variant = OgeVariant::whereRaw('LOWER(hash) = ?', [$hash])->first();
                if ($variant && !empty($variant->hash)) {
                    return url('/oge/' . $variant->hash);
                }

                // If DB lookup fails, still try direct hash URL.
                return url('/oge/' . $hash);
            }

            // Tolerant payload: oge_variant_{hash}
            if (preg_match('/^oge_variant_([a-z0-9]{8,32})$/i', $startParam, $matches)) {
                $hash = strtolower($matches[1]);
                $variant = OgeVariant::whereRaw('LOWER(hash) = ?', [$hash])->first();
                if ($variant && !empty($variant->hash)) {
                    return url('/oge/' . $variant->hash);
                }

                return url('/oge/' . $hash);
            }
        }

        // Mini App: check if user needs onboarding, redirect to /tg/ flow
        $user = auth()->user();
        if ($user) {
            // Check if request came from Mini App context (has query_id or start_param)
            $isWebApp = !empty($authFields['query_id']) || request()->is('api/auth/telegram/*');
            if ($isWebApp) {
                if (!$user->onboarding_completed_at) {
                    return url('/tg/onboarding');
                }
                return url('/tg/dashboard');
            }
        }

        return redirect()->intended('/dashboard')->getTargetUrl();
    }

    private function startParamCacheKey(string $token): string
    {
        return 'telegram_auth_start_param:' . $token;
    }
}
