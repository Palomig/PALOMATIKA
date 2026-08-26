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
 * Вкладка «Статистика» на странице домашки: плашка домашки раскрывается
 * и показывает поимённо, кто сдал, кто открыл и не сдал, а кто не открывал.
 */
class HomeworkStatsBreakdownTest extends TestCase
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

    public function test_stats_card_lists_who_submitted_and_who_did_not_open(): void
    {
        $homework = Homework::create([
            'teacher_id' => $this->teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'ДЗ по уроку — тема 23',
            'assigned_at' => now(),
        ]);
        $task = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 23,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'text' => 'Найдите высоту.'],
            'correct_answer' => '12',
        ]);

        $submitted = $this->student('Аня Сдавшая');
        $opened = $this->student('Боря Открывший');
        $untouched = $this->student('Вася Спящий');

        $submittedAssignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $submitted->id,
            'status' => 'completed',
            'tasks_total' => 1,
            'tasks_completed' => 1,
            'started_at' => now()->subHour(),
        ]);
        HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $submittedAssignment->id,
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
            'tasks_total' => 1,
            'started_at' => now()->subMinutes(20),
        ]);
        HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $untouched->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        $html = $this->actingAs($this->teacher)
            ->get('https://teacher.' . config('app.base_domain') . '/homework')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('открыли 2 из 3', $html);
        $this->assertStringContainsString('не открывали: 1', $html);

        $this->assertStringContainsString('Аня Сдавшая', $html);
        $this->assertStringContainsString('сдал всё', $html);
        $this->assertStringContainsString('Боря Открывший', $html);
        $this->assertStringContainsString('открыл, но ничего не сдал', $html);
        $this->assertStringContainsString('Вася Спящий', $html);
        $this->assertStringContainsString('не открывал', $html);

        // Несдавшие идут выше сдавших внутри самой плашки: имена ещё раз
        // встречаются во вкладке «Новые», поэтому смотрим только хвост страницы.
        $card = substr($html, (int) strpos($html, 'По домашкам'));
        $this->assertLessThan(
            strpos($card, 'Аня Сдавшая'),
            strpos($card, 'Вася Спящий'),
            'Не открывавший ученик должен стоять выше сдавшего'
        );

        // У сдавшего есть переход на страницу проверки, у не открывавшего — нет.
        $this->assertStringContainsString("/homework/assignment/{$submittedAssignment->id}\"", $html);
    }
}
