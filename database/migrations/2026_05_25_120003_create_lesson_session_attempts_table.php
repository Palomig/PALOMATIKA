<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_session_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignId('lesson_session_task_id')->constrained('lesson_session_tasks')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('answer_raw');
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();

            $table->unique(
                ['lesson_session_id', 'student_id', 'lesson_session_task_id'],
                'lsa_session_student_task_unique'
            );
            $table->index(['student_id', 'lesson_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_session_attempts');
    }
};
