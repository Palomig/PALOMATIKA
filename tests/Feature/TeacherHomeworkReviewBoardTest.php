<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Страница домашки учителя — доска проверки: «Новые» (ученик что-то сдал),
 * «Проверенные», «Статистика». Расписания там больше нет.
 */
class TeacherHomeworkReviewBoardTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response([], 200)]);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    private function url(): string
    {
        return 'https://teacher.' . config('app.base_domain') . '/homework';
    }

    private function student(string $name, int $grade = 9): User
    {
        $student = User::factory()->create(['role' => 'student', 'name' => $name, 'grade_num' => $grade]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        return $student;
    }

    /** @return array{assignment:HomeworkAssignment, task:HomeworkTopicTask} */
    private function assign(User $student, string $title = 'Тема 23', int $tasks = 2): array
    {
        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => $tasks,
            'title' => $title,
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Задача'],
            'correct_answer' => '12',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'started',
            'tasks_total' => $tasks,
        ]);

        return ['assignment' => $assignment, 'task' => $task];
    }

    private function submit(array $pair): void
    {
        HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $pair['assignment']->id,
            'homework_topic_task_id' => $pair['task']->id,
            'attempts_count' => 1,
            'first_answer' => '12',
            'is_correct' => true,
            'accepted_at' => now(),
        ]);
        $pair['assignment']->update(['tasks_completed' => 1]);
    }

    public function test_new_tab_shows_only_work_with_something_to_check(): void
    {
        $submitted = $this->student('Арина', 9);
        $silent = $this->student('Тихон', 8);

        $this->submit($this->assign($submitted, 'ДЗ по уроку 24.07'));
        $this->assign($silent, 'ДЗ без единой сдачи');

        $newTab = $this->newTabHtml();

        $this->assertStringContainsString('Арина', $newTab);
        $this->assertStringContainsString('9 класс', $newTab);
        $this->assertStringContainsString('ДЗ по уроку 24.07', $newTab);
        // Работа, к которой никто не притрагивался, в «Новых» не висит
        // (в статистике она законно есть — там показываем все ДЗ).
        $this->assertStringNotContainsString('ДЗ без единой сдачи', $newTab);
        $this->assertStringNotContainsString('Тихон', $newTab);
    }

    /** Кусок страницы со вкладкой «Новые» — только он отвечает за очередь проверки. */
    private function newTabHtml(): string
    {
        $html = $this->actingAs($this->teacher)->get($this->url())->assertOk()->getContent();
        $from = strpos($html, "x-show=\"tab === 'new'\"");
        $to = strpos($html, "x-show=\"tab === 'done'\"");
        $this->assertNotFalse($from);
        $this->assertNotFalse($to);

        return substr($html, $from, $to - $from);
    }

    public function test_reviewed_work_leaves_the_new_tab(): void
    {
        $student = $this->student('Гриша');
        $pair = $this->assign($student, 'Проверяемое ДЗ');
        $this->submit($pair);

        $this->actingAs($this->teacher)
            ->post('https://teacher.' . config('app.base_domain') . "/homework/assignment/{$pair['assignment']->id}/reviewed")
            ->assertRedirect($this->url());   // после «проверено» возвращаемся в список

        $html = $this->actingAs($this->teacher)->get($this->url())->assertOk()->getContent();

        // Из «Новых» работа ушла, осталась во вкладке «Проверенные» с датой.
        $this->assertStringContainsString('Непроверенных работ нет.', $html);
        $this->assertStringContainsString('проверено ' . now()->format('d.m.Y'), $html);
        $this->assertStringContainsString('check-card is-done', $html);
    }

    public function test_schedule_is_gone_but_assigning_stays(): void
    {
        $response = $this->actingAs($this->teacher)->get($this->url())->assertOk();

        $response->assertDontSee('Прошлый');
        $response->assertSee('Новые');
        $response->assertSee('Проверенные');
        $response->assertSee('Статистика');
        // Резервная выдача и привязки профилей никуда не делись.
        $response->assertSee('Выдать ДЗ');
        $response->assertSee('Привязки учеников');
    }

    public function test_statistics_counts_submitted_and_remaining(): void
    {
        $a = $this->student('Первый');
        $b = $this->student('Второй');
        $c = $this->student('Третий');

        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Общее ДЗ',
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Задача'],
            'correct_answer' => '12',
        ]);
        foreach ([$a, $b, $c] as $student) {
            $assignment = HomeworkAssignment::create([
                'homework_id' => $homework->id,
                'student_id' => $student->id,
                'status' => 'started',
                'tasks_total' => 1,
            ]);
            if ($student->id === $a->id) {
                $this->submit(['assignment' => $assignment, 'task' => $task]);
            }
        }

        $html = $this->actingAs($this->teacher)->get($this->url())->assertOk()->getContent();

        $this->assertStringContainsString('сдали 1 из 3', $html);
        $this->assertStringContainsString('Общее ДЗ', $html);
    }

    public function test_statistics_lists_repeat_offenders(): void
    {
        $lazy = $this->student('Прогульщик');
        $ok = $this->student('Молодец');

        // Два незакрытых ДЗ — попадает в «кто не делает».
        $this->assign($lazy, 'Первое');
        $this->assign($lazy, 'Второе');

        // Одно незакрытое — ещё не «несколько раз».
        $this->assign($ok, 'Единственное');

        $html = $this->actingAs($this->teacher)->get($this->url())->assertOk()->getContent();

        $this->assertStringContainsString('Прогульщик', $html);
        $this->assertStringContainsString('не сдано работ: 2', $html);
        $this->assertStringNotContainsString('не сдано работ: 1', $html);
    }

    public function test_teacher_sees_only_own_homework(): void
    {
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Чужой ученик']);
        TeacherStudent::create(['teacher_id' => $otherTeacher->id, 'student_id' => $student->id, 'source' => 'manual']);

        $homework = Homework::create([
            'teacher_id' => $otherTeacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Чужое ДЗ',
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Задача'],
            'correct_answer' => '12',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'started',
            'tasks_total' => 1,
        ]);
        $this->submit(['assignment' => $assignment, 'task' => $task]);

        $this->actingAs($this->teacher)->get($this->url())->assertOk()
            ->assertDontSee('Чужое ДЗ')
            ->assertDontSee('Чужой ученик');
    }
}
