<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptTaskTiming;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OgeAttemptHeartbeatVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('oge_attempt_task_timings');
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
    }

    public function test_hidden_heartbeat_does_not_add_active_time_and_records_away_duration(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::create(['hash' => 'hbtest1']);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        OgeAttemptTaskTiming::create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'active_ms' => 0,
            'focus_count' => 1,
            'last_focus_at' => now()->subSeconds(12),
            'last_heartbeat_at' => now()->subSeconds(12),
        ]);

        $this->actingAs($student)
            ->postJson("/api/oge/attempts/{$attempt->id}/heartbeat", [
                'active_task' => 6,
                'visible' => false,
                'away_ms' => 5000,
                'client_ts' => now()->toIso8601String(),
            ])
            ->assertOk();

        $timing = OgeAttemptTaskTiming::where('attempt_id', $attempt->id)->where('task_number', 6)->firstOrFail();
        $this->assertSame(0, (int) $timing->active_ms);

        $attempt = $attempt->fresh();
        $this->assertSame(5000, (int) ($attempt->device_meta['away_ms_total'] ?? 0));

        $this->assertDatabaseHas('oge_attempt_events', [
            'attempt_id' => $attempt->id,
            'event_type' => 'tab_away',
        ]);
    }
}
