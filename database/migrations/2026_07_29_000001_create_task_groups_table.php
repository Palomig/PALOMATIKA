<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Задание (`zadanie`) внутри блока — единица, которую рендерит интерфейс.
 *
 * Отдельная таблица, а не колонки в `tasks`: `zadanie.blade.php` получает
 * задание со списком задач внутри, и инструкция у них общая. Плоская схема
 * дублировала бы инструкцию у каждой задачи и потребовала переписать вьюхи.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_groups', function (Blueprint $table) {
            $table->id();

            // Направления не пересекаются: ОГЭ, ЕГЭ, ВПР, алгебра, геометрия.
            // ЕГЭ пока наполняется по-старому, но схема его предусматривает.
            $table->string('bank', 8);
            $table->unsignedTinyInteger('grade')->nullable();   // 5–8 для ВПР и алгебры
            $table->string('topic', 4);                          // «01»…«25», ведущий ноль значащий

            $table->unsignedSmallInteger('block_number');
            $table->string('block_title', 190)->nullable();
            $table->unsignedSmallInteger('zadanie_number');
            $table->unsignedSmallInteger('position')->default(0);   // кураторский порядок

            $table->text('instruction')->nullable();
            $table->string('type', 40);
            $table->string('svg_type', 40)->nullable();

            // Всё, что зависит от типа задания: options, geometry, points,
            // options_render_mode, illustration. Сорок один тип несёт разные
            // поля — колонками это были бы сорок почти всегда пустых столбцов.
            $table->json('payload')->nullable();

            $table->string('status', 16)->default('draft');
            $table->string('source', 24)->default('palomatika');

            $table->timestamps();

            $table->index(['bank', 'grade', 'topic', 'position'], 'task_groups_order_index');
            $table->index(['bank', 'topic', 'status'], 'task_groups_lookup_index');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_groups');
    }
};
