<?php

namespace Tests\Feature\Pwa;

use App\Models\OgeVariant;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariantPoolEntry;
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

    public function test_admin_can_open_student_vpr_test_page(): void
    {
        $student = $this->makeStudent(6);
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpr6admtest',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Вариант ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [['task_number' => 2, 'topic_id' => '02', 'text' => 'Задание']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(3),
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/vpr/test/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('Вариант ВПР 6 класс');
    }

    public function test_admin_can_open_student_vpr_results_page(): void
    {
        $student = $this->makeStudent(6);
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpr6admres',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Вариант ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [['task_number' => 2, 'topic_id' => '02', 'text' => 'Задание']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 2,
            'is_correct' => true,
            'correct_answer' => '42',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/vpr/results/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('Результаты');
    }

    public function test_admin_can_open_student_history_detail_for_vpr_attempt(): void
    {
        $student = $this->makeStudent(6);
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpr6admhist',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Мини-ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => ['tasks' => [['task_number' => 12, 'topic_id' => '12', 'text' => 'Задание']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 12,
            'is_correct' => false,
            'correct_answer' => '42',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/history/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('Мини-ВПР');
    }

    public function test_admin_can_open_student_history_list_by_student_id(): void
    {
        $student = $this->makeStudent(5);
        $student->forceFill(['name' => 'Мария Прокина'])->save();
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpr5adminhist',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [['task_number' => 1, 'topic_id' => '01', 'text' => 'Задание']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
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

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/history/{$student->id}");

        $response->assertOk();
        $response->assertSee('Мария Прокина');
        $response->assertSee("/history/{$student->id}/{$attempt->id}", false);
        $response->assertDontSee("/history/{$attempt->id}", false);
    }

    public function test_admin_can_open_student_history_attempt_by_student_and_attempt_id(): void
    {
        $student = $this->makeStudent(6);
        $student->forceFill(['name' => 'Маша Митрофанова'])->save();
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpr6ahd',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Мини-ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => ['tasks' => [['task_number' => 12, 'topic_id' => '12', 'text' => 'Задание']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(4),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 12,
            'is_correct' => false,
            'correct_answer' => '42',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/history/{$student->id}/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('Маша Митрофанова');
        $response->assertSee("/history/{$student->id}", false);
    }

    public function test_admin_history_attempt_route_requires_attempt_to_belong_to_student(): void
    {
        $student = $this->makeStudent(5);
        $otherStudent = $this->makeStudent(6);
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpradminmismatch',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Вариант ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [['task_number' => 2, 'topic_id' => '02', 'text' => 'Задание']]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $otherStudent->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(6),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 2,
            'is_correct' => true,
            'correct_answer' => '42',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/history/{$student->id}/{$attempt->id}");

        $response->assertNotFound();
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

    public function test_vpr_test_template_supports_special_rendering_for_tasks_4_10_and_14(): void
    {
        $user = $this->makeStudent(5);

        $variant = OgeVariant::create([
            'hash' => 'vpr5ui',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [
                [
                    'task_number' => 4,
                    'topic_id' => '04',
                    'text' => "Вступление.\n\n1) Первый вопрос?\n2) Второй вопрос?",
                    'answer_1' => '1',
                    'answer_2' => '2',
                ],
                [
                    'task_number' => 10,
                    'topic_id' => '10',
                    'text' => "А) $\\frac{17}{9}$\nБ) $\\frac{15}{17}$\nВ) $\\frac{12}{29}$\n\n1) Больше 1.\n2) Меньше 0,5.\n3) Между 0,5 и 1.",
                ],
                [
                    'task_number' => 14,
                    'topic_id' => '14',
                    'text' => 'Нужно купить 60 кг стирального порошка.',
                    'table' => [
                        'headers' => ['Стиральный порошок', 'Масса, кг', 'Цена, руб.'],
                        'rows' => [
                            ['А', '15', '1700'],
                            ['Б', '10', '1100'],
                        ],
                    ],
                ],
            ]],
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
        $response->assertSee('isTwoAnswerTask(currentTask)', false);
        $response->assertSee('taskPromptIntro(currentTask)', false);
        $response->assertSee('taskPromptQuestions(currentTask)', false);
        $response->assertSee('isTaskTenMatrix(currentTask)', false);
        $response->assertSee('taskTenFractions(currentTask)', false);
        $response->assertSee('taskHasHtmlTable(currentTask)', false);
    }

    public function test_vpr_test_maps_legacy_mini_answers_from_exam_numbers_to_attempt_slots(): void
    {
        $user = $this->makeStudent(6);

        $variant = OgeVariant::create([
            'hash' => 'vpr6legacy',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Мини-ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => ['tasks' => [
                ['task_number' => 2, 'topic_id' => '02', 'text' => 'Первое задание'],
                ['task_number' => 3, 'topic_id' => '03', 'text' => 'Второе задание'],
                ['task_number' => 12, 'topic_id' => '12', 'text' => 'Третье задание'],
                ['task_number' => 16, 'topic_id' => '16', 'text' => 'Четвёртое задание'],
                ['task_number' => 17, 'topic_id' => '17', 'text' => 'Пятое задание'],
            ]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'active',
            'started_at' => now()->subMinutes(4),
            'last_seen_at' => now(),
        ]);

        $attempt->answers()->createMany([
            ['task_number' => 2, 'current_answer' => '-3,15', 'commits_count' => 1, 'is_final' => false],
            ['task_number' => 3, 'current_answer' => '196', 'commits_count' => 1, 'is_final' => false],
            ['task_number' => 12, 'current_answer' => '10', 'commits_count' => 1, 'is_final' => false],
            ['task_number' => 16, 'current_answer' => '162', 'commits_count' => 1, 'is_final' => false],
            ['task_number' => 17, 'current_answer' => '53', 'commits_count' => 1, 'is_final' => false],
        ]);

        $response = $this->actingAs($user)
            ->get("http://student.palomatika.ru/vpr/test/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('"attempt_task_number":1', false);
        $response->assertSee('"display_task_number":12', false);
        $response->assertSee('"1":"-3,15"', false);
        $response->assertSee('"3":"10"', false);
        $response->assertSee('"5":"53"', false);
        $response->assertDontSee('"12":"10"', false);
        $response->assertDontSee('"16":"162"', false);
        $response->assertDontSee('"17":"53"', false);
    }

    public function test_vpr_results_page_uses_canonical_task_slots_for_legacy_mini_attempts(): void
    {
        $user = $this->makeStudent(6);

        $variant = OgeVariant::create([
            'hash' => 'vpr6legacyres',
            'exam_type' => OgeVariant::EXAM_VPR6,
            'title' => 'Мини-ВПР 6 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_MINI_MIXED,
            'config_json' => ['tasks' => [
                ['task_number' => 2, 'topic_id' => '02', 'text' => 'Первое задание'],
                ['task_number' => 3, 'topic_id' => '03', 'text' => 'Второе задание'],
                ['task_number' => 12, 'topic_id' => '12', 'text' => 'Третье задание'],
                ['task_number' => 16, 'topic_id' => '16', 'text' => 'Четвёртое задание'],
                ['task_number' => 17, 'topic_id' => '17', 'text' => 'Пятое задание'],
            ]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $user->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        $attempt->answers()->createMany([
            ['task_number' => 2, 'current_answer' => '-3,15', 'commits_count' => 1, 'is_final' => true],
            ['task_number' => 3, 'current_answer' => '196', 'commits_count' => 1, 'is_final' => true],
            ['task_number' => 12, 'current_answer' => '10', 'commits_count' => 1, 'is_final' => true],
            ['task_number' => 16, 'current_answer' => '162', 'commits_count' => 1, 'is_final' => true],
            ['task_number' => 17, 'current_answer' => '53', 'commits_count' => 1, 'is_final' => true],
        ]);

        $attempt->scorings()->createMany([
            ['task_number' => 1, 'is_correct' => false, 'correct_answer' => '3.15', 'checked_at' => now()],
            ['task_number' => 2, 'is_correct' => true, 'correct_answer' => '196', 'checked_at' => now()],
            ['task_number' => 3, 'is_correct' => true, 'correct_answer' => '10', 'checked_at' => now()],
            ['task_number' => 4, 'is_correct' => false, 'correct_answer' => '156', 'checked_at' => now()],
            ['task_number' => 5, 'is_correct' => false, 'correct_answer' => '31', 'checked_at' => now()],
            ['task_number' => 12, 'is_correct' => null, 'correct_answer' => null, 'checked_at' => now()],
            ['task_number' => 16, 'is_correct' => null, 'correct_answer' => null, 'checked_at' => now()],
            ['task_number' => 17, 'is_correct' => null, 'correct_answer' => null, 'checked_at' => now()],
        ]);

        $response = $this->actingAs($user)
            ->get("http://student.palomatika.ru/vpr/results/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('"num":2', false);
        $response->assertSee('"num":12', false);
        $response->assertSee('"num":16', false);
        $response->assertSee('"num":17', false);
    }

    public function test_admin_history_detail_canonicalizes_legacy_vpr_table_tasks(): void
    {
        $student = $this->makeStudent(5);
        $admin = $this->makeAdmin();

        $variant = OgeVariant::create([
            'hash' => 'vpr5legacytable',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [[
                'id' => 1,
                'task_number' => 14,
                'topic_id' => '14',
                'source_pdf' => '1_trenirovochny_variant_VPR_2025_po_matematike_5_klass.pdf',
                'text' => 'legacy',
                'image' => '<svg><image href="data:image/png;base64,AAAA" /></svg>',
            ]]],
        ]);

        $attempt = OgeAttempt::create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'scored',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'last_seen_at' => now(),
        ]);

        OgeAttemptScoring::create([
            'attempt_id' => $attempt->id,
            'task_number' => 14,
            'is_correct' => false,
            'correct_answer' => '6450',
            'checked_at' => now(),
        ]);

        $attempt->answers()->create([
            'task_number' => 14,
            'current_answer' => '6400',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("http://student.palomatika.ru/history/{$student->id}/{$attempt->id}");

        $response->assertOk();
        $response->assertSee('Нужно купить 60 кг стирального порошка.');
        $response->assertSee('6450');
        $response->assertSee('6400');
        $response->assertDontSee('data:image/png;base64', false);
    }

    public function test_vpr_full_start_skips_legacy_pool_variants_with_embedded_base64_images(): void
    {
        $user = $this->makeStudent(5);

        $dirtyVariant = OgeVariant::create([
            'hash' => 'vpr5dirtypool',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Грязный вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [[
                'id' => 1,
                'task_number' => 14,
                'topic_id' => '14',
                'source_pdf' => '1_trenirovochny_variant_VPR_2025_po_matematike_5_klass.pdf',
                'image' => '<svg><image href="data:image/png;base64,AAAA" /></svg>',
            ]]],
        ]);

        $cleanVariant = OgeVariant::create([
            'hash' => 'vpr5cleanpool',
            'exam_type' => OgeVariant::EXAM_VPR5,
            'title' => 'Чистый вариант ВПР 5 класс',
            'source' => OgeVariant::SOURCE_MINIAPP,
            'mode' => OgeVariant::MODE_FULL,
            'config_json' => ['tasks' => [[
                'id' => 1,
                'task_number' => 14,
                'topic_id' => '14',
                'text' => 'Нужно купить 60 кг стирального порошка.',
                'table' => [
                    'headers' => ['Стиральный порошок', 'Масса, кг', 'Цена, руб.'],
                    'rows' => [
                        ['Ариэль', '15', '1700'],
                        ['Лотос', '10', '1100'],
                    ],
                ],
            ]]],
        ]);

        OgeVariantPoolEntry::create([
            'variant_id' => $dirtyVariant->id,
            'type' => 'full',
            'status' => 'active',
            'task_fingerprint' => 'dirty-fingerprint',
            'created_at' => now(),
        ]);

        OgeVariantPoolEntry::create([
            'variant_id' => $cleanVariant->id,
            'type' => 'full',
            'status' => 'active',
            'task_fingerprint' => 'clean-fingerprint',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('http://student.palomatika.ru/vpr/full/start');

        $response->assertOk();

        $attempt = OgeAttempt::latest('id')->first();

        $this->assertNotNull($attempt);
        $this->assertSame($cleanVariant->id, $attempt->variant_id);
        $this->assertNotSame($dirtyVariant->id, $attempt->variant_id);
    }

    public function test_grade_5_vpr_bank_contains_explicit_question_for_task_1_and_structured_tables_for_topic_14(): void
    {
        $topicOne = json_decode((string) file_get_contents(storage_path('app/tasks/vpr/grade_5/topic_01.json')), true);
        $topicFourteen = json_decode((string) file_get_contents(storage_path('app/tasks/vpr/grade_5/topic_14.json')), true);

        $taskOne = $topicOne['blocks'][0]['zadaniya'][0]['tasks'][0] ?? null;
        $topicFourteenTasks = $topicFourteen['blocks'][0]['zadaniya'][0]['tasks'] ?? [];
        $taskFourteen = $topicFourteenTasks[0] ?? null;

        $this->assertIsArray($taskOne);
        $this->assertIsArray($taskFourteen);
        $this->assertIsString($taskOne['text'] ?? null);
        $this->assertStringContainsString('Какой', $taskOne['text']);
        $this->assertStringContainsString('?', $taskOne['text']);

        $this->assertSame(
            'Нужно купить 60 кг стирального порошка. Данные о цене и массе стирального порошка в упаковке указаны в таблице. Сколько будет стоить самая дешёвая покупка? Ответ дайте в рублях.',
            $taskFourteen['text'] ?? null
        );
        $this->assertCount(10, $topicFourteenTasks);
        $this->assertArrayHasKey('table', $taskFourteen);
        $this->assertSame(
            ['Стиральный порошок', 'Масса, кг', 'Цена, руб.'],
            $taskFourteen['table']['headers'] ?? null
        );
        $this->assertCount(4, $taskFourteen['table']['rows'] ?? []);
        $this->assertArrayNotHasKey('image', $taskFourteen);

        foreach ($topicFourteenTasks as $topicFourteenTask) {
            $this->assertArrayHasKey('table', $topicFourteenTask, 'Task '.$topicFourteenTask['id'].' is missing a structured table.');
            $this->assertNotEmpty($topicFourteenTask['table']['headers'] ?? [], 'Task '.$topicFourteenTask['id'].' must have table headers.');
            $this->assertNotEmpty($topicFourteenTask['table']['rows'] ?? [], 'Task '.$topicFourteenTask['id'].' must have table rows.');
        }
    }

    public function test_vpr_topic_bank_page_renders_structured_table_for_topic_14(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get('/vpr-topics/5/14');

        $response->assertOk();
        $response->assertSee('Стиральный порошок');
        $response->assertSee('Ариэль');
        $response->assertSee('Молекула');
        $response->assertSee('6450');
    }

    public function test_student_test_templates_do_not_force_numeric_inputmode(): void
    {
        $templates = [
            resource_path('views/pwa/student/test.blade.php'),
            resource_path('views/pwa/student/ege-test.blade.php'),
            resource_path('views/pwa/student/vpr-test.blade.php'),
        ];

        foreach ($templates as $template) {
            $contents = (string) file_get_contents($template);
            $this->assertStringNotContainsString('inputmode="numeric"', $contents, $template.' should not force numeric inputmode on Android.');
        }
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

    public function test_history_back_button_points_to_vpr_dashboard_for_grade_5(): void
    {
        $user = $this->makeStudent(5);

        $response = $this->actingAs($user)
            ->get('http://student.palomatika.ru/history');

        $response->assertOk();
        $response->assertSee('href="' . route('pwa.student.vpr.home') . '"', false);
        $response->assertDontSee('href="' . route('pwa.student.dashboard') . '" class="back"', false);
    }
}
