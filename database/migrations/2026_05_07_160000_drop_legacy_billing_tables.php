<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Эпик #44 / подзадача #49: выпил легаси-биллинга учеников.
 *
 * Дропаем subscriptions/payout_items/teacher_payouts (на проде 0 строк)
 * + колонки users.subscription_plan/subscription_ends_at/trial_ends_at.
 *
 * НЕ ТРОГАЕМ:
 * - referral_clicks (статистика рефералов)
 * - star_transactions + tg_premium_until/star_balance/tg_trial_used
 *   (Telegram Stars — отложено отдельным решением)
 * - users.has_ai_addon (#50 — AI-помощник, отдельная подзадача)
 * - partner_* поля (связаны с TG Stars commission, отложены вместе с ним)
 *
 * Перед drop: бэкап в storage/app/backups/redesign-44-billing.
 * Down() намеренно не реализован.
 */
return new class extends Migration
{
    private const TABLES = ['subscriptions', 'payout_items', 'teacher_payouts'];

    public function up(): void
    {
        $this->backupTables();

        Schema::disableForeignKeyConstraints();

        try {
            // FK: payout_items → teacher_payouts, subscriptions
            Schema::dropIfExists('payout_items');
            Schema::dropIfExists('teacher_payouts');
            Schema::dropIfExists('subscriptions');

            if (Schema::hasTable('users')) {
                Schema::table('users', function (Blueprint $table) {
                    foreach (['subscription_plan', 'subscription_ends_at', 'trial_ends_at'] as $col) {
                        if (Schema::hasColumn('users', $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'drop_legacy_billing_tables — одностороння миграция. ' .
            'Восстановление: docs/plans/2026-05-05-redesign-direction.md.'
        );
    }

    private function backupTables(): void
    {
        $disk = Storage::disk('local');
        $path = 'backups/redesign-44-billing';
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

        // Также бэкапим затрагиваемые user-колонки.
        if (Schema::hasTable('users')) {
            $userCols = DB::table('users')
                ->select(['id', 'subscription_plan', 'subscription_ends_at', 'trial_ends_at'])
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
            $disk->put("{$path}/users_billing_columns.json", json_encode($userCols, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $manifest['tables']['users_billing_columns'] = ['present' => true, 'rows' => count($userCols)];
        }

        $disk->put("{$path}/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
};
