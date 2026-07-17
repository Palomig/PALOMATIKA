<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_session_tasks', function (Blueprint $table) {
            // null = задача для всех; id = персональная (видит только этот ученик)
            $table->foreignId('assigned_student_id')->nullable()->after('lesson_session_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_session_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_student_id');
        });
    }
};
