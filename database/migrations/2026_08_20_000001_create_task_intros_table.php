<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Вводные тексты к практико-ориентированным блокам (задания 1–5).
 *
 * В ОГЭ первые пять заданий — один сюжет с общим условием: план участка,
 * график тарифа, таблица форматов бумаги. Условие печатается один раз перед
 * первым заданием, а вопросы на него ссылаются. При переносе банка ФИПИ в базу
 * эти условия потерялись: задачи 1–5 приехали без контекста и вне экранной
 * вёрстки нечитаемы.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_intros', function (Blueprint $table) {
            $table->id();
            $table->string('bank', 8)->default('oge');

            // GUID вводного текста в открытом банке ФИПИ — по нему задания
            // ссылаются на свой сюжет.
            $table->char('guid', 32);

            $table->text('html');

            // Список иллюстраций вводного текста: относительные пути внутри
            // выгрузки (img/intro_GUID/...). Файлы лежат отдельно, здесь ссылки.
            $table->json('images')->nullable();

            $table->timestamps();

            $table->unique(['bank', 'guid']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->char('intro_guid', 32)->nullable()->after('fipi_guid');
            $table->index('intro_guid');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['intro_guid']);
            $table->dropColumn('intro_guid');
        });

        Schema::dropIfExists('task_intros');
    }
};
