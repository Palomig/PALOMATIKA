<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Домашние задания
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();

            // Тип
            $table->enum('homework_type', ['specific_tasks', 'topic_random', 'weak_skills']);

            // Настройки
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('tasks_count')->nullable(); // для random

            // Сроки
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('deadline_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });

        // Конкретные задачи ДЗ (если specific_tasks)
        Schema::create('homework_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_id')->constrained('homeworks')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->integer('task_order')->default(0);
        });

        // Назначение ДЗ ученикам
        Schema::create('homework_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_id')->constrained('homeworks')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Прогресс
            $table->enum('status', ['assigned', 'started', 'completed'])->default('assigned');
            $table->unsignedInteger('tasks_total')->default(0);
            $table->unsignedInteger('tasks_completed')->default(0);
            $table->unsignedInteger('tasks_correct')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['homework_id', 'student_id'], 'unique_hw_student');
        });

        // Добавляем FK в attempts теперь когда homeworks существует
        Schema::table('attempts', function (Blueprint $table) {
            $table->foreign('homework_id')->references('id')->on('homeworks')->nullOnDelete();
            $table->foreign('duel_id')->references('id')->on('duels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropForeign(['homework_id']);
            $table->dropForeign(['duel_id']);
        });

        Schema::dropIfExists('homework_assignments');
        Schema::dropIfExists('homework_tasks');
        Schema::dropIfExists('homeworks');
    }
};
