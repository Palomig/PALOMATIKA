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
 * Вкладка «Не сделаны» на странице домашки: плоский список выданных, но
 * не доведённых до конца работ — кто открыл и не сдал, кто не открывал, кто сдал часть.
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
}
