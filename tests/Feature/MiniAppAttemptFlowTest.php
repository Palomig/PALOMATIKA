<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeVariant;
use App\Models\User;
use App\Services\OgeAttemptService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MiniAppAttemptFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('oge_attempt_task_details');
        Schema::dropIfExists('oge_attempt_scorings');
        Schema::dropIfExists('oge_attempt_task_timings');
        Schema::dropIfExists('oge_attempt_answers');
        Schema::dropIfExists('oge_attempt_events');
        Schema::dropIfExists('oge_attempts');
        Schema::dropIfExists('oge_variants');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 32)->default('student');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable();
            $table->string('title')->nullable();
            $table->string('source')->nullable();
            $table->string('mode')->nullable();
            $table->json('config_json')->nullable();
            $table->boolean('is_curated')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id');
            $table->foreignId('student_id');
            $table->string('status')->default('active');
            $table->json('device_meta')->nullable();
            $table->json('frozen_answers_json')->nullable();
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
            $table->unsignedInteger('heartbeat_count')->default(0);
            $table->unsignedInteger('blur_count')->default(0);
            $table->timestamp('first_focused_at')->nullable();
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

        Schema::create('oge_attempt_task_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id');
            $table->unsignedTinyInteger('task_number');
            $table->string('topic_id', 8)->nullable();
            $table->unsignedInteger('block_number')->default(0);
            $table->unsignedInteger('zadanie_number')->default(0);
            $table->unsignedInteger('task_index')->nullable();
            $table->string('task_type', 64)->nullable();
            $table->string('svg_type', 64)->nullable();
            $table->string('subtype', 64)->nullable();
            $table->string('section', 255)->nullable();
            $table->string('source', 64)->nullable();
            $table->string('task_key', 255)->nullable();
            $table->string('task_fingerprint', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_mini_attempt_test_page_uses_sequential_attempt_numbers_for_answers(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        $variant = OgeVariant::create([
            'hash' => 'miniui01',
            'title' => 'Мини-ОГЭ: Смешанное',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => [
                'tasks' => [
                    ['task_number' => 6, 'topic_id' => '06', 'correct_answer' => '11'],
                    ['task_number' => 8, 'topic_id' => '08', 'correct_answer' => '22'],
                    ['task_number' => 15, 'topic_id' => '15', 'correct_answer' => '33'],
                ],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(2),
            'last_seen_at' => now(),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => 'legacy-answer',
            'commits_count' => 1,
        ]);

        $response = $this->actingAs($student)
            ->get("/tg/test/{$attempt->id}")
            ->assertOk();

        $response->assertSee('"attempt_task_number":1', false);
        $response->assertSee('"display_task_number":6', false);
        $response->assertSee('"1":"legacy-answer"', false);
    }

    public function test_mini_attempt_submit_and_status_use_sequential_attempt_numbers(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        $variant = OgeVariant::create([
            'hash' => 'minist01',
            'title' => 'Мини-ОГЭ: Смешанное',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => [
                'tasks' => [
                    ['task_number' => 6, 'topic_id' => '06', 'correct_answer' => '11'],
                    ['task_number' => 8, 'topic_id' => '08', 'correct_answer' => '22'],
                    ['task_number' => 15, 'topic_id' => '15', 'correct_answer' => '33'],
                ],
            ],
        ]);

        [, $attempt] = app(OgeAttemptService::class)->startAttempt($student, $variant->hash);

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit", [
                'answers' => [
                    1 => '11',
                    2 => '22',
                    3 => '33',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $statusResponse = $this->actingAs($student)
            ->getJson("/api/oge/attempts/{$attempt->id}/status")
            ->assertOk()
            ->assertJsonPath('summary.tasks_total', 3)
            ->assertJsonPath('summary.answered_count', 3)
            ->assertJsonPath('summary.unanswered_count', 0);

        $this->assertSame([1, 2, 3], OgeAttemptAnswer::where('attempt_id', $attempt->id)->orderBy('task_number')->pluck('task_number')->all());
        $this->assertSame([1, 2, 3], collect($statusResponse->json('tasks'))->pluck('task_number')->all());
    }

    public function test_full_miniapp_attempt_trims_legacy_topic_19_payload_to_three_statements_and_scores_against_it(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        $variant = OgeVariant::create([
            'hash' => 'full1901',
            'title' => 'Полный вариант ОГЭ',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => [
                'tasks' => [
                    [
                        'task_number' => 19,
                        'topic_id' => '19',
                        'type' => 'statements',
                        'instruction' => 'Какие из данных утверждений верны?',
                        'statements' => [
                            ['text' => 'Утверждение 1', 'is_true' => true],
                            ['text' => 'Утверждение 2', 'is_true' => false],
                            ['text' => 'Утверждение 3', 'is_true' => true],
                            ['text' => 'Утверждение 4', 'is_true' => false],
                            ['text' => 'Утверждение 5', 'is_true' => true],
                            ['text' => 'Утверждение 6', 'is_true' => false],
                        ],
                    ],
                ],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(3),
            'last_seen_at' => now(),
        ]);

        $page = $this->actingAs($student)
            ->get("/tg/test/{$attempt->id}")
            ->assertOk();

        $tasks = $page->viewData('tasks');
        $this->assertIsArray($tasks);
        $this->assertCount(1, $tasks);
        $this->assertSame(
            ['Утверждение 1', 'Утверждение 2', 'Утверждение 3'],
            array_column($tasks[0]['selected_statements'] ?? [], 'text')
        );
        $this->assertSame(
            ['Утверждение 1', 'Утверждение 2', 'Утверждение 3'],
            array_column($tasks[0]['statements'] ?? [], 'text')
        );

    }
}
