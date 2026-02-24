<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\User;
use App\Services\OgeVariantBuilderService;
use App\Services\TaskAnswerResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    public function test_resume_submitted_attempt_renders_locked_inputs(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $hash = 'subm1234';

        Cache::put("custom_random_test_{$hash}", [[
            'test_number' => 1,
            'topic_id' => '15',
            'topic_title' => 'Треугольники',
            'block_number' => 1,
            'zadanie_number' => 1,
            'instruction' => 'Лочим форму',
            'type' => 'word_problem',
            'task' => [
                'text' => 'Найдите ответ',
                'answer' => '5',
            ],
        ]], now()->addMinutes(5));

        $variant = OgeVariant::create([
            'hash' => $hash,
            'config_json' => [
                'source' => 'custom_random',
            ],
        ]);

        OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($student)->get("/oge/{$hash}");

        $response->assertOk();
        $response->assertSee('data-attempt-locked="1"', false);
        $response->assertSee('js-answer-input', false);
        $response->assertSee('disabled', false);
    }

    public function test_custom_tasks_with_same_topic_get_unique_attempt_task_keys(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $hash = 'dupt1234';

        Cache::put("custom_random_test_{$hash}", [
            [
                'test_number' => 1,
                'topic_id' => '15',
                'topic_title' => 'Треугольники',
                'block_number' => 1,
                'zadanie_number' => 1,
                'instruction' => 'Задание 1',
                'type' => 'word_problem',
                'task' => ['text' => 'A', 'answer' => '1'],
            ],
            [
                'test_number' => 2,
                'topic_id' => '15',
                'topic_title' => 'Треугольники',
                'block_number' => 1,
                'zadanie_number' => 2,
                'instruction' => 'Задание 2',
                'type' => 'word_problem',
                'task' => ['text' => 'B', 'answer' => '2'],
            ],
        ], now()->addMinutes(5));

        $response = $this->actingAs($student)->get("/oge/{$hash}");

        $response->assertOk();
        $response->assertSee('data-task-number="15"', false);
        $response->assertSee('data-attempt-task-number="1"', false);
        $response->assertSee('data-attempt-task-number="2"', false);
    }

    public function test_submit_custom_random_attempt_scores_using_custom_tasks_answers(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'scorcr01',
            'config_json' => [
                'source' => 'custom_random',
                'custom_tasks' => [
                    [
                        'attempt_task_number' => 1,
                        'task_number' => 1,
                        'type' => 'word_problem',
                        'task' => ['answer' => '42'],
                    ],
                    [
                        'attempt_task_number' => 2,
                        'task_number' => 2,
                        'type' => 'word_problem',
                        'task' => ['answer' => '99'],
                    ],
                ],
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
            ])
            ->assertOk();

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/tasks/2/commit", [
                'answer' => '12',
            ])
            ->assertOk();

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit")
            ->assertOk()
            ->assertJsonPath('status', 'scored');

        $this->assertDatabaseHas('oge_attempt_scorings', [
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => 1,
            'correct_answer' => '42',
        ]);
        $this->assertDatabaseHas('oge_attempt_scorings', [
            'attempt_id' => $attempt->id,
            'task_number' => 2,
            'is_correct' => 0,
            'correct_answer' => '99',
        ]);
        $this->assertDatabaseHas('oge_attempts', [
            'id' => $attempt->id,
            'status' => 'scored',
        ]);
    }

    public function test_submit_custom_random_attempt_scores_using_stored_payload_when_config_missing_custom_tasks(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        $hash = 'scorcache';

        $variant = OgeVariant::create([
            'hash' => $hash,
            'config_json' => [
                'source' => 'custom_random',
                'topics' => ['06'],
                'tasks_per_topic' => 1,
                // Legacy/trimmed payload: task list is not in DB config_json.
            ],
        ]);

        Storage::disk('local')->put(
            "custom_random_tests/{$hash}.json",
            json_encode([
                [
                    'attempt_task_number' => 1,
                    'task_number' => 1,
                    'type' => 'word_problem',
                    'task' => ['answer' => '42'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/tasks/1/commit", [
                'answer' => '42',
            ])
            ->assertOk();

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit")
            ->assertOk()
            ->assertJsonPath('status', 'scored');

        $this->assertDatabaseHas('oge_attempt_scorings', [
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => 1,
            'correct_answer' => '42',
        ]);
    }

    public function test_submit_marks_attempt_error_when_scoring_fails(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'scorcr02',
            'config_json' => [
                'source' => 'custom_random',
                'custom_tasks' => [
                    [
                        'attempt_task_number' => 1,
                        'task_number' => 1,
                        'type' => 'word_problem',
                        'task' => ['answer' => '42'],
                    ],
                ],
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'current_answer' => '42',
            'commits_count' => 1,
        ]);

        $this->app->bind(TaskAnswerResolver::class, function () {
            return new class extends TaskAnswerResolver
            {
                public function isCorrect(?string $userAnswer, ?string $correctAnswer): ?bool
                {
                    throw new \RuntimeException('scoring-broken');
                }
            };
        });

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit")
            ->assertStatus(500);

        $this->assertDatabaseHas('oge_attempts', [
            'id' => $attempt->id,
            'status' => 'error',
        ]);
        $this->assertDatabaseHas('oge_attempt_events', [
            'attempt_id' => $attempt->id,
            'event_type' => 'attempt_scoring_failed',
        ]);
    }

    public function test_submit_generator_variant_scoring_remains_unchanged(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->app->bind(OgeVariantBuilderService::class, function () {
            return new class extends OgeVariantBuilderService
            {
                public function __construct()
                {
                }

                public function build(string $hash, ?array $selectedZadaniya = null): array
                {
                    return [
                        'tasks' => [
                            [
                                'task_number' => 6,
                                'correct_answer' => '77',
                            ],
                        ],
                        'variantNumber' => 1,
                        'selectedZadaniya' => [],
                    ];
                }
            };
        });

        $variant = OgeVariant::create([
            'hash' => 'genok001',
            'config_json' => [
                'source' => 'generator',
            ],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/tasks/6/commit", [
                'answer' => '77',
            ])
            ->assertOk();

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/submit")
            ->assertOk()
            ->assertJsonPath('status', 'scored');

        $this->assertDatabaseHas('oge_attempt_scorings', [
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => 1,
            'correct_answer' => '77',
        ]);
    }
}
