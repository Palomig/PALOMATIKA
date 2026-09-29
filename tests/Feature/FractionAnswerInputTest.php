<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\TaskAnswerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ввод смешанной дроби: целая часть и числитель над знаменателем.
 *
 * «Две целых семь одиннадцатых» в одну строку с телефона не записать без
 * подсказки, поэтому у поля ответа есть раскладка из трёх полей. Она
 * собирает строку «2 7/11» — такую запись проверка ответов понимает давно,
 * и это главное, что здесь надо удержать.
 */
class FractionAnswerInputTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private HomeworkAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 245710727,
        ]);
        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $this->student->id,
            'source' => 'manual',
        ]);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'topic_number' => 0,
            'tasks_count' => 1,
            'title' => 'Скиллы: дробный ответ',
            'assigned_at' => now(),
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 0,
            'task_order' => 1,
            'task_payload' => ['id' => 1, 'expression' => '$\dfrac{\sqrt{29}}{\sqrt{11}}$'],
            'correct_answer' => '2 7/11',
        ]);
        $this->assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $this->student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);
    }

    public function test_homework_offers_the_fraction_layout(): void
    {
        $response = $this->actingAs($this->student)
            ->get('https://student.' . config('app.base_domain') . "/homework/{$this->assignment->id}");

        $response->assertOk();
        $response->assertSee('записать дробью');
        $response->assertSee('frac-line', false);
        // Обычный ввод остаётся основным: раскладку ученик открывает сам,
        // иначе вид поля подсказывал бы, что ответ — дробь.
        $response->assertSee('name="answer"', false);
    }

    public function test_composed_mixed_fraction_is_accepted(): void
    {
        $resolver = new TaskAnswerResolver();

        // Ровно то, что собирает виджет из трёх полей.
        $this->assertTrue($resolver->isCorrect('2 7/11', '2 7/11'));
        $this->assertTrue($resolver->isCorrect('29/11', '2 7/11'), 'неправильная дробь — тот же ответ');
        $this->assertTrue($resolver->isCorrect('2 7/11', '29/11'));
        $this->assertTrue($resolver->isCorrect('1 1/2', '1,5'));
        $this->assertFalse($resolver->isCorrect('2 8/11', '2 7/11'));
    }
}
