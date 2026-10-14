<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * После сдачи попытка сразу проходит проверку и становится «scored» —
 * страница результатов ЕГЭ искала только «submitted» и отдавала 404.
 */
class EgeResultsPageTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(User $student, string $status, string $level = 'prof'): OgeAttempt
    {
        $variant = OgeVariant::create([
            'hash' => 'ege' . substr(md5($status . $level), 0, 3),
            'exam_type' => 'ege',
            'level' => $level,
            'title' => 'Вариант ЕГЭ',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['level' => $level, 'tasks' => [['task_number' => 1, 'topic_id' => '01']]],
        ]);

        return OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => $status,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function test_results_open_after_scoring_and_after_failed_scoring(): void
    {
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now()]);

        $scored = $this->attempt($student, 'scored');
        OgeAttemptScoring::create([
            'attempt_id' => $scored->id,
            'task_number' => 1,
            'is_correct' => true,
            'correct_answer' => '0.8',
            'checked_at' => now(),
        ]);

        foreach ([$scored, $this->attempt($student, 'error')] as $attempt) {
            $this->actingAs($student)
                ->get('https://student.' . config('app.base_domain') . "/ege-app/results/{$attempt->id}")
                ->assertOk();
        }
    }

    public function test_unfinished_attempt_has_no_results(): void
    {
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now()]);
        $attempt = $this->attempt($student, 'active');

        $this->actingAs($student)
            ->get('https://student.' . config('app.base_domain') . "/ege-app/results/{$attempt->id}")
            ->assertNotFound();
    }

    public function test_variant_header_names_its_level(): void
    {
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 11, 'onboarding_completed_at' => now()]);
        $host = 'https://student.' . config('app.base_domain');

        $this->actingAs($student)->get($host . '/ege-app/test/' . $this->attempt($student, 'active', 'base')->id)
            ->assertOk()->assertSee('ЕГЭ (Б)')->assertDontSee('ЕГЭ (П)');
        $this->actingAs($student)->get($host . '/ege-app/test/' . $this->attempt($student, 'active', 'prof')->id)
            ->assertOk()->assertSee('ЕГЭ (П)');
    }
}
