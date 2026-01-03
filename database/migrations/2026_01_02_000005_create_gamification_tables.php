<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Стрики
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);

            $table->date('last_activity_date')->nullable();
            $table->date('streak_protected_until')->nullable(); // "заморозка" стрика

            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        // Бейджи (справочник)
        Schema::create('badges', function (Blueprint $table) {
            $table->id();

            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // путь к иконке или эмодзи

            // Условие получения
            $table->enum('condition_type', ['streak', 'tasks', 'skill', 'league', 'duel', 'referral', 'special']);
            $table->integer('condition_value')->nullable(); // например: 7 дней стрика
            $table->json('condition_json')->nullable(); // сложные условия

            // Редкость
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');

            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->useCurrent();
        });

        // Полученные бейджи
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();

            $table->timestamp('earned_at');
            $table->boolean('is_showcased')->default(false); // показывать в профиле

            $table->unique(['user_id', 'badge_id'], 'unique_user_badge');
        });

        // Лиги
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();

            $table->string('slug', 50)->unique(); // 'bronze', 'silver', etc.
            $table->string('name', 100);
            $table->tinyInteger('level'); // 1-8
            $table->string('icon')->nullable();
            $table->string('color', 20)->nullable(); // hex цвет

            // Правила
            $table->integer('promote_top')->default(3); // топ N повышаются
            $table->integer('demote_bottom')->default(3); // последние N понижаются

            $table->timestamp('created_at')->useCurrent();
        });

        // Участие в лигах (еженедельно)
        Schema::create('league_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();

            // Период (понедельник-воскресенье)
            $table->date('week_start');

            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('rank_position')->nullable(); // место по итогам недели

            // Результат
            $table->enum('result', ['promoted', 'stayed', 'demoted'])->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'week_start'], 'unique_user_week');
            $table->index(['league_id', 'week_start', 'xp_earned']);
        });

        // Дуэли
        Schema::create('duels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('challenger_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('opponent_id')->nullable()->constrained('users')->nullOnDelete();

            // Настройки
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('tasks_count')->default(5);

            // Статус
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled', 'expired'])->default('pending');

            // Результаты
            $table->unsignedInteger('challenger_correct')->nullable();
            $table->unsignedInteger('challenger_time_seconds')->nullable();
            $table->unsignedInteger('opponent_correct')->nullable();
            $table->unsignedInteger('opponent_time_seconds')->nullable();

            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();

            // Даты
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->index('challenger_id');
            $table->index('opponent_id');
            $table->index('status');
        });

        // Задачи дуэли
        Schema::create('duel_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('task_order');
        });

        // Командные челленджи
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            // Тип
            $table->enum('challenge_type', ['class_vs_class', 'school_vs_school', 'marathon']);

            // Период
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            // Настройки
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('min_participants')->default(3);

            $table->enum('status', ['upcoming', 'active', 'completed'])->default('upcoming');

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Команды челленджа
        Schema::create('challenge_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedInteger('rank_position')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });

        // Участники команды
        Schema::create('challenge_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('challenge_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('xp_contributed')->default(0);

            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['team_id', 'user_id'], 'unique_team_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_team_members');
        Schema::dropIfExists('challenge_teams');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('duel_tasks');
        Schema::dropIfExists('duels');
        Schema::dropIfExists('league_participants');
        Schema::dropIfExists('leagues');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('user_streaks');
    }
};
