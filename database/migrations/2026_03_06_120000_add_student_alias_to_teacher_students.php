<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_students', function (Blueprint $table) {
            $table->string('student_alias', 80)->nullable()->after('source');
            $table->index(['teacher_id', 'student_alias'], 'teacher_students_teacher_alias_idx');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_students', function (Blueprint $table) {
            $table->dropIndex('teacher_students_teacher_alias_idx');
            $table->dropColumn('student_alias');
        });
    }
};
