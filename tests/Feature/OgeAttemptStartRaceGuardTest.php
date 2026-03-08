<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\User;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\TaskAnswerResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OgeAttemptStartRaceGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

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
            $table->unique(['attempt_id', 'seq'], 'oge_attempt_events_attempt_id_seq_unique');
        });
    }

    public function test_start_attempt_reuses_existing_active_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'race001',
            'config_json' => [
                'tasks' => [[
                    'task_number' => 6,
                    'correct_answer' => '1',
                    'type' => 'choice',
                    'task' => ['id' => 1],
                ]],
            ],
        ]);

        $builder = $this->createMock(OgeVariantBuilderService::class);
        $service = new OgeAttemptService($builder, new TaskAnswerResolver());

        [, $first] = $service->startAttempt($student, $variant->hash);
        [, $second] = $service->startAttempt($student, $variant->hash);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OgeAttempt::where('variant_id', $variant->id)->where('student_id', $student->id)->count());
    }

    public function test_start_attempt_creates_new_active_after_previous_submitted(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create([
            'hash' => 'race002',
            'config_json' => [
                'tasks' => [[
                    'task_number' => 6,
                    'correct_answer' => '1',
                    'type' => 'choice',
                    'task' => ['id' => 1],
                ]],
            ],
        ]);

        $builder = $this->createMock(OgeVariantBuilderService::class);
        $service = new OgeAttemptService($builder, new TaskAnswerResolver());

        [, $first] = $service->startAttempt($student, $variant->hash);
        $first->update(['status' => 'submitted']);

        [, $second] = $service->startAttempt($student, $variant->hash);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('active', $second->status);
        $this->assertSame(2, OgeAttempt::where('variant_id', $variant->id)->where('student_id', $student->id)->count());
    }
}
