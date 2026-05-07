<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Эпик #44 / подзадача #46: выпил пазлов (step_blocks).
 *
 * Перед drop делает бэкап всех затрагиваемых таблиц в JSON
 * (storage/app/backups/redesign-44/{table}.json + manifest.json).
 *
 * Дропаем legacy puzzle/attempt стек + legacy homework_tasks +
 * legacy homeworks.topic_id FK (новая схема использует topic_number).
 *
 * Down() намеренно не реализован — это одностороння миграция,
 * восстановление 27к step_blocks и связанных строк только из бэкапа.
 */
return new class extends Migration
{
    private const TABLES = [
        'topics',
        'puzzle_templates',
        'tasks',
        'task_skills',
        'task_steps',
        'step_blocks',
        'attempts',
        'attempt_steps',
        'step_block_selections',
        'homework_tasks',
    ];

    public function up(): void
    {
        $this->backupTables();

        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable('homeworks') && Schema::hasColumn('homeworks', 'topic_id')) {
                if (DB::connection()->getDriverName() === 'mysql') {
                    $constraints = DB::select(
                        "SELECT CONSTRAINT_NAME
                         FROM information_schema.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME = 'homeworks'
                           AND COLUMN_NAME = 'topic_id'
                           AND REFERENCED_TABLE_NAME IS NOT NULL"
                    );
                    foreach ($constraints as $c) {
                        DB::statement("ALTER TABLE homeworks DROP FOREIGN KEY `{$c->CONSTRAINT_NAME}`");
                    }
                }
                Schema::table('homeworks', function (Blueprint $table) {
                    $table->dropColumn('topic_id');
                });
            }

            Schema::dropIfExists('homework_tasks');
            Schema::dropIfExists('step_block_selections');
            Schema::dropIfExists('attempt_steps');
            Schema::dropIfExists('attempts');
            Schema::dropIfExists('step_blocks');
            Schema::dropIfExists('task_steps');
            Schema::dropIfExists('task_skills');
            Schema::dropIfExists('tasks');
            Schema::dropIfExists('puzzle_templates');
            Schema::dropIfExists('topics');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'drop_legacy_puzzle_tables — одностороння миграция. ' .
            'Восстановление: docs/plans/2026-05-05-redesign-direction.md.'
        );
    }

    private function backupTables(): void
    {
        $disk = Storage::disk('local');
        $path = 'backups/redesign-44';
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
