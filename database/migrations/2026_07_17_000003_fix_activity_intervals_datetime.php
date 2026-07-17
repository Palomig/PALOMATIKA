<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * На проде столбцы созданы как TIMESTAMP: первый (started_at) при
     * explicit_defaults_for_timestamp=OFF неявно получил ON UPDATE CURRENT_TIMESTAMP
     * и затирался при закрытии интервала. Переводим в DATETIME (нет ни ON UPDATE,
     * ни конвертации таймзоны). Битые тестовые строки чистим.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::table('lesson_activity_intervals')->truncate();
        DB::statement('ALTER TABLE lesson_activity_intervals
            MODIFY started_at DATETIME NOT NULL,
            MODIFY ended_at DATETIME NULL,
            MODIFY updated_at DATETIME NULL');
    }

    public function down(): void
    {
        // Возврат к TIMESTAMP не нужен и небезопасен — оставляем DATETIME.
    }
};
