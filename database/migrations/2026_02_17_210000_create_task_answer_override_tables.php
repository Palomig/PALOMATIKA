<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_answer_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('task_key')->unique();
            $table->string('answer');
            $table->enum('source', ['manual', 'ai'])->default('manual');
            $table->foreignId('updated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('source');
        });

        Schema::create('task_answer_override_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('override_id')->constrained('task_answer_overrides')->cascadeOnDelete();
            $table->text('old_answer')->nullable();
            $table->text('new_answer');
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_answer_override_logs');
        Schema::dropIfExists('task_answer_overrides');
    }
};
