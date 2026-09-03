<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\HomeworkTopicTaskSubmission;
use App\Models\LessonSession;
use App\Models\LessonSessionAttempt;
use App\Models\LessonSessionTask;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Снимок задачи в домашке и на уроке живёт своей жизнью: правка ответа в
 * банке до него не доходит, и ученик остаётся с «неверно» за верный ответ.
 */
class ResyncTaskAnswerSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    private const GUID = 'aaaaaaaabbbbccccddddeeeeffff0001';

    private function bankTask(string $answer): Task
    {
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => '01',
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 2,
            'position' => 0, 'instruction' => '', 'type' => 'fipi',
            'payload' => ['type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);

        return Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 5, 'html' => '<p>Условие</p>', 'answer' => $answer, 'status' => 'production'],
            'answer' => $answer, 'answer_src' => 'claude', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => self::GUID,
        ]);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => $role, 'email' => $role . uniqid() . '@t.t', 'password' => 'x', 'role' => $role,
        ]);
    }

    public function test_homework_and_lesson_snapshots_follow_the_bank(): void
    {
        $this->bankTask('67');
        $teacher = $this->user('teacher');
        $student = $this->user('student');

        $homework = Homework::create([
            'teacher_id' => $teacher->id, 'title' => 'ДЗ', 'homework_type' => 'topic_random', 'tasks_count' => 1,
        ]);
        $hwTask = HomeworkTopicTask::create([
            'homework_id' => $homework->id, 'topic_number' => 1, 'task_order' => 2,
            'task_payload' => ['id' => 5, 'fipi_guid' => self::GUID, 'answer' => '84', 'expression' => 'Условие'],
            'correct_answer' => '84',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id, 'student_id' => $student->id,
            'status' => 'started', 'tasks_total' => 1, 'tasks_completed' => 1, 'tasks_correct' => 0,
        ]);
        $submission = HomeworkTopicTaskSubmission::create([
            'homework_assignment_id' => $assignment->id, 'homework_topic_task_id' => $hwTask->id,
            'attempts_count' => 2, 'first_answer' => '67', 'second_answer' => '67',
            'is_correct' => false, 'accepted_at' => now(),
        ]);

        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'ended', 'join_code' => '1234']);
        $lessonTask = LessonSessionTask::create([
            'lesson_session_id' => $session->id, 'position' => 1, 'bank' => 'ege',
            'topic_id' => '01', 'task_ref' => json_encode(['task_id' => 5]),
            'task_payload' => ['expression' => 'Условие', 'answer' => '84', 'raw' => ['fipi_guid' => self::GUID, 'answer' => '84']],
            'correct_answer' => '84',
        ]);
        $attempt = LessonSessionAttempt::create([
            'lesson_session_id' => $session->id, 'lesson_session_task_id' => $lessonTask->id,
            'student_id' => $student->id, 'answer_raw' => '67', 'is_correct' => false, 'answered_at' => now(),
        ]);

        $this->artisan('tasks:resync-answer', ['--guid' => self::GUID, '--dry-run' => true])->assertExitCode(0);
        $this->assertSame('84', $hwTask->fresh()->correct_answer, 'dry-run не должен писать');

        $this->artisan('tasks:resync-answer', ['--guid' => self::GUID])->assertExitCode(0);

        $hwTask->refresh();
        $this->assertSame('67', $hwTask->correct_answer);
        $this->assertSame('67', $hwTask->task_payload['answer']);
        $this->assertTrue((bool) $submission->fresh()->is_correct);
        $this->assertSame(1, (int) $assignment->fresh()->tasks_correct);

        $lessonTask->refresh();
        $this->assertSame('67', $lessonTask->correct_answer);
        $this->assertSame('67', $lessonTask->task_payload['answer']);
        $this->assertSame('67', $lessonTask->task_payload['raw']['answer']);
        $this->assertTrue((bool) $attempt->fresh()->is_correct);
    }

    public function test_wrong_answer_stays_wrong(): void
    {
        $this->bankTask('67');
        $teacher = $this->user('teacher');
        $student = $this->user('student');

        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'ended', 'join_code' => '4321']);
        $lessonTask = LessonSessionTask::create([
            'lesson_session_id' => $session->id, 'position' => 1, 'bank' => 'ege',
            'topic_id' => '01', 'task_ref' => json_encode(['task_id' => 5]),
            'task_payload' => ['expression' => 'Условие', 'answer' => '84', 'raw' => ['fipi_guid' => self::GUID]],
            'correct_answer' => '84',
        ]);
        $attempt = LessonSessionAttempt::create([
            'lesson_session_id' => $session->id, 'lesson_session_task_id' => $lessonTask->id,
            'student_id' => $student->id, 'answer_raw' => '84', 'is_correct' => true, 'answered_at' => now(),
        ]);

        $this->artisan('tasks:resync-answer', ['--guid' => self::GUID])->assertExitCode(0);

        $this->assertSame('67', $lessonTask->fresh()->correct_answer);
        $this->assertFalse((bool) $attempt->fresh()->is_correct, 'ответ 84 стал неверным');
    }
}
