<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Банк «Скиллы» (`skills`) добавляется в пикер урока, а колонка `bank`
 * у задач урока — enum: без нового значения вставка задачи упадёт на
 * «Data truncated for column 'bank'», как было с базой ЕГЭ.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE lesson_session_tasks MODIFY COLUMN bank ENUM('oge', 'ege', 'ege_b', 'vpr', 'alg-topic', 'alg-skill', 'skills') NOT NULL");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('lesson_session_tasks')->where('bank', 'skills')->delete();

        DB::statement("ALTER TABLE lesson_session_tasks MODIFY COLUMN bank ENUM('oge', 'ege', 'ege_b', 'vpr', 'alg-topic', 'alg-skill') NOT NULL");
    }
};
