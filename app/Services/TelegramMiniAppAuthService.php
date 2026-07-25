<?php

namespace App\Services;

use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TelegramMiniAppAuthService
{
    /**
     * Resolve the final PWA landing URL for a Telegram user after auth.
     * Returns an absolute URL on student./teacher. subdomain so Telegram webview
     * navigates to the full PWA experience (shared session cookie on .base_domain).
     *
     * Deep-link variant hashes are forwarded as ?startapp= so the target page
     * (CaptureTelegramStartParam middleware + dashboard) can open the variant.
     */
    public function pwaLandingUrl(?User $user, ?string $startParam = null): string
    {
        $baseDomain = (string) config('app.base_domain', 'palomatika.ru');
        $isTeacher = $user && in_array($user->role, ['teacher', 'admin'], true);

        $home = $isTeacher
            ? 'https://teacher.' . $baseDomain . '/dashboard'
            : 'https://student.' . $baseDomain . '/';

        $startParam = is_string($startParam) ? trim($startParam) : '';
        if ($startParam === '') {
            return $home;
        }

        // Variant hash payloads are validated against the real variant exam_type
        // so oge_variant_hash_* naming does not bypass grade-based access rules.
        if (preg_match('/^oge_variant_hash_([a-z0-9]{8,32})$/i', $startParam, $m)
            || (preg_match('/^oge_variant_([a-z0-9]{8,32})$/i', $startParam, $m) && !ctype_digit($m[1]))) {
            $hash = strtolower($m[1]);
            $variant = Schema::hasTable('oge_variants')
                ? OgeVariant::where('hash', $hash)->first()
                : null;

            if ($user && $user->role === 'student' && $variant) {
                $examAccess = app(StudentExamAccessService::class);
                if (!$examAccess->canAccessVariant($user, $variant)) {
                    return $home;
                }
            }

            return 'https://student.' . $baseDomain . '/?startapp=oge_variant_hash_' . $hash;
        }

        // miniapp_ / ref_ / anything else → plain landing (middlewares handle the rest).
        return $home;
    }

    /**
     * If startParam is a referral marker (ref_{userId} or ref_tg_{tgId}),
     * set referred_by_user_id and — when the referrer is a teacher/admin —
     * create the teacher_students link so the teacher sees the student
     * in their dashboard even before any attempt is submitted.
     */
    public function linkReferralFromStartParam(User $user, ?string $startParam): void
    {
        $startParam = is_string($startParam) ? trim($startParam) : '';
        if ($startParam === '') {
            return;
        }

        $referrerId = null;
        if (preg_match('/^ref_(\d+)$/', $startParam, $m)) {
            $referrerId = (int) $m[1];
        } elseif (preg_match('/^ref_tg_(\d+)$/', $startParam, $m)) {
            $referrerId = User::where('telegram_chat_id', (int) $m[1])->value('id')
                ?? User::where('oauth_provider', 'telegram')->where('oauth_id', $m[1])->value('id');
        }

        if (!$referrerId || $referrerId === $user->id) {
            return;
        }

        $referrer = User::find($referrerId);
        if (!$referrer) {
            return;
        }

        if ($user->referred_by_user_id === null) {
            $user->update(['referred_by_user_id' => $referrerId]);
        }

        if (in_array($referrer->role, ['teacher', 'admin'], true)) {
            TeacherStudent::firstOrCreate(
                ['teacher_id' => $referrerId, 'student_id' => $user->id],
                ['source' => 'referral']
            );
        }
    }

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
        $maxAge = (int) config('services.telegram.webapp_auth_max_age', 86400);
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
        return app(\App\Services\TelegramIdentityResolver::class)->resolveByChatId([
            'id'       => $telegramUser['id'] ?? '',
            'username' => $telegramUser['username'] ?? null,
            'name'     => $this->normalizeDisplayName($telegramUser),
            'photo'    => $telegramUser['photo_url'] ?? null,
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

    /**
     * @param array<string,mixed> $telegramUser
     */
    private function normalizeDisplayName(array $telegramUser): string
    {
        $name = trim(((string) ($telegramUser['first_name'] ?? '')) . ' ' . ((string) ($telegramUser['last_name'] ?? '')));
        if ($name === '') {
            $name = (string) ($telegramUser['username'] ?? 'User');
        }

        return mb_substr(trim($name), 0, 255);
    }

    private function normalizeTelegramUsername(mixed $username): ?string
    {
        $normalized = trim((string) $username);

        return $normalized !== '' ? mb_substr($normalized, 0, 100) : null;
    }

    private function normalizeAvatarUrl(mixed $photoUrl): ?string
    {
        $normalized = trim((string) $photoUrl);

        return $normalized !== '' ? mb_substr($normalized, 0, 255) : null;
    }
}
