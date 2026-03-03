<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TmaAuthV2Controller extends Controller
{
    public function auth(Request $request)
    {
        $initData = trim((string) $request->input('initData', ''));
        if ($initData === '') {
            return response()->json(['message' => 'initData required'], 422);
        }

        $botToken = (string) config('services.telegram.bot_token', '');
        if ($botToken === '') {
            return response()->json(['message' => 'Telegram not configured'], 500);
        }

        parse_str($initData, $fields);
        if (empty($fields['hash'])) {
            return response()->json(['message' => 'Invalid initData'], 422);
        }

        $providedHash = (string) $fields['hash'];
        $signableFields = $fields;
        unset($signableFields['hash']);

        $normalizedFields = [];
        foreach ($signableFields as $key => $value) {
            if ($value === null) continue;
            $normalizedFields[(string) $key] = is_array($value)
                ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (string) $value;
        }

        ksort($normalizedFields);
        $dataCheckString = collect($normalizedFields)
            ->map(fn(string $value, string $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $providedHash)) {
            Log::warning('tma_v2_bad_hmac', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'auth_date' => $fields['auth_date'] ?? null,
            ]);
            return response()->json(['message' => 'Bad Telegram signature'], 401);
        }

        $authDate = (int) ($fields['auth_date'] ?? 0);
        if ($authDate <= 0 || abs(time() - $authDate) > 86400) {
            Log::warning('tma_v2_auth_date_expired', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'auth_date' => $authDate,
                'now' => time(),
            ]);
            return response()->json(['message' => 'auth_date expired, reopen mini app'], 401);
        }

        $telegramUser = null;
        if (is_string($fields['user'] ?? null)) {
            $telegramUser = json_decode($fields['user'], true);
        }
        if (!is_array($telegramUser) || empty($telegramUser['id'])) {
            return response()->json(['message' => 'No Telegram user'], 422);
        }

        $telegramId = (string) $telegramUser['id'];
        $user = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $telegramId)
            ->first();

        if (!$user) {
            $name = trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? ''));
            if ($name === '') {
                $name = $telegramUser['username'] ?? 'User';
            }
            $user = User::create([
                'name' => $name,
                'oauth_provider' => 'telegram',
                'oauth_id' => $telegramId,
                'avatar' => $telegramUser['photo_url'] ?? null,
                'trial_ends_at' => now()->addDays(7),
            ]);
        }

        $access = Str::random(64);
        $refresh = Str::random(64);
        Cache::put('tma_v2_access:' . $access, ['user_id' => $user->id], now()->addMinutes(20));
        Cache::put('tma_v2_refresh:' . $refresh, ['user_id' => $user->id], now()->addDays(30));

        return response()->json([
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
            'expires_in' => 1200,
            'needs_onboarding' => !$user->onboarding_completed_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function refresh(Request $request)
    {
        $refresh = trim((string) $request->input('refresh_token', ''));
        if ($refresh === '') {
            return response()->json(['message' => 'refresh_token required'], 422);
        }

        $payload = Cache::get('tma_v2_refresh:' . $refresh);
        if (!is_array($payload) || empty($payload['user_id'])) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $access = Str::random(64);
        Cache::put('tma_v2_access:' . $access, ['user_id' => (int) $payload['user_id']], now()->addMinutes(20));

        return response()->json([
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => 1200,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'needs_onboarding' => !$user->onboarding_completed_at,
        ]);
    }
}
