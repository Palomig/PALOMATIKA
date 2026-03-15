<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_students', function (Blueprint $table) {
            $table->string('evrium_name', 100)->nullable()->after('student_alias');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_students', function (Blueprint $table) {
            $table->dropColumn('evrium_name');
        });
    }
};
