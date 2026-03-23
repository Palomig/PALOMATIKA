<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('lesson_schedule')->nullOnDelete();
            $table->date('lesson_date');
            $table->time('start_time')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'cancelled'])->default('present');
            $table->text('note')->nullable();
            $table->string('source', 50)->default('manual'); // manual, evrium_bot
            $table->timestamps();

            $table->unique(['student_id', 'lesson_date', 'start_time'], 'unique_attendance');
            $table->index(['student_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attendance');
    }
};
