<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Базовые навыки (иерархия)
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('skills')->cascadeOnDelete();

            $table->string('name'); // "Сложение дробей"
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Категория для группировки
            $table->string('category', 100)->nullable(); // "Алгебра", "Геометрия"

            // Связь с номерами ОГЭ
            $table->json('oge_numbers')->nullable(); // ["06", "07", "12"]

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('parent_id');
            $table->index('category');
        });

        // Зависимости между навыками (пререквизиты)
        Schema::create('skill_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requires_skill_id')->constrained('skills')->cascadeOnDelete();

            // Минимальный вес пререквизита для изучения
            $table->decimal('min_weight', 3, 2)->default(0.5);

            $table->unique(['skill_id', 'requires_skill_id'], 'unique_dependency');
        });

        // Веса навыков пользователя
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();

            $table->decimal('weight', 4, 3)->default(0.000); // 0.000 - 1.000

            // Статистика
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);

            $table->timestamp('last_practiced_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'skill_id'], 'unique_user_skill');
            $table->index(['user_id', 'weight']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skill_dependencies');
        Schema::dropIfExists('skills');
    }
};
