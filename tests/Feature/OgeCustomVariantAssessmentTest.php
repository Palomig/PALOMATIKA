<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OgeCustomVariantAssessmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('oge_attempt_scorings');
        Schema::dropIfExists('oge_attempt_task_timings');
        Schema::dropIfExists('oge_attempt_answers');
        Schema::dropIfExists('oge_attempt_events');
        Schema::dropIfExists('oge_attempts');
        Schema::dropIfExists('oge_variants');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role')->default('student');
            $table->timestamps();
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable();
            $table->string('title')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id');
            $table->foreignId('student_id');
            $table->string('status')->default('active');
            $table->json('device_meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempt_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedInteger('seq');
            $table->string('event_type', 64);
            $table->unsignedTinyInteger('task_number')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('client_ts')->nullable();
            $table->timestamp('server_ts')->nullable();
        });

        Schema::create('oge_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->string('current_answer', 255)->nullable();
            $table->unsignedInteger('commits_count')->default(0);
            $table->timestamp('first_committed_at')->nullable();
            $table->timestamp('last_committed_at')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempt_task_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->unsignedBigInteger('active_ms')->default(0);
            $table->unsignedInteger('focus_count')->default(0);
            $table->timestamp('last_focus_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempt_scorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->boolean('is_correct')->nullable();
            $table->string('correct_answer', 255)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function test_commit_accepts_custom_task_number_below_6(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'custm001',
            'config_json' => [
                'source' => 'custom_random',
                'custom_task_numbers' => [1, 6],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/tasks/1/commit", [
                'answer' => '42',
                'client_ts' => now()->toIso8601String(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('oge_attempt_answers', [
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'current_answer' => '42',
        ]);
    }

    public function test_teacher_results_page_renders_custom_task_answers(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $variant = OgeVariant::create([
            'hash' => 'custm002',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Кастомный вариант',
            'config_json' => [
                'source' => 'custom_random',
                'custom_task_numbers' => [1, 6],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'current_answer' => 'ans-task-1',
            'commits_count' => 1,
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => 'ans-task-6',
            'commits_count' => 1,
        ]);

        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'active_ms' => 11000,
            'focus_count' => 1,
        ]);

        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'active_ms' => 22000,
            'focus_count' => 1,
        ]);

        $this->actingAs($teacher)
            ->get("/teacher/oge/teachers/{$teacher->id}/variants")
            ->assertOk()
            ->assertSee('custm002');

        $this->actingAs($teacher)
            ->get("/teacher/oge/variants/{$variant->id}/results")
            ->assertOk()
            ->assertSee('ans-task-1')
            ->assertSee('ans-task-6');
    }

    public function test_opening_custom_hash_persists_variant_for_teacher_review(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $hash = 'a3fdt77c';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '01',
            'topic_title' => 'Тема 1',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Маркер кастома',
            'type' => 'word_problem',
            'task' => [
                'text' => 'Найдите ответ',
                'answer' => '5',
            ],
        ]], now()->addMinutes(5));

        $this->actingAs($teacher)
            ->get("/oge/{$hash}")
            ->assertOk();

        $variant = OgeVariant::where('hash', $hash)->first();

        $this->assertNotNull($variant);
        $this->assertSame($teacher->id, (int) $variant->owner_teacher_id);
        $this->assertSame('custom_random', $variant->config_json['source'] ?? null);
    }
}
