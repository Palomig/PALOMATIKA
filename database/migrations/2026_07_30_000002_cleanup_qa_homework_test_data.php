<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Уборка тестовых ДЗ, созданных при отладке фото-домашки 30.07.2026.
 *
 * Записи для QA-ученика выдавались с QA-аккаунта учителя, поэтому чистим строго
 * по нему и только по этому окну дат — будущие QA-демо не пострадают. Задачи,
 * назначения и сабмишны уходят каскадом за `homeworks` (см. миграцию
 * 2026_04_23_000001). Файлы фото удалены руками: на хостинге
 * `storage/app/public/homework_solutions/{6,7,8}`, в сервисе hw-photos — каталог
 * assignment'а 8.
 */
return new class extends Migration {
    public function up(): void
    {
        $qaTeacherId = DB::table('users')->where('email', 'qa-teacher@palomatika.ru')->value('id');

        if ($qaTeacherId === null) {
            return;
        }

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
