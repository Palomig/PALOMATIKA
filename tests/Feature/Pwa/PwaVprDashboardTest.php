<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeVariant;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PwaVprDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(int $grade): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => $grade,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function makeAdmin(?int $grade = 9): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'grade_num' => $grade,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_vpr_dashboard_shows_real_student_grade_and_oge_style_action_cards(): void
    {
        $user = $this->makeStudent(5);

        $response = $this->actingAs($user)
            ->get('http://student.palomatika.ru/vpr');

        $response->assertOk();
        $response->assertSee('ВПР · 5 класс');
        $response->assertSee('Мини-ВПР');
        $response->assertSee('Полный вариант');
        $response->assertSee('База заданий');
        $response->assertSee('История');
        $response->assertSee('Профиль');
    }

    public function test_admin_vpr_dashboard_defaults_to_grade_5_for_testing(): void
    {
        $admin = $this->makeAdmin(9);

        $response = $this->actingAs($admin)
            ->get('http://student.palomatika.ru/vpr');

        $response->assertOk();
        $response->assertSee('ВПР · 5 класс');
        $response->assertDontSee('ВПР · 9 класс');
    }

    public function test_vpr_mini_start_creates_mini_vpr_attempt_and_returns_redirect(): void
    {
        $user = $this->makeStudent(5);

        $response = $this->actingAs($user)
            ->postJson('http://student.palomatika.ru/vpr/mini/start', [
                'mode' => 'mixed',
            ]);

        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', fn (string $value) => str_contains($value, '/vpr/test/'))->etc()
        );

        $this->assertDatabaseCount('oge_attempts', 1);
        $this->assertDatabaseHas('oge_variants', [
            'exam_type' => OgeVariant::EXAM_VPR5,
            'mode' => OgeVariant::MODE_MINI_MIXED,
        ]);
    }

    public function test_admin_can_start_vpr_variants_for_testing(): void
    {
        $admin = $this->makeAdmin(9);

        $miniResponse = $this->actingAs($admin)
            ->postJson('http://student.palomatika.ru/vpr/mini/start', [
                'mode' => 'mixed',
            ]);

        $miniResponse->assertOk();
        $miniResponse->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', fn (string $value) => str_contains($value, '/vpr/test/'))->etc()
        );

        $fullResponse = $this->actingAs($admin)
            ->postJson('http://student.palomatika.ru/vpr/full/start');

        $fullResponse->assertOk();
        $fullResponse->assertJson(fn (AssertableJson $json) =>
            $json->where('redirect', fn (string $value) => str_contains($value, '/vpr/test/'))->etc()
        );

        $this->assertDatabaseHas('oge_variants', [
            'exam_type' => OgeVariant::EXAM_VPR5,
            'mode' => OgeVariant::MODE_MINI_MIXED,
        ]);
        $this->assertDatabaseHas('oge_variants', [
            'exam_type' => OgeVariant::EXAM_VPR5,
            'mode' => OgeVariant::MODE_FULL,
        ]);
    }

    public function test_vpr_grade_5_task_database_allows_topic_18(): void
    {
        $user = $this->makeStudent(5);

        $response = $this->actingAs($user)
            ->get('http://student.palomatika.ru/vpr/tasks?topic=18');

        $response->assertOk();
        $response->assertSee('Задание 18');
        $response->assertSee('ВПР · 5 КЛ');
    }

    public function test_vpr_results_page_is_available_for_scored_attempts(): void
    {
        $user = $this->makeStudent(5);

        $variant = OgeVariant::create([
            'hash' => 'vpr5aa',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [['task_number' => 1, 'topic_id' => '01']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => true,
            'correct_answer' => '42',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get("http://student.palomatika.ru/vpr/results/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('Результаты');
    }

    public function test_vpr_test_template_uses_primary_prompt_helper_for_selected_tasks(): void
    {
        $user = $this->makeStudent(5);

        $variant = OgeVariant::create([
            'hash' => 'vpr5tt',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [[
                'task_number' => 1,
                'topic_id' => '01',
                'instruction' => 'Служебный заголовок',
                'text' => 'Основной текст задания',
            ]]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(1),
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get("http://student.palomatika.ru/vpr/test/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('primaryPrompt(currentTask)', false);
        $response->assertSee('shouldShowInstruction(currentTask)', false);
        $response->assertDontSee('currentTask.instruction || currentTask.text ||', false);
    }

    public function test_history_uses_vpr_labels_for_vpr_attempts(): void
    {
        $user = $this->makeStudent(5);

        $variant = OgeVariant::create([
            'hash' => 'vpr5mx',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Мини-ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => ['tasks' => [['task_number' => 1, 'topic_id' => '01']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => false,
            'correct_answer' => '42',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('http://student.palomatika.ru/history');

        $response->assertOk();
        $response->assertSee('Мини-ВПР');
        $response->assertDontSee('Мини-ОГЭ');
    }
}
