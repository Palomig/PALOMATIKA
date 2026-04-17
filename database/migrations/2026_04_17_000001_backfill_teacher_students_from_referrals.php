<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Backfill teacher_students rows for students who were referred by a teacher/admin
     * but never got the link row (e.g. Telegram Mini App users who authenticated before
     * auto-linking was added).
     */
    public function up(): void
    {
        $rows = DB::table('users as s')
            ->join('users as t', 's.referred_by_user_id', '=', 't.id')
            ->leftJoin('teacher_students as ts', function ($join) {
                $join->on('ts.teacher_id', '=', 't.id')->on('ts.student_id', '=', 's.id');
            })
            ->whereIn('t.role', ['teacher', 'admin'])
            ->where('s.role', 'student')
            ->whereNull('ts.id')
            ->select('t.id as teacher_id', 's.id as student_id')
            ->get();

        $now = now();
        foreach ($rows as $row) {
            DB::table('teacher_students')->insert([
                'teacher_id' => $row->teacher_id,
                'student_id' => $row->student_id,
                'source' => 'referral',
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Non-reversible backfill — leaving rows in place is safe.
    }
};
