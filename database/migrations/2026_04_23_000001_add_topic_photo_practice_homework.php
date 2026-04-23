<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE homeworks MODIFY homework_type ENUM('specific_tasks','topic_random','weak_skills','full_variant','topic_practice','topic_photo_practice') NOT NULL");
        }

        Schema::create('homework_topic_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_id')->constrained('homeworks')->cascadeOnDelete();
            $table->unsignedTinyInteger('topic_number');
            $table->unsignedInteger('task_order')->default(0);
            $table->json('task_payload');
            $table->string('correct_answer')->nullable();
            $table->timestamps();

            $table->unique(['homework_id', 'task_order']);
        });

        Schema::create('homework_topic_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_assignment_id')->constrained('homework_assignments')->cascadeOnDelete();
            $table->foreignId('homework_topic_task_id')->constrained('homework_topic_tasks')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempts_count')->default(0);
            $table->string('first_answer')->nullable();
            $table->string('second_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->string('solution_photo_path')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['homework_assignment_id', 'homework_topic_task_id'], 'unique_hw_topic_submission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_topic_task_submissions');
        Schema::dropIfExists('homework_topic_tasks');

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE homeworks MODIFY homework_type ENUM('specific_tasks','topic_random','weak_skills','full_variant','topic_practice') NOT NULL");
    }
};
