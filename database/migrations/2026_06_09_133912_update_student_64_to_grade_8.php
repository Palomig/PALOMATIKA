<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('id', 64)
            ->where('role', 'student')
            ->update([
                'grade_num' => 8,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('id', 64)
            ->where('role', 'student')
            ->update([
                'grade_num' => 7,
                'updated_at' => now(),
            ]);
    }
};
