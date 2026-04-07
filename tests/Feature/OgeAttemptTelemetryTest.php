<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptEvent;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OgeAttemptTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_post_paste_telemetry(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::factory()->create();
        $attempt = OgeAttempt::factory()->create([
            'student_id' => $student->id,
            'variant_id' => $variant->id,
            'status'     => 'active',
        ]);

        $response = $this->actingAs($student)->postJson(
            "/api/oge/attempts/{$attempt->id}/telemetry",
            [
                'event_type'  => 'answer_pasted',
                'task_number' => 7,
                'payload'     => ['away_ms_before' => 45000, 'time_since_return_ms' => 3200],
                'client_ts'   => now()->toIso8601String(),
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('oge_attempt_events', [
            'attempt_id' => $attempt->id,
            'event_type' => 'answer_pasted',
            'task_number' => 7,
        ]);
    }

    public function test_telemetry_rejects_unknown_event_type(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::factory()->create();
        $attempt = OgeAttempt::factory()->create([
            'student_id' => $student->id,
            'variant_id' => $variant->id,
            'status'     => 'active',
        ]);

        $this->actingAs($student)->postJson(
            "/api/oge/attempts/{$attempt->id}/telemetry",
            ['event_type' => 'hack_attempt']
        )->assertStatus(422);
    }

    public function test_telemetry_requires_active_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::factory()->create();
        $attempt = OgeAttempt::factory()->create([
            'student_id' => $student->id,
            'variant_id' => $variant->id,
            'status'     => 'submitted',
        ]);

        $this->actingAs($student)->postJson(
            "/api/oge/attempts/{$attempt->id}/telemetry",
            ['event_type' => 'answer_pasted']
        )->assertStatus(409);
    }
}
