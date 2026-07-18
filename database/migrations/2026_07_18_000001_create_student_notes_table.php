<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('lesson_session_id')->nullable()->constrained('lesson_sessions')->nullOnDelete();
            $t->string('task_ref')->nullable();
            $t->string('topic_tag')->nullable();
            $t->enum('kind', ['weakness', 'strength', 'todo', 'general'])->default('general');
            $t->text('body');
            $t->enum('source', ['chat', 'lesson_button', 'manual'])->default('chat');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['student_id', 'kind']);
            $t->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notes');
    }
};
