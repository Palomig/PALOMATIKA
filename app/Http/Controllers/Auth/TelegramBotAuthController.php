<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OgeVariant;
use App\Models\TelegramAuthToken;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OgeVariantPoolService;
use App\Services\TelegramMiniAppAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TelegramBotAuthController extends Controller
{
    public function sessionCheck(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'authenticated' => (bool) $user,
            'user_id' => $user?->id,
            'onboarding_completed' => (bool) $user?->onboarding_completed_at,
        ]);
    }

    public function diag(Request $request)
    {
        $payload = $request->validate([
            'trace_id' => ['nullable', 'string', 'max:120'],
            'stage' => ['nullable', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:80'],
            'platform' => ['nullable', 'string', 'max:40'],
            'version' => ['nullable', 'string', 'max:40'],
            'init_len' => ['nullable', 'integer'],
            'has_hash' => ['nullable', 'boolean'],
            'has_signature' => ['nullable', 'boolean'],
            'path' => ['nullable', 'string', 'max:50'],
            'extra' => ['nullable', 'array'],
        ]);

        Log::info('tg_client_diag', [
            'trace_id' => $payload['trace_id'] ?? null,
            'stage' => $payload['stage'] ?? null,
            'code' => $payload['code'] ?? null,
            'platform' => $payload['platform'] ?? null,
            'version' => $payload['version'] ?? null,
            'init_len' => $payload['init_len'] ?? null,
            'has_hash' => $payload['has_hash'] ?? null,
            'has_signature' => $payload['has_signature'] ?? null,
            'path' => $payload['path'] ?? null,
            'extra' => $payload['extra'] ?? null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OgeVariantPoolService $variantPool,
        private readonly TelegramMiniAppAuthService $tgMiniAuth,
    ) {
    }

    /**
     * Generate a new auth token and return deep link
     */
    public function generateToken(Request $request)
    {
        $traceId = trim((string) $request->input('trace_id', ''));
        if ($traceId === '') {
            $traceId = trim((string) $request->query('trace_id', ''));
        }

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

        Log::info('tg_auth_fallback_token_created', [
            'trace_id' => $traceId !== '' ? $traceId : null,
            'token' => $token,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'has_startParam' => $startParam !== '',
        ]);

        return response()->json([
            'token' => $token,
            'deep_link' => $deepLink,
            'expires_in' => 300, // 5 minutes
            'trace_id' => $traceId !== '' ? $traceId : null,
        ]);
    }

    /**
     * Check auth token status (API - just returns status, no login)
     */
    public function checkToken(Request $request, string $token)
    {
        $traceId = trim((string) $request->query('trace_id', ''));

        $authToken = TelegramAuthToken::where('token', $token)->first();

        if (!$authToken) {
            Log::warning('tg_auth_check_not_found', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'token' => $token,
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => 'not_found']);
        }

        if ($authToken->isExpired()) {
            Log::warning('tg_auth_check_expired', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'token' => $token,
                'ip' => $request->ip(),
            ]);
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
            if ($traceId !== '') {
                $separator = str_contains($loginUrl, '?') ? '&' : '?';
                $loginUrl .= $separator . 'trace_id=' . rawurlencode($traceId);
            }

            Log::info('tg_auth_check_authenticated', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'token' => $token,
                'ip' => $request->ip(),
            ]);

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
        $traceId = trim((string) $request->input('trace_id', ''));

        Log::info('tg_webapp_login_attempt', [
            'trace_id' => $traceId !== '' ? $traceId : null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'has_initData' => trim((string) $request->input('initData', '')) !== '',
            'has_initDataUnsafe' => is_array($request->input('initDataUnsafe')),
            'has_startParam' => trim((string) $request->input('startParam', '')) !== '',
        ]);

        $validator = Validator::make($request->all(), [
            'initData' => ['nullable', 'string'],
            'initDataUnsafe' => ['nullable', 'array'],
            'startParam' => ['nullable', 'string'],
            'trace_id' => ['nullable', 'string'],
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

            Log::warning('tg_webapp_login_invalid_payload', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'reason' => $e->getMessage(),
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

            Log::warning('tg_webapp_login_signature_failed', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'reason' => $e->getMessage(),
                'user_id_hint' => (string) ($authFields['user_id'] ?? 'unknown'),
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

        Log::info('tg_webapp_login_success', [
            'trace_id' => $traceId !== '' ? $traceId : null,
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'redirect_to' => $redirectTo,
        ]);

        return response()->json([
            'success' => true,
            'redirect_to' => $redirectTo,
            'user_id' => $user->id,
            'trace_id' => $traceId !== '' ? $traceId : null,
        ]);
    }

    /**
     * Perform actual login (Web route with session)
     */
    public function login(Request $request, string $token)
    {
        $traceId = trim((string) $request->query('trace_id', ''));

        $authToken = TelegramAuthToken::where('token', $token)
            ->where('status', 'authenticated')
            ->first();

        if (!$authToken || $authToken->isExpired()) {
            Log::warning('tg_token_login_failed', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'token' => $token,
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);

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
            Log::info('tg_token_login_success', [
                'trace_id' => $traceId !== '' ? $traceId : null,
                'token' => $token,
                'user_id' => $user->id,
                'redirect_to' => $redirectTo,
            ]);
            return redirect()->to($redirectTo);
        }

        $fallbackRedirect = $user->onboarding_completed_at ? '/tg/dashboard' : '/tg/onboarding';
        Log::info('tg_token_login_success', [
            'trace_id' => $traceId !== '' ? $traceId : null,
            'token' => $token,
            'user_id' => $user->id,
            'redirect_to' => $fallbackRedirect,
        ]);

        return redirect()->to($fallbackRedirect);
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

        // Handle callback buttons (battle creation)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return response()->json(['ok' => true]);
        }

        // Handle text commands
        if (isset($update['message']['text'])) {
            $text = trim((string) $update['message']['text']);
            $from = $update['message']['from'] ?? null;

            if (preg_match('/^\/start\s+(.+)$/', $text, $matches)) {
                $param = $matches[1];
                // Variant/battle deep links should open mini-app, not auth-token flow
                if (preg_match('/^oge_variant_hash_[a-z0-9]{8,32}$/i', $param) || preg_match('/^oge_variant_[a-z0-9]{8,32}$/i', $param)) {
                    $this->sendMiniAppOpenMessage($from, $param);
                // Skip referral links — don't treat them as auth tokens
                } elseif (str_starts_with($param, 'ref_')) {
                    $this->sendWelcomeMessage($from);
                } else {
                    $this->handleStartCommand($param, $from);
                }
            } elseif ($text === '/start') {
                $this->sendWelcomeMessage($from);
            } elseif ($text === '/battle' || $text === '/battle_create') {
                $this->sendBattleModeButtons($from);
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Send welcome message for plain /start or referral links.
     */
    private function sendWelcomeMessage(?array $from): void
    {
        if (!$from) {
            return;
        }

        $name = $from['first_name'] ?? 'друг';

        $keyboard = [[
            [
                'text' => '🚀 Открыть palomatika',
                'web_app' => ['url' => url('/tg')],
            ],
        ]];

        if ($this->isBattleAdmin($from)) {
            $keyboard[] = [[
                'text' => '⚔️ Создать батл-вариант',
                'callback_data' => 'battle_menu',
            ]];
        }

        $this->sendTelegramMessage(
            $from['id'],
            "Привет, {$name}! 👋\n\nЯ бот palomatika — помогаю готовиться к ОГЭ по математике.\n\nОткрой мини-приложение, чтобы начать тренировку!",
            ['inline_keyboard' => $keyboard]
        );
    }

    private function isBattleAdmin(?array $from): bool
    {
        $allowedTelegramId = (string) env('BATTLE_ADMIN_TELEGRAM_ID', '245710727');
        $fromId = (string) ($from['id'] ?? '');

        return $fromId !== '' && $fromId === $allowedTelegramId;
    }

    private function sendMiniAppOpenMessage(?array $from, string $startParam): void
    {
        if (!$from || empty($from['id'])) {
            return;
        }

        // Open dashboard directly so startapp payload is handled immediately (battle jump),
        // instead of landing on generic miniapp home.
        $openUrl = url('/tg/dashboard?startapp=' . rawurlencode($startParam));
        $this->sendTelegramMessage(
            (string) $from['id'],
            "Открой вариант в мини-приложении:",
            [
                'inline_keyboard' => [[
                    [
                        'text' => '🚀 Открыть вариант',
                        'web_app' => ['url' => $openUrl],
                    ],
                ]],
            ]
        );
    }

    private function sendBattleModeButtons(?array $from): void
    {
        if (!$from) {
            return;
        }

        if (!$this->isBattleAdmin($from)) {
            $this->sendTelegramMessage((string) $from['id'], '⛔️ Эта команда доступна только администратору.');
            return;
        }

        $this->sendTelegramMessage(
            (string) $from['id'],
            "Выбери тип варианта для батла:",
            [
                'inline_keyboard' => [
                    [[ 'text' => '🎯 Смешанный мини-вариант', 'callback_data' => 'battle_create_mixed' ]],
                    [[ 'text' => '📘 Полный вариант', 'callback_data' => 'battle_create_full' ]],
                ],
            ]
        );
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $data = (string) ($callbackQuery['data'] ?? '');
        $from = $callbackQuery['from'] ?? null;
        $chatId = (string) ($callbackQuery['message']['chat']['id'] ?? '');
        $callbackId = (string) ($callbackQuery['id'] ?? '');

        if ($data === '') {
            return;
        }

        if ($data === 'battle_menu') {
            $this->answerCallbackQuery($callbackId);
            $this->sendBattleModeButtons($from);
            return;
        }

        if (!in_array($data, ['battle_create_mixed', 'battle_create_full'], true)) {
            return;
        }

        if (!$this->isBattleAdmin($from)) {
            $this->answerCallbackQuery($callbackId, 'Недостаточно прав', true);
            if ($chatId !== '') {
                $this->sendTelegramMessage($chatId, '⛔️ Создавать батл-варианты может только Стас.');
            }
            return;
        }

        $mode = $data === 'battle_create_full' ? 'full' : 'mixed';

        try {
            $this->findOrCreateTelegramUserFromProfile($from ?? []); // ensure actor exists in DB/audit context
            $variant = $this->variantPool->createBattleVariant($mode);

            $botUsername = (string) config('services.telegram.bot_username', 'palomatika_auth_bot');
            $miniAppShort = trim((string) config('services.telegram.mini_app_short_name', ''));
            $startParam = 'oge_variant_hash_' . $variant->hash;

            // Use direct mini-app deep-link only when short name is explicitly configured.
            // Otherwise fallback to /start flow (no 404 risk).
            if ($miniAppShort !== '') {
                $shareLink = "https://t.me/{$botUsername}/{$miniAppShort}?startapp={$startParam}";
            } else {
                $shareLink = "https://t.me/{$botUsername}?start={$startParam}";
            }

            $title = $mode === 'full' ? 'Полный вариант' : 'Смешанный мини-вариант';

            $this->answerCallbackQuery($callbackId, 'Вариант создан ✅');
            if ($chatId !== '') {
                $this->sendTelegramMessage(
                    $chatId,
                    "✅ Батл-вариант создан\n\nТип: {$title}\nHash: <code>{$variant->hash}</code>\n\nСсылка для учеников:\n{$shareLink}"
                );
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to create battle variant via Telegram callback', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            $this->answerCallbackQuery($callbackId, 'Ошибка создания', true);
            if ($chatId !== '') {
                $this->sendTelegramMessage($chatId, '❌ Не удалось создать батл-вариант. Попробуй ещё раз.');
            }
        }
    }

    private function answerCallbackQuery(string $callbackId, string $text = '', bool $showAlert = false): void
    {
        if ($callbackId === '') {
            return;
        }

        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/answerCallbackQuery";
        $payload = [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert,
        ];

        try {
            $client = new \GuzzleHttp\Client();
            $client->post($url, [
                'json' => $payload,
                'timeout' => 5,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to answer callback query', ['error' => $e->getMessage()]);
        }
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
        $startParam = is_string($startParam) ? trim($startParam) : '';
        if ($startParam === '') {
            $startParam = 'miniapp_home';
        }
        $separator = str_contains($loginUrl, '?') ? '&' : '?';
        $loginUrl .= $separator . 'startapp=' . rawurlencode($startParam);

        $webAppBaseUrl = trim((string) config('services.telegram.webapp_base_url', ''));
        $button = [
            'text' => '🚀 Открыть приложение',
        ];

        if ($webAppBaseUrl !== '') {
            $button['web_app'] = ['url' => $loginUrl];
        } else {
            $button['url'] = $loginUrl;
        }

        $this->sendTelegramMessage(
            $from['id'],
            "✅ Вы успешно авторизовались!\n\nПривет, {$name}! Чтобы вернуться обратно в приложение, нажмите на кнопку ниже:\n⬇️⬇️⬇️",
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
        return $this->tgMiniAuth->extractAndVerify($initData, $initDataUnsafe);
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
            // Explicit mini app marker from fallback login flow
            if (str_starts_with($startParam, 'miniapp_')) {
                $user = auth()->user();
                if ($user && !$user->onboarding_completed_at) {
                    return url('/tg/onboarding');
                }
                return url('/tg/dashboard');
            }

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
