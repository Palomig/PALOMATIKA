<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oge_attempt_task_timings', function (Blueprint $table) {
            $table->unsignedInteger('heartbeat_count')->default(0)->after('focus_count');
            $table->unsignedInteger('blur_count')->default(0)->after('heartbeat_count');
            $table->timestamp('first_focused_at')->nullable()->after('blur_count');
        });

        Schema::table('oge_attempt_task_details', function (Blueprint $table) {
            $table->string('subtype', 96)->nullable()->after('svg_type');
            $table->string('source', 64)->nullable()->after('section');
            $table->string('task_fingerprint', 64)->nullable()->after('task_key');
            $table->index(['topic_id', 'task_type', 'subtype'], 'oge_task_details_topic_type_subtype_idx');
        });

        try {
            Schema::table('student_topic_mastery', function (Blueprint $table) {
                $table->dropUnique('stm_unique_composite');
            });
        } catch (\Throwable $e) {
            try {
                Schema::table('student_topic_mastery', function (Blueprint $table) {
                    $table->dropUnique('stm_unique');
                });
            } catch (\Throwable $e) {
            }
        }

        Schema::table('student_topic_mastery', function (Blueprint $table) {
            $table->string('subtype', 96)->nullable()->after('svg_type');
            $table->json('recent_outcomes')->nullable()->after('mastery_score');
            $table->unsignedInteger('current_correct_streak')->default(0)->after('recent_outcomes');
            $table->unsignedInteger('current_incorrect_streak')->default(0)->after('current_correct_streak');
            $table->boolean('last_outcome')->nullable()->after('current_incorrect_streak');
            $table->unique(['student_id', 'topic_id', 'task_type', 'svg_type', 'subtype', 'section'], 'stm_unique_composite');
            $table->index(['student_id', 'topic_id', 'subtype'], 'stm_student_topic_subtype_idx');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('student_topic_mastery', function (Blueprint $table) {
                $table->dropUnique('stm_unique_composite');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('student_topic_mastery', function (Blueprint $table) {
            $table->dropIndex('stm_student_topic_subtype_idx');
            $table->dropColumn([
                'subtype',
                'recent_outcomes',
                'current_correct_streak',
                'current_incorrect_streak',
                'last_outcome',
            ]);
            $table->unique(['student_id', 'topic_id', 'task_type', 'svg_type', 'section'], 'stm_unique_composite');
        });

        Schema::table('oge_attempt_task_details', function (Blueprint $table) {
            $table->dropIndex('oge_task_details_topic_type_subtype_idx');
            $table->dropColumn(['subtype', 'source', 'task_fingerprint']);
        });

        Schema::table('oge_attempt_task_timings', function (Blueprint $table) {
            $table->dropColumn(['heartbeat_count', 'blur_count', 'first_focused_at']);
        });
    }
};
