<?php

namespace App\Services;

use App\Models\User;

class TelegramMiniAppAuthService
{
    /**
     * @param array<string,mixed> $initDataUnsafe
     * @return array{0: array<string,string>, 1: array<string,mixed>}
     */
    public function extractAndVerify(string $initData, array $initDataUnsafe = []): array
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
            if ($value === null) continue;
            if (is_array($value)) {
                $normalizedFields[(string) $key] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $normalizedFields[(string) $key] = (string) $value;
            }
        }

        ksort($normalizedFields);
        $dataCheckString = collect($normalizedFields)
            ->map(fn (string $value, string $key) => "{$key}={$value}")
            ->implode("\n");

        $botToken = (string) config('services.telegram.bot_token', '');
        if ($botToken === '') {
            throw new \RuntimeException('Telegram bot token not configured');
        }

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $providedHash)) {
            throw new \RuntimeException('Invalid signature');
        }

        // WebView/VPN can delay payload delivery in real world.
        // Keep this configurable; default to tolerant window until nonce/replay-store is introduced.
        $maxAge = (int) env('TELEGRAM_WEBAPP_AUTH_MAX_AGE', 86400);
        if ($maxAge <= 0) {
            $maxAge = 86400;
        }
        $authDate = (int) ($normalizedFields['auth_date'] ?? 0);
        if ($authDate <= 0 || abs(time() - $authDate) > $maxAge) {
            throw new \RuntimeException('auth_date expired');
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
     * @param array<string,mixed> $telegramUser
     */
    public function findOrCreateUser(array $telegramUser): User
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
     * @param array<string,mixed> $initDataUnsafe
     * @return array<string,mixed>
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
}
