<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Уборка тестовых ДЗ должна снести ровно QA-мусор и не тронуть чужие ДЗ —
 * миграция гоняется по продовой базе, где рядом лежат живые данные учеников.
 */
class QaHomeworkCleanupMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function homeworkFor(User $teacher, User $student, string $assignedAt): array
    {
        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 23,
            'tasks_count' => 1,
            'title' => 'Тема 23',
            'assigned_at' => $assignedAt,
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
        $submission = HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $task->id,
            'attempts_count' => 1,
            'first_answer' => '12',
            'is_correct' => true,
            'solution_photo_remote_id' => 'p.test.sig',
            'accepted_at' => now(),
        ]);

        return compact('homework', 'task', 'assignment', 'submission');
    }

    public function test_removes_qa_homework_with_all_children_and_keeps_the_rest(): void
    {
        $qaTeacher = User::factory()->create(['role' => 'teacher', 'email' => 'qa-teacher@palomatika.ru']);
        $realTeacher = User::factory()->create(['role' => 'teacher']);
        $qaStudent = User::factory()->create(['role' => 'student']);
        $realStudent = User::factory()->create(['role' => 'student']);

        $qa = $this->homeworkFor($qaTeacher, $qaStudent, '2026-07-30 12:16:39');
        $real = $this->homeworkFor($realTeacher, $realStudent, '2026-07-24 14:57:54');
        // QA-демо после окна уборки трогать нельзя.
        $later = $this->homeworkFor($qaTeacher, $qaStudent, '2026-08-05 10:00:00');

        $this->runCleanup();

        $this->assertDatabaseMissing('homeworks', ['id' => $qa['homework']->id]);
        $this->assertDatabaseMissing('homework_topic_tasks', ['id' => $qa['task']->id]);
        $this->assertDatabaseMissing('homework_assignments', ['id' => $qa['assignment']->id]);
        $this->assertDatabaseMissing('homework_topic_task_submissions', ['id' => $qa['submission']->id]);

        $this->assertDatabaseHas('homeworks', ['id' => $real['homework']->id]);
        $this->assertDatabaseHas('homework_topic_task_submissions', ['id' => $real['submission']->id]);
        $this->assertDatabaseHas('homeworks', ['id' => $later['homework']->id]);

        // Ученики и учителя остаются на месте — чистим только ДЗ.
        $this->assertDatabaseHas('users', ['id' => $qaStudent->id]);
        $this->assertDatabaseHas('users', ['id' => $qaTeacher->id]);
    }

    public function test_is_safe_without_qa_account(): void
    {
        $realTeacher = User::factory()->create(['role' => 'teacher']);
        $realStudent = User::factory()->create(['role' => 'student']);
        $real = $this->homeworkFor($realTeacher, $realStudent, '2026-07-30 12:00:00');

        $this->runCleanup();

        $this->assertDatabaseHas('homeworks', ['id' => $real['homework']->id]);
    }

    private function runCleanup(): void
    {
        $migration = require database_path('migrations/2026_07_30_000002_cleanup_qa_homework_test_data.php');
        $migration->up();

        // Повторный запуск не должен падать (миграции гоняются и на других средах).
        $migration->up();
        $this->assertTrue(DB::getPdo() !== null);
    }
}
