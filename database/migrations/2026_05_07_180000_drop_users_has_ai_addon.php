<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Эпик #44 / подзадача #50: выпил AI-помощника.
 *
 * Сам AI-помощник в коде уже отсутствовал (нет AssistantService /
 * Anthropic / OpenAI SDK). Осталась лишь boolean-колонка
 * users.has_ai_addon из старой модели «платный аддон». Дропаем.
 *
 * Down() намеренно не реализован.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'has_ai_addon')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('has_ai_addon');
            });
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'drop_users_has_ai_addon — одностороння миграция.'
        );
    }
};
