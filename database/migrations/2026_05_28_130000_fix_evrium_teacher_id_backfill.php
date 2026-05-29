<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Корректировка бэкофилла evrium_teacher_id.
 *
 * Предыдущая миграция пинила владельца по email dzhuzeppeoreo@gmail.com и
 * нумеровала только role=teacher. На проде владелец (Стас Никитин, id 1) —
 * role=admin с email=NULL, поэтому пин не сработал, а Руслан/служебный QA-аккаунт
 * получили неверные номера.
 *
 * Бизнес-правило (со слов владельца): Стас = 1, Руслан = 2.
 * Условия по id+name/email — чтобы фикс срабатывал только на проде и был no-op
 * в dev/test, где этих записей нет.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Стас Никитин (владелец, admin) → 1
        DB::table('users')->where('id', 1)->where('name', 'Стас Никитин')->update(['evrium_teacher_id' => 1]);

        // Руслан (teacher) → 2
        DB::table('users')->where('id', 10)->where('name', 'Руслан')->update(['evrium_teacher_id' => 2]);

        // Служебный QA-аккаунт не должен тянуть чужое расписание.
        DB::table('users')->where('email', 'qa-teacher@palomatika.ru')->update(['evrium_teacher_id' => null]);
    }

    public function down(): void
    {
        // Откат не имеет смысла — корректировка данных. Оставляем no-op.
    }
};
