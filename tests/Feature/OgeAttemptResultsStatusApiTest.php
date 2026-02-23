<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptScoring;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OgeAttemptResultsStatusApiTest extends TestCase
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

    public function test_student_can_fetch_attempt_status_details_for_generator_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'stgen001',
            'config_json' => ['source' => 'generator'],
        ]);
        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'device_meta' => ['away_ms_total' => 3000],
            'started_at' => now()->subMinutes(4),
            'last_seen_at' => now()->subSecond(),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => '77',
            'commits_count' => 2,
            'is_final' => false,
            'last_committed_at' => now()->subMinute(),
        ]);

        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'active_ms' => 15000,
            'focus_count' => 2,
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => true,
            'correct_answer' => '77',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->getJson("/api/oge/attempts/{$attempt->id}/status")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attempt.id', $attempt->id)
            ->assertJsonPath('attempt.variant_hash', 'stgen001')
            ->assertJsonPath('attempt.locked', false)
            ->assertJsonPath('summary.answered_count', 1)
            ->assertJsonPath('summary.total_active_ms', 15000)
            ->assertJsonPath('summary.away_ms_total', 3000)
            ->assertJsonPath('tasks.0.task_number', 6)
            ->assertJsonPath('tasks.0.answer', '77')
            ->assertJsonPath('tasks.0.commits_count', 2)
            ->assertJsonPath('tasks.0.active_ms', 15000)
            ->assertJsonPath('tasks.0.status', 'answered');

        $task = collect($response->json('tasks'))->firstWhere('task_number', 6);
        $this->assertIsArray($task);
        $this->assertArrayNotHasKey('is_correct', $task);
        $this->assertArrayNotHasKey('correct_answer', $task);
    }

    public function test_student_can_fetch_attempt_status_details_for_custom_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'stcus001',
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
            'submitted_at' => now()->subMinutes(2),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'current_answer' => '42',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        $this->actingAs($student)
            ->getJson("/api/oge/attempts/{$attempt->id}/status")
            ->assertOk()
            ->assertJsonPath('attempt.locked', true)
            ->assertJsonPath('attempt.is_custom', true)
            ->assertJsonPath('summary.tasks_total', 2)
            ->assertJsonPath('tasks.0.task_number', 1)
            ->assertJsonPath('tasks.1.task_number', 6);
    }

    public function test_teacher_can_fetch_attempt_result_payload_for_generator_attempt(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'resgen01',
            'owner_teacher_id' => $teacher->id,
            'config_json' => ['source' => 'generator'],
        ]);
        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'device_meta' => ['away_ms_total' => 7000],
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => '77',
            'commits_count' => 2,
            'is_final' => true,
        ]);
        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'active_ms' => 22000,
            'focus_count' => 3,
        ]);
        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => true,
            'correct_answer' => '77',
            'checked_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($teacher)
            ->getJson("/api/oge/attempts/{$attempt->id}/result")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('attempt.id', $attempt->id)
            ->assertJsonPath('attempt.student.id', $student->id)
            ->assertJsonPath('attempt.variant.id', $variant->id)
            ->assertJsonPath('summary.answered_count', 1)
            ->assertJsonPath('summary.correct_count', 1)
            ->assertJsonPath('summary.incorrect_count', 0)
            ->assertJsonPath('summary.total_active_ms', 22000)
            ->assertJsonPath('summary.away_ms_total', 7000);

        $task = collect($response->json('tasks'))->firstWhere('task_number', 6);
        $this->assertSame('correct', $task['status'] ?? null);
        $this->assertTrue($task['is_correct'] ?? false);
        $this->assertSame(22000, $task['active_ms'] ?? null);
    }

    public function test_teacher_can_fetch_attempt_result_payload_for_custom_attempt(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'rescus01',
            'owner_teacher_id' => $teacher->id,
            'config_json' => [
                'source' => 'custom_random',
                'custom_task_numbers' => [1, 6],
            ],
        ]);
        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(12),
            'submitted_at' => now()->subMinutes(1),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'current_answer' => '42',
            'commits_count' => 1,
            'is_final' => true,
        ]);
        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => '11',
            'commits_count' => 1,
            'is_final' => true,
        ]);
        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'active_ms' => 9000,
            'focus_count' => 1,
        ]);
        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'active_ms' => 13000,
            'focus_count' => 2,
        ]);
        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => true,
            'correct_answer' => '42',
            'checked_at' => now()->subMinute(),
        ]);
        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => false,
            'correct_answer' => '99',
            'checked_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($teacher)
            ->getJson("/api/oge/attempts/{$attempt->id}/result")
            ->assertOk()
            ->assertJsonPath('attempt.is_custom', true)
            ->assertJsonPath('summary.tasks_total', 2)
            ->assertJsonPath('summary.answered_count', 2)
            ->assertJsonPath('summary.correct_count', 1)
            ->assertJsonPath('summary.incorrect_count', 1)
            ->assertJsonPath('summary.total_active_ms', 22000)
            ->assertJsonPath('tasks.0.task_number', 1)
            ->assertJsonPath('tasks.1.task_number', 6);

        $task6 = collect($response->json('tasks'))->firstWhere('task_number', 6);
        $this->assertSame('incorrect', $task6['status'] ?? null);
        $this->assertFalse($task6['is_correct'] ?? true);
        $this->assertSame('99', $task6['correct_answer'] ?? null);
    }

    public function test_teacher_can_fetch_telegram_summary_payload_for_generator_attempt(): void
    {
        config()->set('services.telegram.bot_username', 'palomatika_bot');
        config()->set('services.telegram.webapp_base_url', 'https://mini.example.com/oge');
        config()->set('services.telegram.mini_app_link_scheme', 'https');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Ivan Student']);
        $variant = OgeVariant::create([
            'hash' => 'tggen001',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Generator Variant',
            'config_json' => ['source' => 'generator'],
        ]);
        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(15),
            'submitted_at' => now()->subMinutes(2),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => '77',
            'commits_count' => 1,
            'is_final' => true,
        ]);
        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => true,
            'correct_answer' => '77',
            'checked_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($teacher)
            ->getJson("/api/oge/attempts/{$attempt->id}/telegram-summary")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('contract.name', 'oge_attempt_telegram_result_summary')
            ->assertJsonPath('contract.version', 1)
            ->assertJsonPath('attempt.id', $attempt->id)
            ->assertJsonPath('attempt.variant_id', $variant->id)
            ->assertJsonPath('attempt.is_custom', false)
            ->assertJsonPath('summary.correct_count', 1)
            ->assertJsonPath('telegram.links.variant_results_url', route('teacher.oge.results', ['variantId' => $variant->id]))
            ->assertJsonPath(
                'telegram.links.attempt_results_url',
                route('teacher.oge.results', ['variantId' => $variant->id]) . '?attempt=' . $attempt->id . '#attempt-' . $attempt->id
            )
            ->assertJsonPath('telegram.links.variant_results_mini_app_url', "https://t.me/palomatika_bot?startapp=oge_variant_{$variant->id}")
            ->assertJsonPath('telegram.links.attempt_results_mini_app_url', "https://t.me/palomatika_bot?startapp=oge_attempt_{$attempt->id}")
            ->assertJsonPath('telegram.links.variant_results_button_url', "https://mini.example.com/oge?startapp=oge_variant_{$variant->id}")
            ->assertJsonPath('telegram.links.attempt_results_button_url', "https://mini.example.com/oge?startapp=oge_attempt_{$attempt->id}");

        $task6 = collect($response->json('telegram.task_statuses'))->firstWhere('task_number', 6);
        $this->assertSame('correct', $task6['status'] ?? null);
        $this->assertSame('+', $task6['code'] ?? null);

        $messageText = (string) $response->json('telegram.message_text');
        $this->assertStringContainsString('Variant tggen001', $messageText);
        $this->assertStringContainsString('Tasks:', $messageText);
        $this->assertStringContainsString('6:+', $messageText);
        $this->assertStringContainsString("https://t.me/palomatika_bot?startapp=oge_variant_{$variant->id}", $messageText);
    }

    public function test_teacher_can_fetch_telegram_summary_payload_for_custom_attempt(): void
    {
        config()->set('services.telegram.bot_username', null);
        config()->set('services.telegram.webapp_base_url', null);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Petr Student']);
        $variant = OgeVariant::create([
            'hash' => 'tgcus001',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Custom Variant',
            'config_json' => [
                'source' => 'custom_random',
                'custom_task_numbers' => [1, 6],
            ],
        ]);
        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
        ]);

        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'current_answer' => '42',
            'commits_count' => 1,
            'is_final' => true,
        ]);
        OgeAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => '11',
            'commits_count' => 1,
            'is_final' => true,
        ]);
        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => true,
            'correct_answer' => '42',
            'checked_at' => now()->subMinute(),
        ]);
        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => false,
            'correct_answer' => '99',
            'checked_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($teacher)
            ->getJson("/api/oge/attempts/{$attempt->id}/telegram-summary")
            ->assertOk()
            ->assertJsonPath('attempt.is_custom', true)
            ->assertJsonPath('summary.tasks_total', 2)
            ->assertJsonPath('summary.correct_count', 1)
            ->assertJsonPath('summary.incorrect_count', 1);

        $statuses = collect($response->json('telegram.task_statuses'))
            ->mapWithKeys(fn (array $row) => [(int) $row['task_number'] => $row['code']])
            ->all();

        $this->assertSame([1 => '+', 6 => '-'], $statuses);
        $this->assertStringContainsString('Tasks: 1:+ 6:-', (string) $response->json('telegram.message_text'));
        $this->assertStringContainsString(
            '#attempt-' . $attempt->id,
            (string) $response->json('telegram.links.attempt_results_url')
        );
        $this->assertNull($response->json('telegram.links.variant_results_mini_app_url'));
        $this->assertNull($response->json('telegram.links.attempt_results_mini_app_url'));
        $this->assertSame(
            route('teacher.oge.results', ['variantId' => $variant->id]),
            $response->json('telegram.links.variant_results_button_url')
        );
    }
}
