<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Токены «запомнить меня» — по одному на устройство (таблица remember_devices).
 * В БД лежит только sha256 токена; сам токен живёт в remember-куке.
 *
 * Любая ошибка БД (например, миграция ещё не прошла) не должна ронять вход:
 * методы глотают её, а DeviceSessionGuard откатывается на старый общий токен.
 */
class RememberDevices
{
    /** Столько дней неиспользуемое устройство считается живым (как потолок куки в Chrome). */
    public const TTL_DAYS = 400;

    /** Больше устройств на одного пользователя не держим — старшие удаляются. */
    public const MAX_PER_USER = 30;

    /** last_used_at обновляем не чаще, чем раз в столько минут. */
    private const TOUCH_EVERY_MINUTES = 60;

    /** Новый токен устройства или null, если записать его не удалось. */
    public static function issue(Authenticatable $user, ?Request $request): ?string
    {
        $token = Str::random(60);
        $userId = $user->getAuthIdentifier();

        return self::safely(function () use ($token, $userId, $request) {
            DB::table('remember_devices')->insert([
                'user_id' => $userId,
                'token_hash' => self::hash($token),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
                'ip' => $request?->ip(),
                'last_used_at' => now(),
                'created_at' => now(),
            ]);

            self::prune($userId);

            return $token;
        });
    }

    /** Строка устройства, если токен принадлежит этому пользователю и не протух. */
    public static function find($userId, string $token): ?object
    {
        if ($token === '') {
            return null;
        }

        return self::safely(fn () => DB::table('remember_devices')
            ->where('user_id', $userId)
            ->where('token_hash', self::hash($token))
            ->where('last_used_at', '>=', now()->subDays(self::TTL_DAYS))
            ->first());
    }

    public static function touch(object $device, ?Request $request): void
    {
        if ($device->last_used_at && now()->subMinutes(self::TOUCH_EVERY_MINUTES)->lt($device->last_used_at)) {
            return;
        }

        self::safely(fn () => DB::table('remember_devices')->where('id', $device->id)->update([
            'last_used_at' => now(),
            'ip' => $request?->ip() ?? $device->ip,
        ]));
    }

    /** Перенести старый общий токен (users.remember_token) в устройство, чтобы не выкинуть уже вошедших. */
    public static function adopt($userId, string $token, ?Request $request): void
    {
        self::safely(fn () => DB::table('remember_devices')->insertOrIgnore([
            'user_id' => $userId,
            'token_hash' => self::hash($token),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            'ip' => $request?->ip(),
            'last_used_at' => now(),
            'created_at' => now(),
        ]));
    }

    public static function forget(string $token): void
    {
        if ($token !== '') {
            self::safely(fn () => DB::table('remember_devices')->where('token_hash', self::hash($token))->delete());
        }
    }

    public static function forgetAll($userId): int
    {
        return DB::table('remember_devices')->where('user_id', $userId)->delete();
    }

    private static function prune($userId): void
    {
        DB::table('remember_devices')
            ->where('user_id', $userId)
            ->where('last_used_at', '<', now()->subDays(self::TTL_DAYS))
            ->delete();

        $keep = DB::table('remember_devices')
            ->where('user_id', $userId)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->limit(self::MAX_PER_USER)
            ->pluck('id');

        DB::table('remember_devices')
            ->where('user_id', $userId)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    private static function safely(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            Log::error('remember_devices_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
