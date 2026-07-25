<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Отделяет «чем человек вошёл» от «куда боту писать».
 *
 * До этой миграции telegram-идентичность жила в одном поле `oauth_id`, куда
 * Mini App клал настоящий chat_id, а OIDC — псевдоним `sub` (19–20 цифр).
 * Бот на sub писать не может, поэтому уведомления о ДЗ молча терялись.
 */
return new class extends Migration
{
    /** Настоящий telegram user id укладывается в 15 цифр; OIDC sub — 19–20. */
    private const REAL_ID_MAX_DIGITS = 15;

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Куда боту слать сообщения. Заполняется ТОЛЬКО из проверенных
            // источников: initData мини-аппа и /start в боте.
            $table->unsignedBigInteger('telegram_chat_id')->nullable()->after('oauth_id');
            // Псевдоним из OIDC — только для матчинга входа, не для отправки.
            $table->string('telegram_oidc_sub', 64)->nullable()->after('telegram_chat_id');
            // ON UPDATE CURRENT_TIMESTAMP у timestamp ломает прод — только dateTime.
            $table->dateTime('telegram_linked_at')->nullable()->after('telegram_oidc_sub');
            // Ученик заблокировал бота: 403 от sendMessage.
            $table->dateTime('telegram_blocked_at')->nullable()->after('telegram_linked_at');

            // Аккаунт слит в другой: сам по себе больше не входная точка,
            // но остаётся в базе, чтобы не рвать внешние ссылки и аудит.
            $table->unsignedBigInteger('merged_into_id')->nullable()->after('referred_by_user_id');
            $table->dateTime('merged_at')->nullable()->after('merged_into_id');

            $table->unique('telegram_chat_id');
            $table->index('telegram_oidc_sub');
            $table->index('merged_into_id');
        });

        // Бэкфилл: настоящие id переезжают в telegram_chat_id...
        DB::table('users')
            ->where('oauth_provider', 'telegram')
            ->whereNotNull('oauth_id')
            ->whereRaw('CHAR_LENGTH(oauth_id) <= ?', [self::REAL_ID_MAX_DIGITS])
            ->whereRaw("oauth_id REGEXP '^[0-9]+$'")
            ->update([
                'telegram_chat_id' => DB::raw('CAST(oauth_id AS UNSIGNED)'),
                'telegram_linked_at' => now(),
            ]);

        // ...а OIDC-псевдонимы — в свою колонку, chat_id у них остаётся пустым,
        // пока ученик не привяжет телеграм через бота.
        DB::table('users')
            ->where('oauth_provider', 'telegram')
            ->whereNotNull('oauth_id')
            ->whereRaw('CHAR_LENGTH(oauth_id) > ?', [self::REAL_ID_MAX_DIGITS])
            ->update(['telegram_oidc_sub' => DB::raw('oauth_id')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_chat_id']);
            $table->dropIndex(['telegram_oidc_sub']);
            $table->dropIndex(['merged_into_id']);
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_oidc_sub',
                'telegram_linked_at',
                'telegram_blocked_at',
                'merged_into_id',
                'merged_at',
            ]);
        });
    }
};
