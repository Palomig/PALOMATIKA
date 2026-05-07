<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Эпик #44 / подзадача #47: выпил геймификации.
 *
 * Дропаем Duolingo-стиль: badges, leagues, duels, streaks, daily_stats,
 * gifts, challenges. Мини-игры (practice_game_runs) и рефералы
 * (referral_clicks) — TRIP-WIRES, не трогаем.
 *
 * Telegram Stars (star_transactions, tg_premium_until/star_balance/
 * tg_trial_used) — отложено отдельным решением, не трогаем.
 *
 * Перед drop: бэкап в storage/app/backups/redesign-44-gamification.
 * Down() намеренно не реализован.
 */
return new class extends Migration
{
    private const TABLES = [
        'user_streaks',
        'user_badges',
        'badges',
        'league_participants',
        'leagues',
        'duel_tasks',
        'duels',
        'challenge_team_members',
        'challenge_teams',
        'challenges',
        'user_daily_stats',
        'user_gifts',
    ];

    public function up(): void
    {
        $this->backupTables();

        Schema::disableForeignKeyConstraints();

        try {
            // Дропаем child-таблицы первыми.
            Schema::dropIfExists('user_badges');
            Schema::dropIfExists('user_streaks');
            Schema::dropIfExists('league_participants');
            Schema::dropIfExists('duel_tasks');
            Schema::dropIfExists('challenge_team_members');
            Schema::dropIfExists('challenge_teams');

            // Parent-таблицы.
            Schema::dropIfExists('badges');
            Schema::dropIfExists('leagues');
            Schema::dropIfExists('duels');
            Schema::dropIfExists('challenges');
            Schema::dropIfExists('user_daily_stats');
            Schema::dropIfExists('user_gifts');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'drop_legacy_gamification_tables — одностороння миграция. ' .
            'Восстановление: docs/plans/2026-05-05-redesign-direction.md.'
        );
    }

    private function backupTables(): void
    {
        $disk = Storage::disk('local');
        $path = 'backups/redesign-44-gamification';
        $disk->makeDirectory($path);

        $manifest = ['created_at' => now()->toIso8601String(), 'tables' => []];

        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                $manifest['tables'][$table] = ['present' => false];
                continue;
            }

            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            $disk->put("{$path}/{$table}.json", json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $manifest['tables'][$table] = ['present' => true, 'rows' => count($rows)];
        }

        $disk->put("{$path}/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
};
