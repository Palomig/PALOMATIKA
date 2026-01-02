<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Темы (номера ОГЭ)
        Schema::create('topics', function (Blueprint $table) {
            $table->id();

            $table->string('oge_number', 10); // '01-05', '06'...'25'
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Теория (опционально)
            $table->longText('theory_content')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Шаблоны пазлов (для типовых задач)
        Schema::create('puzzle_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            // JSON структура шагов
            $table->json('steps_json');

            $table->timestamps();
        });

        // Задачи
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();

            // Идентификация
            $table->string('external_id', 100)->nullable(); // ID из исходного JSON

            // Контент
            $table->text('text');
            $table->text('text_html')->nullable(); // с KaTeX формулами
            $table->string('image_path')->nullable();

            // Классификация
            $table->string('subtopic')->nullable();
            $table->tinyInteger('difficulty')->default(1); // 1-5

            // Правильный ответ (для проверки)
            $table->string('correct_answer')->nullable();
            $table->enum('answer_type', ['number', 'text', 'sequence'])->default('number');

            // Шаблон пазла
            $table->foreignId('puzzle_template_id')->nullable()->constrained()->nullOnDelete();

            // Статистика
            $table->unsignedInteger('times_shown')->default(0);
            $table->unsignedInteger('times_correct')->default(0);
            $table->unsignedInteger('avg_time_seconds')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('topic_id');
            $table->index('difficulty');
        });

        // Связь задач с навыками
        Schema::create('task_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();

            // Вес влияния (насколько задача проверяет этот навык)
            $table->decimal('relevance', 3, 2)->default(1.00);

            $table->unique(['task_id', 'skill_id'], 'unique_task_skill');
        });

        // Шаги пазла для конкретной задачи
        Schema::create('task_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('step_number');
            $table->text('instruction'); // "Запиши теорему Пифагора"

            // Формат: c² = [___] + [___]
            $table->text('template');

            // Правильные ответы для пропусков
            $table->json('correct_answers'); // ["a²", "b²"]

            $table->timestamp('created_at')->useCurrent();

            $table->index(['task_id', 'step_number']);
        });

        // Блоки пазла (варианты ответов)
        Schema::create('step_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_step_id')->constrained()->cascadeOnDelete();

            $table->string('content'); // "a²", "b²", "a+b"
            $table->string('content_html')->nullable(); // с KaTeX

            $table->boolean('is_correct');
            $table->boolean('is_trap')->default(false); // ловушка (типичная ошибка)

            // Какой навык тестирует этот блок
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();

            // Объяснение почему неправильно (для ловушек)
            $table->text('trap_explanation')->nullable();

            $table->integer('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_blocks');
        Schema::dropIfExists('task_steps');
        Schema::dropIfExists('task_skills');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('puzzle_templates');
        Schema::dropIfExists('topics');
    }
};
