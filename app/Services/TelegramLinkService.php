<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Привязка настоящего telegram chat_id к аккаунту.
 *
 * Ученик может войти как угодно (Яндекс, Google, «Войти через Telegram»), но
 * писать ему бот сможет только после /start в личке. Поэтому из веб-сессии
 * выдаём одноразовый код, а бот, получив `/start link_<code>`, знает и код,
 * и настоящий id отправителя — этого достаточно, чтобы связать одно с другим.
 *
 * Побочный эффект: если этот chat_id уже принадлежит другому аккаунту, значит
 * это один и тот же человек с двумя записями — их сливаем (см. {@see AccountMergeService}).
 */
class TelegramLinkService
{
    private const CODE_TTL_MINUTES = 15;
    /** Результат живёт дольше кода: страница может опрашивать статус после привязки. */
    private const RESULT_TTL_MINUTES = 30;

    public function __construct(
        private readonly AccountMergeService $merger,
    ) {
    }

    /**
     * @return array{code: string, deep_link: string}
     */
    public function issueCode(User $user): array
    {
        $code = Str::lower(Str::random(24));
        Cache::put($this->codeKey($code), $user->id, now()->addMinutes(self::CODE_TTL_MINUTES));

        $bot = (string) config('services.telegram.bot_username', 'palomatika_auth_bot');

        return [
            'code' => $code,
            'deep_link' => "https://t.me/{$bot}?start=link_{$code}",
        ];
    }

    /**
     * Вызывается из вебхука бота на `/start link_<code>`.
     *
     * @param array<string,mixed> $from Telegram-профиль отправителя
     * @return array{user: User, merged: bool}|null null — код протух или уже использован
     */
    public function completeLink(string $code, array $from): ?array
    {
        $userId = Cache::pull($this->codeKey($code));
        $chatId = trim((string) ($from['id'] ?? ''));

        if (!$userId || $chatId === '' || !ctype_digit($chatId)) {
            return null;
        }

        $user = User::find((int) $userId);
        if (!$user) {
            return null;
        }

        $merged = false;
        $owner = User::where('telegram_chat_id', (int) $chatId)->first();
        // Обе стороны слияния — чтобы страница-опросчик могла перелогинить только
        // того, кто в этом слиянии участвовал.
        $participants = [$user->id];

        if ($owner && $owner->id !== $user->id) {
            $participants[] = $owner->id;
            // Тот же телеграм уже привязан к другой записи → это дубль одного человека.
            $canonical = $this->merger->pickCanonical($owner, $user);
            $donor = $canonical->is($owner) ? $user : $owner;

            $this->merger->merge($donor, $canonical);
            $user = $canonical->refresh();
            $merged = true;
        }

        $user->update([
            'telegram_chat_id'    => (int) $chatId,
            'telegram_linked_at'  => now(),
            'telegram_blocked_at' => null,
            'tg_username'         => $this->username($from) ?? $user->tg_username,
        ]);

        Cache::put($this->resultKey($code), [
            'user_id'      => $user->id,
            'merged'       => $merged,
            'participants' => $participants,
        ], now()->addMinutes(self::RESULT_TTL_MINUTES));

        Log::info('telegram_linked', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'merged'  => $merged,
        ]);

        return ['user' => $user, 'merged' => $merged];
    }

    /**
     * Статус для опроса со страницы привязки.
     *
     * @return array{user_id: int, merged: bool, participants: array<int, int>}|null
     */
    public function result(string $code): ?array
    {
        $result = Cache::get($this->resultKey($code));

        return is_array($result) ? $result : null;
    }

    /**
     * @param array<string,mixed> $from
     */
    private function username(array $from): ?string
    {
        $username = ltrim(trim((string) ($from['username'] ?? '')), '@');

        return $username === '' ? null : mb_substr($username, 0, 100);
    }

    private function codeKey(string $code): string
    {
        return 'tg_link_code:' . $code;
    }

    private function resultKey(string $code): string
    {
        return 'tg_link_result:' . $code;
    }
}
