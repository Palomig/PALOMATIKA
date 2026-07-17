<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_activity_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('kind', ['present', 'away']);
            // DATETIME (не TIMESTAMP): на MySQL с explicit_defaults_for_timestamp=OFF
            // первый TIMESTAMP-столбец неявно получает ON UPDATE CURRENT_TIMESTAMP,
            // из-за чего started_at затирается при UPDATE. DATETIME от этого свободен.
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['lesson_session_id', 'student_id', 'started_at'], 'lai_session_student_started');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_activity_intervals');
    }
};
