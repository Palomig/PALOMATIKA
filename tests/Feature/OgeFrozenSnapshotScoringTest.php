<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\User;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\TaskAnswerResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OgeFrozenSnapshotScoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('oge_attempt_scorings');
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
            $table->string('mode')->nullable();
            $table->string('source')->nullable();
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

    public function test_scoring_uses_frozen_answers_even_if_variant_tasks_change_after_start(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $variant = OgeVariant::create([
            'hash' => 'frzsc001',
            'config_json' => [
                'tasks' => [
                    [
                        'task_number' => 6,
                        'type' => 'choice',
                        'correct_answer' => '1',
                        'options' => ['A', 'B', 'C', 'D'],
                        'task' => ['id' => 1],
                    ],
                ],
            ],
        ]);

        $builder = $this->createMock(OgeVariantBuilderService::class);
        $resolver = new TaskAnswerResolver();
        $service = new OgeAttemptService($builder, $resolver);

        [, $attempt] = $service->startAttempt($student, $variant->hash);

        $attempt = OgeAttempt::findOrFail($attempt->id);
        $this->assertSame('1', (string) ($attempt->frozen_answers_json[6] ?? null));

        // Emulate data drift after attempt start: JSON changed to another answer.
        $config = $variant->config_json;
        $config['tasks'][0]['correct_answer'] = '2';
        $variant->update(['config_json' => $config]);

        $service->commitAnswer($attempt, 6, '1');

        $scoring = OgeAttemptScoring::where('attempt_id', $attempt->id)
            ->where('task_number', 6)
            ->firstOrFail();

        $this->assertSame('1', $scoring->correct_answer);
        $this->assertTrue((bool) $scoring->is_correct);
    }
}
