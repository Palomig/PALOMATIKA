<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('lesson_schedule');
        Schema::create('lesson_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->comment('1=Mon ... 7=Sun');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['teacher_id', 'student_id', 'day_of_week', 'start_time'], 'lesson_sched_unique');
            $table->index(['teacher_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_schedule');
    }
};
