<?php

namespace App\Services;

use App\Models\User;

/**
 * Единый резолвер Telegram-идентичности для ВСЕХ путей входа
 * (OIDC web + Mini App initData). Ключ: (oauth_provider='telegram', oauth_id=tg id).
 *
 * @param array{id:int|string, username?:?string, name?:?string, photo?:?string} $claims
 */
class TelegramIdentityResolver
{
    public function resolve(array $claims): User
    {
        $telegramId = (string) ($claims['id'] ?? '');
        if ($telegramId === '') {
            throw new \InvalidArgumentException('Missing Telegram user id');
        }

        $username = $this->normalizeUsername($claims['username'] ?? null);
        $avatar   = $this->normalizeAvatar($claims['photo'] ?? null);

        $user = User::where('oauth_provider', 'telegram')->where('oauth_id', $telegramId)->first();
        if ($user) {
            $updates = [];
            if ($username !== null && $user->tg_username !== $username) {
                $updates['tg_username'] = $username;
            }
            if ($avatar !== null && $user->avatar !== $avatar) {
                $updates['avatar'] = $avatar;
            }
            if ($updates !== []) {
                $user->update($updates);
            }
            return $user;
        }

        // Легаси: запись с oauth_id, но без provider — присваиваем telegram.
        $legacy = User::whereNull('oauth_provider')->where('oauth_id', $telegramId)->orderBy('id')->first();
        if ($legacy) {
            $updates = ['oauth_provider' => 'telegram'];
            if ($username !== null && $legacy->tg_username !== $username) {
                $updates['tg_username'] = $username;
            }
            if ($avatar !== null && $legacy->avatar !== $avatar) {
                $updates['avatar'] = $avatar;
            }
            $legacy->update($updates);
            return $legacy;
        }

        return User::create([
            'name'           => $this->normalizeName($claims),
            'oauth_provider' => 'telegram',
            'oauth_id'       => $telegramId,
            'tg_username'    => $username,
            'avatar'         => $avatar,
        ]);
    }

    private function normalizeUsername(?string $u): ?string
    {
        $u = $u !== null ? ltrim(trim($u), '@') : '';
        return $u === '' ? null : $u;
    }

    private function normalizeAvatar(?string $url): ?string
    {
        $url = trim((string) $url);
        return ($url !== '' && str_starts_with($url, 'https://')) ? $url : null;
    }

    private function normalizeName(array $claims): string
    {
        $name = trim((string) ($claims['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $username = $this->normalizeUsername($claims['username'] ?? null);
        return $username ?? 'Пользователь';
    }
}
