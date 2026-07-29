<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Сама задача — то, на что ученик даёт ответ.
 *
 * Ключевое отличие от JSON: ссылка на задачу перестаёт быть её позицией.
 * Сейчас задачу адресует строка `topic_15_block_1_zadanie_3_task_5`, и любая
 * перестановка внутри задания уводит ссылки в пулах, ДЗ, уроках и аналитике.
 * `legacy_task_key` хранит прежний ключ, чтобы история попыток продолжала
 * резолвиться, а новые ссылки идут по неизменному `id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_group_id')->constrained('task_groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->string('type', 40)->nullable();   // переопределение типа группы

            // text / expression / options / svg / params / statements /
            // graph_options и прочее, что зависит от типа задания.
            $table->json('payload')->nullable();

            $table->text('answer')->nullable();
            // Происхождение ответа: pal, calc, gpt, geo, px, graph, set, tex,
            // manual. Нужен, чтобы отличать надёжно вычисленный ответ от
            // требующего перепроверки — в банке ФИПИ это уже размечено.
            $table->string('answer_src', 16)->nullable();

            $table->string('status', 16)->default('draft');
            // palomatika | fipi | palomatika_legacy (старый банк ОГЭ,
            // отключённый после переноса, но сохранённый для истории)
            $table->string('source', 24)->default('palomatika');

            // GUID задания в открытом банке ФИПИ: по нему check_updates.py
            // находит новые и пропавшие задания одним запросом вместо перебора.
            $table->char('fipi_guid', 32)->nullable();

            $table->string('legacy_task_key', 160)->nullable();

            // Данные, из которых нарисован SVG (обмеры чертежей ФИПИ). Сам SVG
            // переносится статикой, но без модели его нельзя ни перекрасить под
            // другую тему, ни изменить размер — пришлось бы заново мерить
            // растры. Хранение стоит ноль, право передумать сохраняет.
            $table->json('svg_model')->nullable();

            $table->timestamps();

            $table->index(['task_group_id', 'position']);
            $table->index(['source', 'status']);
            $table->unique('fipi_guid');
            $table->unique('legacy_task_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
