<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\LessonSessionService;
use App\Services\TaskAnswerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Скилл «Графики функций»: значения y ученик вписывает прямо в клетки
 * таблицы — и в домашке, и на уроке. Отдельное поле «через |» было неудобно.
 */
class ValueTableAnswerTest extends TestCase
{
    use RefreshDatabase;

    private const EXPRESSION = 'Заполните таблицу значений функции $y=2x-1$ и постройте её график. '
        . 'В ответ запишите значения $y$ через точку с запятой. '
        . '$\begin{array}{|c|c|c|c|}\hline x&-1&0&1\\\\\hline y&\phantom{-00}&\phantom{-00}&\phantom{-00}\\\\\hline\end{array}$';

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student', 'onboarding_completed_at' => now(), 'telegram_chat_id' => 245710727,
        ]);
    }

    public function test_homework_shows_table_with_inputs_instead_of_formula(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student();
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'source' => 'manual']);
        $homework = Homework::create([
            'teacher_id' => $teacher->id, 'homework_type' => 'topic_photo_practice', 'topic_number' => 7,
            'tasks_count' => 1, 'title' => 'Графики', 'assigned_at' => now(),
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $homework->id, 'topic_number' => 7, 'task_order' => 1,
            'task_payload' => ['id' => 1, 'expression' => self::EXPRESSION],
            'correct_answer' => '-3 | -1 | 1',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id, 'student_id' => $student->id, 'status' => 'assigned', 'tasks_total' => 1,
        ]);

        $this->actingAs($student)
            ->get('https://student.' . config('app.base_domain') . "/homework/{$assignment->id}")
            ->assertOk()
            ->assertSee('vt-input', false)
            ->assertSee('y при x = -1', false)
            ->assertSee('Заполните таблицу значений функции', false)
            ->assertDontSee('\begin{array}', false)
            ->assertDontSee('через точку с запятой', false);

        // Клетки уходят как «-3; -1; 1» — это верный ответ.
        $this->assertTrue((new TaskAnswerResolver())->isCorrect('-3; -1; 1', '-3 | -1 | 1'));
    }

    public function test_lesson_state_gives_student_the_table(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = $this->student();
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->joinByCode($session->join_code, $student);
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $task->update(['task_payload' => array_merge($task->task_payload, ['expression' => self::EXPRESSION])]);
        $svc->start($session);

        $tasks = $this->actingAs($student)
            ->getJson('https://student.' . config('app.base_domain') . "/lessons/{$session->id}/state")
            ->assertOk()
            ->json('tasks');

        $this->assertSame(['-1', '0', '1'], $tasks[0]['payload']['value_table']['xs']);
        $this->assertStringNotContainsString('array', $tasks[0]['payload']['value_table']['lead']);
    }
}
