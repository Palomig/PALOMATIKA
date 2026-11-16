<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Вкладка «Не сделаны»: ученики со свёрнутыми списками всех невыполненных работ.
 */
class HomeworkUndoneTabTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    private function student(string $name): User
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => $name,
            'onboarding_completed_at' => now(),
        ]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        return $student;
    }

    /** Кусок страницы со вкладкой «Не сделаны». */
    private function undoneTabHtml(): string
    {
        $html = $this->actingAs($this->teacher)
            ->get('https://teacher.' . config('app.base_domain') . '/homework')
            ->assertOk()
            ->getContent();
        $from = strpos($html, "x-show=\"tab === 'undone'\"");
        $to = strpos($html, 'Привязки учеников');
        $this->assertNotFalse($from);
        $this->assertNotFalse($to);

        return substr($html, $from, $to - $from);
    }

    public function test_undone_tab_lists_everyone_who_has_not_finished(): void
    {
        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 2,
            'title' => 'ДЗ по уроку — тема 23',
            'assigned_at' => now()->setDate(2026, 9, 12),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Найдите высоту.'],
            'correct_answer' => '12',
        ]);

        $done = $this->student('Аня Сдавшая');
        $partial = $this->student('Петя Половинка');
        $opened = $this->student('Боря Открывший');
        $untouched = $this->student('Вася Спящий');

        HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $done->id,
            'status' => 'completed',
            'tasks_total' => 2,
            'tasks_completed' => 2,
            'started_at' => now()->subHour(),
        ]);
        $partialAssignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $partial->id,
            'status' => 'started',
            'tasks_total' => 2,
            'tasks_completed' => 1,
            'started_at' => now()->subHour(),
        ]);
        HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $partialAssignment->id,
            'homework_topic_task_id' => $task->id,
            'attempts_count' => 1,
            'first_answer' => '12',
            'is_correct' => true,
            'accepted_at' => now(),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $opened->id,
            'status' => 'started',
            'tasks_total' => 2,
            'started_at' => now()->subMinutes(20),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $untouched->id,
            'status' => 'assigned',
            'tasks_total' => 2,
        ]);

        $tab = $this->undoneTabHtml();

        // Доделавший до конца в списке не висит.
        $this->assertStringNotContainsString('Аня Сдавшая', $tab);

        $this->assertStringContainsString('Петя Половинка', $tab);
        $this->assertStringContainsString('сдано 1 из 2', $tab);
        $this->assertStringContainsString('Боря Открывший', $tab);
        $this->assertStringContainsString('открыл, но ничего не сдал', $tab);
        $this->assertStringContainsString('Вася Спящий', $tab);
        $this->assertStringContainsString('не открывал · выдано 12.09', $tab);
        $this->assertStringContainsString('ДЗ по уроку — тема 23', $tab);

        // Открыть страницу проверки можно только у того, кто что-то сдал.
        $this->assertStringContainsString("/homework/assignment/{$partialAssignment->id}\"", $tab);
        $this->assertSame(1, substr_count($tab, '/homework/assignment/'));
    }

    public function test_reviewed_work_leaves_the_undone_tab_and_newer_homework_goes_first(): void
    {
        $student = $this->student('Гриша');

        $old = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Старое ДЗ',
            'assigned_at' => now()->subDays(3),
        ]);
        $fresh = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Свежее ДЗ',
            'assigned_at' => now(),
        ]);
        $closed = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Закрытое учителем ДЗ',
            'assigned_at' => now()->subDay(),
        ]);
        foreach ([$old, $fresh] as $hw) {
            HomeworkAssignment::create([
                'homework_id' => $hw->id,
                'student_id' => $student->id,
                'status' => 'assigned',
                'tasks_total' => 1,
                'debt_since' => $hw->id === $old->id ? now() : null,
            ]);
        }
        HomeworkAssignment::create([
            'homework_id' => $closed->id,
            'student_id' => $student->id,
            'status' => 'started',
            'tasks_total' => 1,
            'reviewed_at' => now(),
            'reviewed_by' => $this->teacher->id,
        ]);

        $tab = $this->undoneTabHtml();

        $this->assertStringContainsString('Старое ДЗ', $tab);
        $this->assertStringContainsString('Свежее ДЗ', $tab);
        // Учитель нажал «проверено» — работа ушла в «Проверенные», а не висит несделанной.
        $this->assertStringNotContainsString('Закрытое учителем ДЗ', $tab);
        $this->assertLessThan(strpos($tab, 'Старое ДЗ'), strpos($tab, 'Свежее ДЗ'), 'Свежая домашка должна стоять выше старой');
        $this->assertStringContainsString('долг', $tab);
    }

    public function test_students_are_grouped_by_id_and_sorted_by_latest_assignment_date(): void
    {
        $first = $this->student('Одинаковое имя');
        $second = $this->student('Одинаковое имя');
        TeacherStudent::where('teacher_id', $this->teacher->id)
            ->where('student_id', $first->id)->update(['student_alias' => 'Алиас ученика']);

        // Создаём не по дате выдачи, чтобы сортировка по ID не могла пройти тест.
        foreach ([
            [$second, 'Средняя домашка', '2026-11-10 12:00:00'],
            [$first, 'Самая новая домашка', '2026-11-15 12:00:00'],
            [$first, 'Самая старая домашка', '2026-10-01 12:00:00'],
        ] as [$student, $title, $date]) {
            $hw = Homework::create([
                'teacher_id' => $this->teacher->id,
                'homework_type' => 'full_variant',
                'tasks_count' => 1,
                'title' => $title,
                'assigned_at' => $date,
            ]);
            HomeworkAssignment::create([
                'homework_id' => $hw->id, 'student_id' => $student->id,
                'status' => 'assigned', 'tasks_total' => 1,
            ]);
        }

        $tab = $this->undoneTabHtml();
        $this->assertSame(2, substr_count($tab, '<details class="undone-student"'));
        $this->assertStringNotContainsString('<details open', $tab);
        $this->assertSame(1, substr_count($tab, 'Алиас ученика'));
        $this->assertStringContainsString('Не сделано: 2', $tab);
        $this->assertStringContainsString('Последняя ДЗ: 15.11.2026', $tab);
        $this->assertLessThan(strpos($tab, 'Средняя домашка'), strpos($tab, 'Самая старая домашка'));
        $this->assertLessThan(strpos($tab, 'Самая старая домашка'), strpos($tab, 'Самая новая домашка'));

        // Одинаковые имена тоже не должны склеивать разных учеников.
        TeacherStudent::where('teacher_id', $this->teacher->id)
            ->where('student_id', $first->id)->update(['student_alias' => null]);
        $this->assertSame(2, substr_count($this->undoneTabHtml(), '<details class="undone-student"'));
    }

    public function test_old_undone_work_is_not_lost_after_300_newer_assignments(): void
    {
        $student = $this->student('Ученик со старым долгом');
        for ($i = 0; $i <= 300; $i++) {
            $hw = Homework::create([
                'teacher_id' => $this->teacher->id,
                'homework_type' => 'full_variant',
                'tasks_count' => 1,
                'title' => $i === 0 ? 'Очень старый долг' : 'Завершённая работа',
                'assigned_at' => now()->subDays(301 - $i),
            ]);
            HomeworkAssignment::create([
                'homework_id' => $hw->id, 'student_id' => $student->id,
                'status' => $i === 0 ? 'assigned' : 'completed', 'tasks_total' => 1,
            ]);
        }

        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $foreign = Homework::create([
            'teacher_id' => $otherTeacher->id, 'homework_type' => 'full_variant',
            'tasks_count' => 1, 'title' => 'Работа другого учителя', 'assigned_at' => now(),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $foreign->id, 'student_id' => $student->id,
            'status' => 'assigned', 'tasks_total' => 1,
        ]);

        $tab = $this->undoneTabHtml();
        $this->assertStringContainsString('Очень старый долг', $tab);
        $this->assertStringContainsString('Не сделано: 1', $tab);
        $this->assertStringNotContainsString('Завершённая работа', $tab);
        $this->assertStringNotContainsString('Работа другого учителя', $tab);
    }

    public function test_empty_undone_tab_has_no_student_spoilers(): void
    {
        $tab = $this->undoneTabHtml();
        $this->assertStringContainsString('Все выданные домашки сделаны.', $tab);
        $this->assertStringNotContainsString('<details', $tab);
    }

    public function test_each_homework_has_its_own_read_only_task_spoiler(): void
    {
        $student = $this->student('Ученик с заданиями');
        $photo = Homework::create([
            'teacher_id' => $this->teacher->id, 'homework_type' => 'topic_photo_practice',
            'tasks_count' => 1, 'title' => 'Фото-практика', 'assigned_at' => now(),
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $photo->id, 'topic_number' => 6, 'task_order' => 1,
            'task_payload' => ['instruction' => 'Вычислите', 'expression' => '2+3', 'answer' => 'PHOTO-SECRET'],
            'correct_answer' => 'PHOTO-SECRET',
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $photo->id, 'topic_number' => 7, 'task_order' => 2,
            'task_payload' => ['type' => 'between_fractions', 'instruction' => 'Между числами', 'left' => '\\frac{8}{3}', 'right' => '\\frac{11}{4}'],
            'correct_answer' => 'STRUCTURED-SECRET',
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $photo->id, 'topic_number' => 7, 'task_order' => 3,
            'task_payload' => ['type' => 'segment', 'instruction' => 'На отрезке', 'segment' => '[3; 4]'],
            'correct_answer' => 'SEGMENT-SECRET',
        ]);
        $variant = OgeVariant::create([
            'hash' => 'preview-variant', 'exam_type' => 'vpr_5', 'mode' => 'mini_mixed',
            'config_json' => ['tasks' => [['task' => ['html' => '<p>Условие варианта</p>', 'answer' => 'VARIANT-SECRET']]]],
        ]);
        $full = Homework::create([
            'teacher_id' => $this->teacher->id, 'homework_type' => 'full_variant',
            'variant_hash' => $variant->hash, 'tasks_count' => 1, 'title' => 'Мини-вариант', 'assigned_at' => now(),
        ]);
        $assignments = collect([$photo, $full])->map(fn ($hw) => HomeworkAssignment::create([
            'homework_id' => $hw->id, 'student_id' => $student->id, 'status' => 'assigned', 'tasks_total' => 1,
        ]));
        $before = $assignments->map(fn ($a) => $a->fresh()->getAttributes())->all();
        $variantBefore = $variant->fresh()->getAttributes();
        $attemptCount = \App\Models\OgeAttempt::count();

        $tab = $this->undoneTabHtml();
        $this->assertSame(2, substr_count($tab, '<details class="undone-homework"'));
        $this->assertStringContainsString('Вычислите', $tab);
        $this->assertStringContainsString('2+3', $tab);
        $this->assertStringContainsString('\\(\\frac{8}{3} \\text{ и } \\frac{11}{4}\\)', $tab);
        $this->assertStringContainsString('\\([3; 4]\\)', $tab);
        $this->assertStringContainsString('<p>Условие варианта</p>', $tab);
        $this->assertStringNotContainsString('SECRET', $tab);
        $this->assertSame($before, $assignments->map(fn ($a) => $a->fresh()->getAttributes())->all());
        $this->assertSame($variantBefore, $variant->fresh()->getAttributes());
        $this->assertSame($attemptCount, \App\Models\OgeAttempt::count());
        $this->assertSame(0, HomeworkTopicTaskSubmission::count());
    }
}
