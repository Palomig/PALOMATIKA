<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Уборка тестовых ДЗ после разбора поломки сдачи 31.07.2026 (браузерные
 * прогоны: обычный путь, фолбэк, обрыв сети). Правило то же, что в
 * 000002/000004 — только работы QA-учителя и только по это окно дат.
 *
 * Задачи, назначения, сабмишны и страницы решения уходят каскадом
 * за `homeworks`; файлы удалены руками из обоих хранилищ.
 */
return new class extends Migration {
    public function up(): void
    {
        $qaTeacherId = DB::table('users')->where('email', 'qa-teacher@palomatika.ru')->value('id');

        if ($qaTeacherId === null) {
            return;
        }

        DB::table('student_notes')
            ->where('teacher_id', $qaTeacherId)
            ->where('source', 'homework')
            ->where('created_at', '<', '2026-08-01 00:00:00')
            ->delete();

        DB::table('homeworks')
            ->where('teacher_id', $qaTeacherId)
            ->where('assigned_at', '<', '2026-08-01 00:00:00')
            ->delete();
    }

    public function down(): void
    {
        // Тестовые данные восстанавливать нечем и незачем.
    }
};
