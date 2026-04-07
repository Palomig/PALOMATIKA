<?php

namespace Tests\Unit;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptEvent;
use App\Models\OgeVariant;
use App\Models\User;
use App\Services\OgeAttemptSuspicionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OgeAttemptSuspicionServiceTest extends TestCase
{
    use RefreshDatabase;

    private OgeAttemptSuspicionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OgeAttemptSuspicionService();
    }

    private function makeAttempt(): OgeAttempt
    {
        $student = User::factory()->create(['role' => 'student']);
        $variant = OgeVariant::factory()->create();
        return OgeAttempt::factory()->create([
            'student_id' => $student->id,
            'variant_id' => $variant->id,
            'status'     => 'submitted',
        ]);
    }

    public function test_no_events_is_not_suspicious(): void
    {
        $attempt = $this->makeAttempt();
        $result = $this->service->analyze($attempt->id);
        $this->assertFalse($result['is_suspicious']);
        $this->assertSame(0, $result['score']);
        $this->assertEmpty($result['reasons']);
    }

    public function test_paste_after_long_away_is_suspicious(): void
    {
        $attempt = $this->makeAttempt();
        OgeAttemptEvent::create([
            'attempt_id'   => $attempt->id,
            'seq'          => 1,
            'event_type'   => 'answer_pasted',
            'task_number'  => 7,
            'payload_json' => ['away_ms_before' => 45000, 'time_since_return_ms' => 2000],
            'server_ts'    => now(),
        ]);

        $result = $this->service->analyze($attempt->id);
        $this->assertTrue($result['is_suspicious']);
        $this->assertGreaterThanOrEqual(2, $result['score']);
        $this->assertNotEmpty($result['reasons']);
    }

    public function test_paste_after_short_away_is_not_suspicious(): void
    {
        $attempt = $this->makeAttempt();
        OgeAttemptEvent::create([
            'attempt_id'   => $attempt->id,
            'seq'          => 1,
            'event_type'   => 'answer_pasted',
            'task_number'  => 6,
            'payload_json' => ['away_ms_before' => 5000],
            'server_ts'    => now(),
        ]);

        $result = $this->service->analyze($attempt->id);
        $this->assertFalse($result['is_suspicious']);
    }

    public function test_three_or_more_pastes_raises_score(): void
    {
        $attempt = $this->makeAttempt();
        foreach (range(1, 3) as $seq) {
            OgeAttemptEvent::create([
                'attempt_id'   => $attempt->id,
                'seq'          => $seq,
                'event_type'   => 'answer_pasted',
                'task_number'  => $seq + 5,
                'payload_json' => ['away_ms_before' => 15000],
                'server_ts'    => now(),
            ]);
        }

        $result = $this->service->analyze($attempt->id);
        $this->assertTrue($result['is_suspicious']);
    }

    public function test_analyze_many_returns_keyed_by_attempt_id(): void
    {
        $a1 = $this->makeAttempt();
        $a2 = $this->makeAttempt();

        OgeAttemptEvent::create([
            'attempt_id'   => $a1->id,
            'seq'          => 1,
            'event_type'   => 'answer_pasted',
            'task_number'  => 8,
            'payload_json' => ['away_ms_before' => 60000],
            'server_ts'    => now(),
        ]);

        $results = $this->service->analyzeMany([$a1->id, $a2->id]);

        $this->assertArrayHasKey($a1->id, $results);
        $this->assertArrayHasKey($a2->id, $results);
        $this->assertTrue($results[$a1->id]['is_suspicious']);
        $this->assertFalse($results[$a2->id]['is_suspicious']);
    }
}
