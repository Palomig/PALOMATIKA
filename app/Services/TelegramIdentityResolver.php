<?php

namespace App\Services;

use App\Models\User;

/**
 * Единый резолвер Telegram-идентичности для ВСЕХ путей входа.
 *
 * Ключей два, и они разной природы:
 * - `telegram_chat_id` — настоящий telegram user id. Приходит из initData мини-аппа
 *   и из /start в боте. Только на него бот умеет писать.
 * - `telegram_oidc_sub` — псевдоним из «Войти через Telegram» (OIDC). Годится
 *   только чтобы узнать вернувшегося пользователя; писать по нему нельзя.
 *
 * Раньше оба ключа сваливались в `oauth_id`, из-за чего один человек получал два
 * аккаунта, а уведомления уходили на несуществующий chat_id.
 */
class TelegramIdentityResolver
{
    /**
     * Вход из мини-аппа или бота: у нас есть настоящий chat_id.
     *
     * @param array{id:int|string, username?:?string, name?:?string, photo?:?string} $claims
     */
    public function resolveByChatId(array $claims): User
    {
        $chatId = $this->normalizeChatId($claims['id'] ?? null);
        if ($chatId === null) {
            throw new \InvalidArgumentException('Missing Telegram user id');
        }

        $user = User::where('telegram_chat_id', $chatId)->first()
            // Легаси: до разделения колонок настоящий id лежал в oauth_id.
            ?? User::where('oauth_id', (string) $chatId)
                ->where(function ($q) {
                    $q->where('oauth_provider', 'telegram')->orWhereNull('oauth_provider');
                })
                ->orderBy('id')
                ->first();

        if ($user) {
            $this->touchProfile($user, $claims, [
                'telegram_chat_id'    => $chatId,
                'telegram_linked_at'  => $user->telegram_linked_at ?? now(),
                // Раз человек снова пришёл из телеграма — прошлый 403 неактуален.
                'telegram_blocked_at' => null,
            ]);

            return $user;
        }

        return User::create([
            'name'               => $this->normalizeName($claims),
            'oauth_provider'     => 'telegram',
            'oauth_id'           => (string) $chatId,
            'telegram_chat_id'   => $chatId,
            'telegram_linked_at' => now(),
            'tg_username'        => $this->normalizeUsername($claims['username'] ?? null),
            'avatar'             => $this->normalizeAvatar($claims['photo'] ?? null),
        ]);
    }

    /**
     * Вход через OIDC: настоящего id нет, только псевдоним `sub`. Такой аккаунт
     * остаётся без chat_id, пока ученик не привяжет телеграм через бота
     * (см. {@see TelegramLinkService}).
     *
     * @param array{sub:string, username?:?string, name?:?string, photo?:?string} $claims
     */
    public function resolveBySub(array $claims): User
    {
        $sub = trim((string) ($claims['sub'] ?? ''));
        if ($sub === '') {
            throw new \InvalidArgumentException('Missing Telegram OIDC sub');
        }

        $user = User::where('telegram_oidc_sub', $sub)->first()
            // Легаси: до разделения колонок sub лежал в oauth_id.
            ?? User::where('oauth_provider', 'telegram')->where('oauth_id', $sub)->orderBy('id')->first();

        if ($user) {
            $this->touchProfile($user, $claims, ['telegram_oidc_sub' => $sub]);

            return $user;
        }

        return User::create([
            'name'              => $this->normalizeName($claims),
            'oauth_provider'    => 'telegram',
            'oauth_id'          => $sub,
            'telegram_oidc_sub' => $sub,
            'tg_username'       => $this->normalizeUsername($claims['username'] ?? null),
            'avatar'            => $this->normalizeAvatar($claims['photo'] ?? null),
        ]);
    }

    /**
     * @param array<string,mixed> $claims
     * @param array<string,mixed> $extra
     */
    private function touchProfile(User $user, array $claims, array $extra = []): void
    {
        $updates = [];

        foreach ($extra as $key => $value) {
            if ($user->{$key} != $value || ($value === null) !== ($user->{$key} === null)) {
                $updates[$key] = $value;
            }
        }

        $username = $this->normalizeUsername($claims['username'] ?? null);
        if ($username !== null && $user->tg_username !== $username) {
            $updates['tg_username'] = $username;
        }

        $avatar = $this->normalizeAvatar($claims['photo'] ?? null);
        if ($avatar !== null && $user->avatar !== $avatar) {
            $updates['avatar'] = $avatar;
        }

        // Легаси-запись без провайдера — присваиваем telegram.
        if ($user->oauth_provider === null) {
            $updates['oauth_provider'] = 'telegram';
        }

        if ($updates !== []) {
            $user->update($updates);
        }
    }

    private function normalizeChatId(mixed $id): ?int
    {
        $id = trim((string) $id);

        return ($id !== '' && ctype_digit($id)) ? (int) $id : null;
    }

    private function normalizeUsername(?string $u): ?string
    {
        $u = $u !== null ? ltrim(trim($u), '@') : '';

        return $u === '' ? null : mb_substr($u, 0, 100);
    }

    private function normalizeAvatar(?string $url): ?string
    {
        $url = trim((string) $url);

        return ($url !== '' && str_starts_with($url, 'https://')) ? mb_substr($url, 0, 255) : null;
    }

    /**
     * @param array<string,mixed> $claims
     */
    private function normalizeName(array $claims): string
    {
        $name = trim((string) ($claims['name'] ?? ''));
        if ($name !== '') {
            return mb_substr($name, 0, 255);
        }

        return $this->normalizeUsername($claims['username'] ?? null) ?? 'Пользователь';
    }
}
