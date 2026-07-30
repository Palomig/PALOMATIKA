<?php

namespace Tests\Feature\Pwa;

use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PwaHomeworkPhotoPracticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_assigns_ten_topic_tasks_to_selected_students(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $students = User::factory()->count(2)->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);

        foreach ($students as $student) {
            TeacherStudent::create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'source' => 'manual',
            ]);
        }

        $response = $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'topic_number' => 6,
            'student_ids' => $students->pluck('id')->all(),
            'task_indices' => range(0, 9),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $homework = Homework::query()->where('homework_type', 'topic_photo_practice')->firstOrFail();

        $this->assertSame('Тема 6: 10 задач с фото решения', $homework->title);
        $this->assertSame(6, $homework->topic_number);
        $this->assertSame(10, $homework->topicTasks()->count());
        $this->assertSame(2, $homework->assignments()->count());

        foreach ($students as $student) {
            $this->assertDatabaseHas('homework_assignments', [
                'homework_id' => $homework->id,
                'student_id' => $student->id,
                'status' => 'assigned',
                'tasks_total' => 10,
            ]);
        }
    }

    public function test_topic_photo_practice_rejects_assignment_without_task_selection(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'topic_number' => 6,
            'student_ids' => [$student->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Homework::query()->count());
    }

    public function test_topic_tasks_endpoint_returns_tasks_with_answers(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($teacher)->getJson('http://teacher.palomatika.ru/homework/topic-tasks/6');
        $response->assertOk();
        $data = $response->json();

        $this->assertSame(6, $data['topic_number']);
        $this->assertNotEmpty($data['tasks']);
        $this->assertArrayHasKey('index', $data['tasks'][0]);
        $this->assertArrayHasKey('text', $data['tasks'][0]);
        $this->assertArrayHasKey('answer', $data['tasks'][0]);
    }

    public function test_teacher_assigns_mini_variant_homework(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'mini_variant',
            'student_id' => $student->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $homework = Homework::query()->latest('id')->first();
        $this->assertNotNull($homework);
        $this->assertSame('Мини-вариант ОГЭ', $homework->title);
        $this->assertNotNull($homework->variant_hash);
        $this->assertDatabaseHas('homework_assignments', [
            'homework_id' => $homework->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_teacher_assigns_mini_vpr_to_grade_5_student(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 5,
            'onboarding_completed_at' => now(),
        ]);
        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'mini_variant',
            'student_id' => $student->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $homework = Homework::query()->latest('id')->first();
        $this->assertNotNull($homework);
        $this->assertSame('Мини-ВПР 5 класс', $homework->title);
        $this->assertNotNull($homework->variant_hash);

        $variant = \App\Models\OgeVariant::where('hash', $homework->variant_hash)->firstOrFail();
        $this->assertSame('vpr_5', $variant->exam_type);
    }

    public function test_teacher_can_link_multiple_profiles_to_same_evrium_student(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $firstProfile = User::factory()->create([
            'role' => 'student',
            'name' => 'Ivan Main',
            'last_active_at' => now()->subMinutes(8),
            'onboarding_completed_at' => now(),
        ]);
        $secondProfile = User::factory()->create([
            'role' => 'student',
            'name' => 'Ivan Duplicate',
            'last_active_at' => now()->subHour(),
            'onboarding_completed_at' => now(),
        ]);

        foreach ([$firstProfile, $secondProfile] as $profile) {
            $this->actingAs($teacher)
                ->patchJson("http://teacher.palomatika.ru/students/{$profile->id}/link", [
                    'evrium_name' => 'Иван Иванов',
                    'alias' => $profile->name,
                ])
                ->assertOk()
                ->assertJson(['ok' => true]);
        }

        $this->assertDatabaseHas('teacher_students', [
            'teacher_id' => $teacher->id,
            'student_id' => $firstProfile->id,
            'evrium_name' => 'Иван Иванов',
        ]);
        $this->assertDatabaseHas('teacher_students', [
            'teacher_id' => $teacher->id,
            'student_id' => $secondProfile->id,
            'evrium_name' => 'Иван Иванов',
        ]);

        $this->assertSame(2, TeacherStudent::where('teacher_id', $teacher->id)->where('evrium_name', 'Иван Иванов')->count());
    }

    public function test_student_must_attach_photo_and_second_wrong_answer_is_accepted(): void
    {
        Storage::fake('public');

        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'title' => 'Тема 6: 10 задач с фото решения',
            'topic_number' => 6,
            'tasks_count' => 1,
            'assigned_at' => now(),
        ]);
        $homeworkTask = HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 6,
            'task_order' => 1,
            'task_payload' => [
                'id' => 101,
                'text' => 'Решите задачу с коротким ответом.',
                'answer' => 'teacher-only-token',
            ],
            'correct_answer' => 'teacher-only-token',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        $this->actingAs($student)
            ->get("http://student.palomatika.ru/homework/{$assignment->id}")
            ->assertOk()
            ->assertSee('Решите задачу с коротким ответом.')
            ->assertDontSee('teacher-only-token');

        $this->actingAs($student)
            ->post("http://student.palomatika.ru/homework/{$assignment->id}/tasks/{$homeworkTask->id}", [
                'answer' => '3',
            ])
            ->assertSessionHasErrors('solution_photos');

        $firstAttempt = $this->actingAs($student)
            ->post("http://student.palomatika.ru/homework/{$assignment->id}/tasks/{$homeworkTask->id}", [
                'answer' => '3',
                'solution_photos' => [UploadedFile::fake()->image('solution-1.jpg')],
            ]);

        $firstAttempt->assertRedirect();
        $firstAttempt->assertSessionHas('error');

        $this->assertDatabaseHas('homework_topic_task_submissions', [
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $homeworkTask->id,
            'attempts_count' => 1,
            'accepted_at' => null,
        ]);

        $secondAttempt = $this->actingAs($student)
            ->post("http://student.palomatika.ru/homework/{$assignment->id}/tasks/{$homeworkTask->id}", [
                'answer' => '5',
                'solution_photos' => [UploadedFile::fake()->image('solution-2.jpg')],
            ]);

        $secondAttempt->assertRedirect();
        $secondAttempt->assertSessionHas('success');

        $this->assertDatabaseHas('homework_topic_task_submissions', [
            'homework_assignment_id' => $assignment->id,
            'homework_topic_task_id' => $homeworkTask->id,
            'attempts_count' => 2,
            'is_correct' => false,
        ]);
        $this->assertDatabaseHas('homework_assignments', [
            'id' => $assignment->id,
            'status' => 'completed',
            'tasks_completed' => 1,
            'tasks_correct' => 0,
        ]);
    }

    public function test_geometry_homework_renders_inline_svg(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'title' => 'Тема 15: 1 задача с фото решения',
            'topic_number' => 15,
            'tasks_count' => 1,
            'assigned_at' => now(),
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 15,
            'task_order' => 1,
            'task_payload' => [
                'id' => 1,
                'topic_id' => '15',
                'text' => 'Найдите площадь треугольника.',
                'svg' => '<svg viewBox="0 0 100 100" data-testid="geometry-svg"><polygon points="10,90 90,90 50,10"/></svg>',
                'image' => '23-1.png',
            ],
            'correct_answer' => '42',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        $this->actingAs($student)
            ->get("http://student.palomatika.ru/homework/{$assignment->id}")
            ->assertOk()
            ->assertSee('data-testid="geometry-svg"', false)
            ->assertSee('Найдите площадь треугольника.');
    }

    public function test_homework_renders_image_file_when_no_inline_svg(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $homework = Homework::create([
            'teacher_id' => $teacher->id,
            'homework_type' => 'topic_photo_practice',
            'title' => 'Тема 10: 1 задача с фото решения',
            'topic_number' => 10,
            'tasks_count' => 1,
            'assigned_at' => now(),
        ]);
        HomeworkTopicTask::create([
            'homework_id' => $homework->id,
            'topic_number' => 10,
            'task_order' => 1,
            'task_payload' => [
                'id' => 5,
                'topic_id' => '10',
                'text' => 'Решите задачу по диаграмме.',
                'image' => 'p-05-new.jpg',
            ],
            'correct_answer' => '7',
        ]);
        $assignment = HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'status' => 'assigned',
            'tasks_total' => 1,
        ]);

        $this->actingAs($student)
            ->get("http://student.palomatika.ru/homework/{$assignment->id}")
            ->assertOk()
            ->assertSee('/images/tasks/10/p-05-new.jpg', false);
    }

    public function test_assign_from_picker_links_lesson_and_custom_title(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 7, 'onboarding_completed_at' => now()]);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'source' => 'manual']);

        $lesson = \App\Models\LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'ended']);

        $pickerTasks = json_encode([[
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 3],
        ]]);

        $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'student_ids' => [$student->id],
            'picker_tasks' => $pickerTasks,
            'lesson_session_id' => $lesson->id,
            'title' => 'ДЗ по уроку 22.07',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $homework = Homework::query()->latest('id')->firstOrFail();
        $this->assertSame($lesson->id, $homework->lesson_session_id);
        $this->assertSame('ДЗ по уроку 22.07', $homework->title);
    }

    public function test_assign_notifies_telegram_student_and_marks_notified(): void
    {
        \Illuminate\Support\Facades\Http::fake(['api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200)]);
        config(['services.telegram.bot_token' => 'TESTTOKEN']);

        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $student = User::factory()->create([
            'role' => 'student', 'grade_num' => 7, 'onboarding_completed_at' => now(),
            'oauth_provider' => 'telegram', 'oauth_id' => '770001', 'telegram_chat_id' => 770001,
        ]);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'source' => 'manual']);

        $pickerTasks = json_encode([[
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 3],
        ]]);

        $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'student_ids' => [$student->id],
            'picker_tasks' => $pickerTasks,
        ])->assertRedirect()->assertSessionHasNoErrors();

        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['chat_id'] === '770001'
            && str_contains($r['text'], 'домашк'));

        $this->assertDatabaseMissing('homework_assignments', [
            'student_id' => $student->id, 'notified_at' => null,
        ]);
    }

    public function test_assign_from_picker_saves_deadline(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 7, 'onboarding_completed_at' => now()]);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'source' => 'manual']);

        $pickerTasks = json_encode([[
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 3],
        ]]);

        $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'student_ids' => [$student->id],
            'picker_tasks' => $pickerTasks,
            'deadline' => '2026-08-01',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $homework = Homework::query()->latest('id')->firstOrFail();
        $this->assertSame('2026-08-01', $homework->deadline_at?->format('Y-m-d'));
    }

    public function test_assign_from_lesson_authorizes_unlinked_participant(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        // Ученик вошёл в урок по коду, но НЕ в списке учеников учителя (нет TeacherStudent).
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 7, 'onboarding_completed_at' => now()]);

        $lesson = \App\Models\LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'ended']);
        \App\Models\LessonSessionParticipant::create([
            'lesson_session_id' => $lesson->id,
            'student_id' => $student->id,
            'source' => 'code',
        ]);

        $pickerTasks = json_encode([[
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 3],
        ]]);

        $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'student_ids' => [$student->id],
            'picker_tasks' => $pickerTasks,
            'lesson_session_id' => $lesson->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $homework = Homework::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('homework_assignments', [
            'homework_id' => $homework->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_assign_from_picker_ignores_foreign_lesson_id(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $other = User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
        $student = User::factory()->create(['role' => 'student', 'grade_num' => 7, 'onboarding_completed_at' => now()]);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'source' => 'manual']);

        $foreignLesson = \App\Models\LessonSession::create(['teacher_id' => $other->id, 'status' => 'ended']);

        $pickerTasks = json_encode([[
            'bank' => 'alg-skill',
            'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 3],
        ]]);

        $this->actingAs($teacher)->post('http://teacher.palomatika.ru/homework/assign', [
            'type' => 'topic_photo_practice',
            'student_ids' => [$student->id],
            'picker_tasks' => $pickerTasks,
            'lesson_session_id' => $foreignLesson->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $homework = Homework::query()->latest('id')->firstOrFail();
        // Чужой урок не привязывается (мягко игнорируем, ДЗ всё равно создаётся).
        $this->assertNull($homework->lesson_session_id);
    }
}
