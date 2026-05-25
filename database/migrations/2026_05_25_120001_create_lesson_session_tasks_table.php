<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_session_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->enum('bank', ['oge', 'ege', 'vpr', 'alg-topic', 'alg-skill']);
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('topic_id', 16)->nullable();
            $table->string('skill_slug', 64)->nullable();
            $table->string('task_ref', 255);
            $table->json('task_payload');
            $table->text('correct_answer');
            $table->timestamps();

            $table->unique(['lesson_session_id', 'position'], 'lst_session_position_unique');
            $table->index('lesson_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_session_tasks');
    }
};
