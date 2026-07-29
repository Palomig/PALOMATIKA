<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Данные темы вне списка блоков.
 *
 * В файле темы кроме `blocks` лежит ещё десяток ключей: `topic_id`, `meta`,
 * `exam_type`, `grade`, `exported_at`, `svg_baked`, а у алгебры вдобавок
 * `curriculum`, `micro_skills` и `homework_sets`. Часть из них читает код
 * (`OgeAttemptService` берёт `topic_id`, `GeomTaskDataService` — `meta`),
 * поэтому терять их при переносе нельзя.
 *
 * Уникального индекса нет намеренно: `grade` у ОГЭ и ЕГЭ равен NULL, а в MySQL
 * NULL-ы в уникальном индексе считаются различными, так что защита была бы
 * мнимой. Единственный писатель — `tasks:import-json`, и он пересобирает тему
 * целиком: сначала удаляет строку, потом вставляет новую.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_topics', function (Blueprint $table) {
            $table->id();
            $table->string('bank', 8);
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('topic', 4);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['bank', 'grade', 'topic'], 'task_topics_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_topics');
    }
};
