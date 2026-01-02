<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Попытки решения задачи
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            // Контекст
            $table->uuid('session_id')->nullable();
            $table->enum('source', ['practice', 'diagnostic', 'homework', 'duel', 'challenge'])->default('practice');
            $table->foreignId('homework_id')->nullable();
            $table->foreignId('duel_id')->nullable();

            // Результат
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_correct')->nullable();

            // Время
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();

            // XP заработано
            $table->unsignedInteger('xp_earned')->default(0);

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
        });

        // Шаги попытки (детальная аналитика)
        Schema::create('attempt_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_step_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('step_number');
            $table->boolean('is_correct');

            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();

            // Количество попыток на этом шаге
            $table->tinyInteger('attempts_count')->default(1);
        });

        // Выбранные блоки (максимальная детализация)
        Schema::create('step_block_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('step_block_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('position'); // позиция в шаблоне
            $table->boolean('is_correct');
            $table->timestamp('selected_at');

            // Какой навык был затронут
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_block_selections');
        Schema::dropIfExists('attempt_steps');
        Schema::dropIfExists('attempts');
    }
};
