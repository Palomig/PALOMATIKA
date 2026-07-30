<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Три вещи разом, потому что все про один сценарий «проверка домашки»:
 *
 * 1. Решение может занимать несколько страниц — фото становится несколько
 *    (до 10 на попытку), поэтому они переезжают в отдельную таблицу.
 *    Заодно перестают перетираться фото первой попытки.
 * 2. Учитель отмечает работу проверенной.
 * 3. Несделанное ДЗ остаётся долгом, когда ученику выдают новое.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('homework_solution_photos', function (Blueprint $table) {
            $table->id();
            // Имя короткое намеренно: `homework_topic_task_submission_id` даёт
            // имя внешнего ключа длиннее 64 символов, MySQL такое не принимает.
            $table->foreignId('submission_id')
                ->constrained('homework_topic_task_submissions')
                ->cascadeOnDelete();
            // Попытка, к которой относится страница решения (1 или 2).
            $table->unsignedTinyInteger('attempt_no')->default(1);
            $table->unsignedTinyInteger('position')->default(1);
            // Заполнено ровно одно: id в сервисе hw-photos либо путь на хостинге (фолбэк).
            $table->string('remote_id', 512)->nullable();
            $table->string('path')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_id', 'attempt_no'], 'hw_photos_submission_attempt_idx');
        });

        Schema::table('homework_assignments', function (Blueprint $table) {
            // dateTime, а не timestamp: на проде explicit_defaults_for_timestamp=OFF
            // навешивает ON UPDATE CURRENT_TIMESTAMP на первый TIMESTAMP таблицы.
            $table->dateTime('reviewed_at')->nullable()->after('completed_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
            // Момент, когда работа стала долгом (ученику выдали новое ДЗ, а это не сдано).
            $table->dateTime('debt_since')->nullable()->after('reviewed_by');
        });

        Schema::table('student_notes', function (Blueprint $table) {
            // Заметки по домашке живут в той же копилке, что и заметки с урока,
            // и так же видны в карточке ученика.
            $table->foreignId('homework_assignment_id')->nullable()->after('lesson_session_id')
                ->constrained('homework_assignments')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE student_notes MODIFY source ENUM('chat','lesson_button','manual','homework') NOT NULL DEFAULT 'chat'");
        }
    }

    public function down(): void
    {
        Schema::table('student_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('homework_assignment_id');
        });

        Schema::table('homework_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'debt_since']);
        });

        Schema::dropIfExists('homework_solution_photos');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE student_notes MODIFY source ENUM('chat','lesson_button','manual') NOT NULL DEFAULT 'chat'");
        }
    }
};
