<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('source', ['schedule', 'invite'])->default('schedule');
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['lesson_session_id', 'student_id'], 'lsp_session_student_unique');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_session_participants');
    }
};
