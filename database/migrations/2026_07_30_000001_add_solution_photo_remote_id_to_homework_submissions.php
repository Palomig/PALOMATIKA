<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('homework_topic_task_submissions', function (Blueprint $table) {
            // Фото решения живёт в сервисе hw-photos на VPS: тут только его
            // подписанный идентификатор. `solution_photo_path` остаётся для
            // фолбэка — файлов, сохранённых на самом хостинге.
            $table->string('solution_photo_remote_id', 512)->nullable()->after('solution_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('homework_topic_task_submissions', function (Blueprint $table) {
            $table->dropColumn('solution_photo_remote_id');
        });
    }
};
