<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Полный лог действий пользователя
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('event_type', [
                'login', 'logout',
                'page_view',
                'task_started', 'task_completed',
                'step_started', 'block_selected',
                'duel_created', 'duel_accepted', 'duel_completed',
                'homework_started', 'homework_completed',
                'subscription_created', 'subscription_cancelled',
                'idle_detected', 'tab_hidden', 'tab_visible'
            ]);

            // Контекст
            $table->string('page_url', 500)->nullable();
            $table->foreignId('task_id')->nullable();
            $table->json('metadata')->nullable(); // дополнительные данные

            // Устройство
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet'])->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });

        // Ежедневная статистика пользователя (агрегация)
        Schema::create('user_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Время
            $table->unsignedInteger('online_seconds')->default(0);
            $table->unsignedInteger('active_seconds')->default(0); // исключая idle

            // Задачи
            $table->unsignedInteger('tasks_started')->default(0);
            $table->unsignedInteger('tasks_completed')->default(0);
            $table->unsignedInteger('tasks_correct')->default(0);

            // XP
            $table->unsignedInteger('xp_earned')->default(0);

            // Сессии
            $table->unsignedInteger('sessions_count')->default(0);
            $table->timestamp('first_activity_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->unique(['user_id', 'date'], 'unique_user_date');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_stats');
        Schema::dropIfExists('activity_log');
    }
};
