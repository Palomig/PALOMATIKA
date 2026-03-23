<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавить 'parent' в enum ролей
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','admin','parent') NOT NULL DEFAULT 'student'");

        // Связка родитель-ученик (привязка вручную)
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('relation', 50)->default('parent'); // parent, mother, father, guardian
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student'");
    }
};
