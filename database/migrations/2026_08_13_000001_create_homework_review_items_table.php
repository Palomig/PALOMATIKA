<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homework_review_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('homework_assignment_id')->constrained('homework_assignments')->cascadeOnDelete();
            $t->foreignId('homework_topic_task_id')->constrained('homework_topic_tasks')->cascadeOnDelete();
            $t->text('note')->nullable();
            $t->foreignId('student_note_id')->nullable()->constrained('student_notes')->nullOnDelete();
            $t->enum('status', ['pending', 'planned', 'done'])->default('pending');
            $t->foreignId('lesson_session_id')->nullable()->constrained('lesson_sessions')->nullOnDelete();

            // dateTime, а не timestamp: MySQL на проде вешает ON UPDATE CURRENT_TIMESTAMP
            // на первую TIMESTAMP-колонку и молча перетирает дату при любом update.
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('resolved_at')->nullable();

            $t->index(['student_id', 'status']);
            // Уникального индекса на пару намеренно нет: разобранную на прошлом уроке
            // задачу можно отметить снова. Активная строка ищется по статусу.
            $t->index(['homework_assignment_id', 'homework_topic_task_id'], 'hri_assignment_task_idx');
            $t->index('lesson_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_review_items');
    }
};
