<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Вторая уборка за тот же день: проверка многостраничных решений, заметок,
 * отметки «проверено» и долгов на проде снова оставила тестовые ДЗ на
 * QA-аккаунтах. Чистим по тому же правилу, что и в 000002 — только работы
 * QA-учителя и только за это окно дат, чтобы будущие QA-демо не пострадали.
 *
 * Заметки, фото, задачи и назначения уходят каскадом за `homeworks`;
 * файлы удалены руками из обоих хранилищ.
 */
return new class extends Migration {
    public function up(): void
    {
        $qaTeacherId = DB::table('users')->where('email', 'qa-teacher@palomatika.ru')->value('id');

        if ($qaTeacherId === null) {
            return;
        }

        // Заметки переживают удаление назначения (nullOnDelete), а тестовым им
        // в копилке ученика делать нечего.
        DB::table('student_notes')
            ->where('teacher_id', $qaTeacherId)
            ->where('source', 'homework')
            ->where('created_at', '<', '2026-07-31 00:00:00')
            ->delete();

        DB::table('homeworks')
            ->where('teacher_id', $qaTeacherId)
            ->where('assigned_at', '<', '2026-07-31 00:00:00')
            ->delete();
    }

    public function down(): void
    {
        // Тестовые данные восстанавливать нечем и незачем.
    }
};
