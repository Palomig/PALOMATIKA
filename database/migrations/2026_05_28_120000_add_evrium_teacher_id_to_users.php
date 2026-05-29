<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Привязка учителя palomatika к teacher_id в Evrium (внешняя CRM «zarplata»).
 *
 * Раньше расписание тянулось с жёстко зашитым teacher_id=1 — все учителя видели
 * одно и то же расписание. Теперь у каждого учителя свой evrium_teacher_id, и
 * fetchEvriumSchedule() показывает только его уроки (null → пустое расписание).
 *
 * Бэкофилл существующих: владелец → 1, остальные учителя — по порядку регистрации
 * (id ASC), начиная со следующего свободного номера. Новым учителям номер
 * выдаётся при промоушене (PromoteUserToTeacher).
 */
return new class extends Migration
{
    private const OWNER_EMAIL = 'dzhuzeppeoreo@gmail.com';

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('evrium_teacher_id')->nullable()->after('role');
        });

        // Владелец платформы (Стас) — Evrium teacher_id = 1.
        DB::table('users')->where('email', self::OWNER_EMAIL)->update(['evrium_teacher_id' => 1]);

        // Остальные учителя — по порядку регистрации, со следующего свободного номера.
        $next = (int) (DB::table('users')->max('evrium_teacher_id') ?? 0) + 1;
        $teacherIds = DB::table('users')
            ->where('role', 'teacher')
            ->whereNull('evrium_teacher_id')
            ->orderBy('id')
            ->pluck('id');

        foreach ($teacherIds as $uid) {
            DB::table('users')->where('id', $uid)->update(['evrium_teacher_id' => $next]);
            $next++;
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('evrium_teacher_id');
        });
    }
};
