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
            ->assertSessionHasErrors('solution_photo');

        $firstAttempt = $this->actingAs($student)
            ->post("http://student.palomatika.ru/homework/{$assignment->id}/tasks/{$homeworkTask->id}", [
                'answer' => '3',
                'solution_photo' => UploadedFile::fake()->image('solution-1.jpg'),
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
                'solution_photo' => UploadedFile::fake()->image('solution-2.jpg'),
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
}
