<?php

namespace Tests\Feature\Pwa;

use App\Models\PracticeGameRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaPracticeRoutesTest extends TestCase
{
    use RefreshDatabase;

    private static int $studentSeq = 0;

    private function makeStudent(array $attrs = []): User
    {
        self::$studentSeq++;
        return User::factory()->create(array_merge([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
            'oauth_provider' => 'vk',
            'oauth_id' => 'practice-test-user-' . self::$studentSeq,
        ], $attrs));
    }

    public function test_dashboard_shows_practice_entry_instead_of_mistake_review_tile(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($student)->get('http://student.palomatika.ru/');

        $response->assertOk();
        $response->assertSee('Практика');
        $response->assertDontSee('Разбор ошибок');
    }

    public function test_practice_pages_are_accessible_for_authenticated_student(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student)
            ->get('http://student.palomatika.ru/practice')
            ->assertOk()
            ->assertSee('Практика')
            ->assertSee('Мини-игры');

        $this->actingAs($student)
            ->get('http://student.palomatika.ru/practice/mini-games')
            ->assertOk()
            ->assertSee('Мини-игры')
            ->assertSee('Уравнения');

        $this->actingAs($student)
            ->get('http://student.palomatika.ru/practice/mini-games/equations')
            ->assertOk()
            ->assertSee('Уравнения')
            ->assertSee('10 секунд на ход')
            ->assertSee('Сыграть ещё');
    }

    public function test_start_run_creates_run_and_returns_question_without_correct_flag(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/start');

        $response->assertOk();
        $response->assertJsonStructure([
            'run_id',
            'game' => ['slug', 'title', 'theory' => ['title', 'items']],
            'score',
            'question' => ['prompt', 'options' => [['id', 'label']], 'level', 'task_type'],
        ]);

        $options = $response->json('question.options');
        foreach ($options as $option) {
            $this->assertArrayNotHasKey('is_correct', $option, 'is_correct must not leak to client');
        }

        $this->assertDatabaseHas('practice_game_runs', [
            'id' => $response->json('run_id'),
            'user_id' => $student->id,
            'slug' => 'equations',
            'score' => 0,
            'ended_at' => null,
        ]);
    }

    public function test_correct_answer_increments_score_and_returns_next_question(): void
    {
        $student = $this->makeStudent();

        $start = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/start');
        $runId = $start->json('run_id');
        $correctId = PracticeGameRun::find($runId)->current_question['correct_id'];

        $answer = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/answer', [
                'run_id' => $runId,
                'option_id' => $correctId,
            ]);

        $answer->assertOk();
        $answer->assertJsonPath('status', 'continue');
        $answer->assertJsonPath('score', 1);
        $answer->assertJsonStructure(['question' => ['options' => [['id', 'label']]]]);

        $this->assertDatabaseHas('practice_game_runs', [
            'id' => $runId,
            'score' => 1,
            'ended_at' => null,
        ]);
    }

    public function test_wrong_answer_ends_run_with_reason_wrong(): void
    {
        $student = $this->makeStudent();

        $start = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/start');
        $runId = $start->json('run_id');
        $correctId = PracticeGameRun::find($runId)->current_question['correct_id'];
        $wrongId = $correctId === 'a' ? 'b' : 'a';

        $answer = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/answer', [
                'run_id' => $runId,
                'option_id' => $wrongId,
            ]);

        $answer->assertOk();
        $answer->assertJsonPath('status', 'over');
        $answer->assertJsonPath('reason', 'wrong');
        $answer->assertJsonPath('score', 0);

        $run = PracticeGameRun::find($runId);
        $this->assertNotNull($run->ended_at);
        $this->assertSame('wrong', $run->end_reason);
    }

    public function test_timeout_endpoint_closes_open_run(): void
    {
        $student = $this->makeStudent();

        $start = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/start');
        $runId = $start->json('run_id');

        $timeout = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/timeout', [
                'run_id' => $runId,
            ]);

        $timeout->assertOk();
        $timeout->assertJsonPath('status', 'over');
        $timeout->assertJsonPath('reason', 'timeout');

        $this->assertSame('timeout', PracticeGameRun::find($runId)->end_reason);
    }

    public function test_run_belongs_to_other_user_returns_404(): void
    {
        $student = $this->makeStudent();
        $other = $this->makeStudent();

        $start = $this->actingAs($other)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/start');
        $runId = $start->json('run_id');

        $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/equations/answer', [
                'run_id' => $runId,
                'option_id' => 'a',
            ])
            ->assertNotFound();
    }

    public function test_functions_start_returns_graph_svg(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/practice/api/mini-games/functions/start');

        $response->assertOk();
        $graph = $response->json('question.graph');
        $this->assertIsString($graph);
        $this->assertStringContainsString('<svg', $graph);
    }
}
