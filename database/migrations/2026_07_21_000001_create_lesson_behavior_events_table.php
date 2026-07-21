<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_behavior_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_session_task_id')->nullable()
                ->constrained('lesson_session_tasks')->nullOnDelete();
            $table->enum('kind', ['copy_task', 'paste_answer', 'resume']);
            $table->json('meta')->nullable();
            // DATETIME, не TIMESTAMP — см. lesson_activity_intervals (ON UPDATE ловушка MySQL)
            $table->dateTime('occurred_at');

            $table->index(['lesson_session_id', 'student_id', 'occurred_at'], 'lbe_session_student_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_behavior_events');
    }
};
