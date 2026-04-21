<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PwaStudentExamAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'grade_letter' => 'А',
            'school_number' => '1',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
        ]);
    }

    private function makeVariant(string $hash, string $examType, string $mode = OgeVariant::MODE_FULL): OgeVariant
    {
        return OgeVariant::create([
            'hash' => $hash,
            'exam_type' => $examType,
            'title' => "Variant {$examType}",
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => $mode,
            'config_json' => [
                'tasks' => [
                    ['task_number' => 1, 'topic_id' => '01', 'text' => 'Task 1'],
                ],
            ],
        ]);
    }

    private function makeScoredAttempt(User $student, OgeVariant $variant): OgeAttempt
    {
        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
            'last_seen_at' => now()->subMinutes(5),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => true,
            'correct_answer' => '42',
            'checked_at' => now()->subMinutes(4),
        ]);

        return $attempt;
    }

    public function test_grade_5_student_cannot_start_oge_mini(): void
    {
        $student = $this->makeStudent(5);

        $response = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/mini/start', [
                'mode' => 'mixed',
            ]);

        $response->assertForbidden();
    }

    public function test_grade_9_student_cannot_start_vpr_5_variant_by_student_variant_hash_route(): void
    {
        $student = $this->makeStudent(9);
        $variant = $this->makeVariant('vpr5hash9', OgeVariant::EXAM_VPR5);

        $response = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/full/start', [
                'variant_hash' => $variant->hash,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('oge_attempts', 0);
    }

    public function test_grade_9_student_cannot_start_vpr_5_variant_via_api_attempt_start(): void
    {
        $student = $this->makeStudent(9);
        $variant = $this->makeVariant('vpr5api99', OgeVariant::EXAM_VPR5);

        $response = $this->actingAs($student)
            ->postJson("/api/oge/variants/{$variant->hash}/attempt/start");

        $response->assertForbidden();
        $this->assertDatabaseCount('oge_attempts', 0);
    }

    public function test_grade_8_student_can_start_oge_and_vpr_8_but_not_vpr_5(): void
    {
        $student = $this->makeStudent(8);
        $vpr8Variant = $this->makeVariant('vpr8hash8', OgeVariant::EXAM_VPR8);
        $vpr5Variant = $this->makeVariant('vpr5hash8', OgeVariant::EXAM_VPR5);

        $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/mini/start', [
                'mode' => 'mixed',
            ])
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('redirect', fn (string $value) => str_contains($value, '/test/'))->etc()
            );

        $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/full/start', [
                'variant_hash' => $vpr8Variant->hash,
            ])
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('redirect', fn (string $value) => str_contains($value, '/test/'))->etc()
            );

        $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/full/start', [
                'variant_hash' => $vpr5Variant->hash,
            ])
            ->assertForbidden();
    }

    public function test_history_hides_attempts_from_disallowed_exam_tracks_for_student(): void
    {
        $student = $this->makeStudent(9);
        $ogeVariant = $this->makeVariant('ogehist99', OgeVariant::EXAM_OGE, OgeVariant::MODE_MINI_MIXED);
        $vprVariant = $this->makeVariant('vprhist95', OgeVariant::EXAM_VPR5, OgeVariant::MODE_MINI_MIXED);

        $this->makeScoredAttempt($student, $ogeVariant);
        $this->makeScoredAttempt($student, $vprVariant);

        $response = $this->actingAs($student)
            ->get('http://student.palomatika.ru/history');

        $response->assertOk();
        $response->assertSee('Мини-ОГЭ');
        $response->assertDontSee('Мини-ВПР');
    }

    public function test_student_cannot_open_active_attempt_from_disallowed_exam_track(): void
    {
        $student = $this->makeStudent(9);
        $vprVariant = $this->makeVariant('vprtest95', OgeVariant::EXAM_VPR5);
        $attempt = OgeAttempt::create([
            'variant_id' => $vprVariant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(2),
            'last_seen_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($student)
            ->get("http://student.palomatika.ru/test/{$attempt->id}");

        $response->assertNotFound();
    }
}
