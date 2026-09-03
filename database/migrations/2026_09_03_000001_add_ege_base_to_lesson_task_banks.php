<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Банк ЕГЭ (Б) — самостоятельный банк `ege_b`, и в пикере урока он есть,
 * а enum колонки `bank` остался с пятью банками 2026-05: вставка задачи базы
 * падала на «Data truncated for column 'bank'».
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE lesson_session_tasks MODIFY COLUMN bank ENUM('oge', 'ege', 'ege_b', 'vpr', 'alg-topic', 'alg-skill') NOT NULL");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Задачи базы в уроках при откате стали бы невалидными — сносим их,
        // иначе ALTER обрежет значение до пустой строки.
        DB::table('lesson_session_tasks')->where('bank', 'ege_b')->delete();

        DB::statement("ALTER TABLE lesson_session_tasks MODIFY COLUMN bank ENUM('oge', 'ege', 'vpr', 'alg-topic', 'alg-skill') NOT NULL");
    }
};
