<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-task detail snapshot: captures the full fingerprint of each task
        // within an attempt at scoring time. Enables granular analytics by
        // task_type/svg_type/section without needing to rebuild the variant.
        Schema::create('oge_attempt_task_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attempt_id');
            $table->unsignedTinyInteger('task_number');  // OGE slot 6-19
            $table->string('topic_id', 4);               // "06".."19"
            $table->unsignedInteger('block_number');
            $table->unsignedInteger('zadanie_number');
            $table->unsignedInteger('task_index')->nullable(); // task.id inside zadanie
            $table->string('task_type', 64);              // expression, geometry, matching, etc.
            $table->string('svg_type', 64)->nullable();   // bisector, tangent_lines, etc. (geometry subtypes)
            $table->string('section', 128)->nullable();   // section/skill tag from JSON
            $table->string('task_key', 160)->nullable();  // e.g. topic_15_block_1_zadanie_3_task_5
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['attempt_id', 'task_number']);
            $table->index(['topic_id', 'task_type']);

            $table->foreign('attempt_id')
                ->references('id')->on('oge_attempts')
                ->cascadeOnDelete();
        });

        // Aggregated per-student per-topic-type performance stats.
        // Updated after each scored attempt. Used for analytics & adaptive generation.
        Schema::create('student_topic_mastery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('topic_id', 4);
            $table->string('task_type', 64);
            $table->string('svg_type', 64)->nullable();
            $table->string('section', 128)->nullable();

            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedBigInteger('total_active_ms')->default(0);
            $table->unsignedBigInteger('avg_active_ms')->default(0); // computed: total_active_ms / attempts_count
            $table->decimal('accuracy', 5, 4)->default(0);           // computed: correct / attempts (0.0000-1.0000)
            $table->decimal('mastery_score', 5, 4)->default(0.5000); // EWA mastery (0=weak, 1=strong)
            $table->timestamp('last_attempted_at')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'topic_id', 'task_type', 'svg_type', 'section'], 'stm_unique_composite');
            $table->index(['student_id', 'mastery_score']);
            $table->index(['student_id', 'last_attempted_at']);

            $table->foreign('student_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_topic_mastery');
        Schema::dropIfExists('oge_attempt_task_details');
    }
};
