<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\StudentNote;
use App\Models\User;
use App\Services\LessonSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Просмотр истории заметок об ученике прямо на странице урока.
 */
class LessonStudentNotesTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://teacher.palomatika.ru';

    private function teacher(): User
    {
        return User::factory()->create(['role' => 'teacher', 'onboarding_completed_at' => now()]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'onboarding_completed_at' => now()]);
    }

    /** Живой урок с одной задачей (без неё старт запрещён) и вошедшим учеником. */
    private function liveSessionWith(User $teacher, User $student): LessonSession
    {
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher, now());
        $svc->addTask($session, 'alg-skill', [
            'grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1,
        ]);
        $svc->start($session);
        $session->refresh();
        $svc->joinByCode($session->join_code, $student);

        return $session->refresh();
    }

    private function note(User $teacher, User $student, string $body, array $attributes = []): StudentNote
    {
        return StudentNote::create(array_merge([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'kind'       => 'weakness',
            'source'     => 'chat',
            'body'       => $body,
        ], $attributes));
    }

    public function test_returns_notes_newest_first_with_current_lesson_flag(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $session = $this->liveSessionWith($teacher, $student);

        $this->note($teacher, $student, 'Старое: путает подобие', ['created_at' => now()->subDays(3)]);
        $this->note($teacher, $student, 'Сегодня: не понимает трапецию', [
            'lesson_session_id' => $session->id,
            'topic_tag' => '23',
        ]);

        $resp = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/students/{$student->id}/notes")
            ->assertOk();

        $notes = $resp->json('notes');
        $this->assertCount(2, $notes);
        $this->assertSame('Сегодня: не понимает трапецию', $notes[0]['body']);
        $this->assertTrue($notes[0]['is_current_lesson']);
        $this->assertSame('23', $notes[0]['topic_tag']);
        $this->assertFalse($notes[1]['is_current_lesson']);
        $this->assertSame($student->name, $resp->json('student.name'));
    }

    public function test_other_teachers_notes_are_not_exposed(): void
    {
        $teacher = $this->teacher();
        $colleague = $this->teacher();
        $student = $this->student();
        $session = $this->liveSessionWith($teacher, $student);

        $this->note($teacher, $student, 'Моя заметка');
        $this->note($colleague, $student, 'Заметка коллеги');

        $notes = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/students/{$student->id}/notes")
            ->assertOk()
            ->json('notes');

        $this->assertCount(1, $notes);
        $this->assertSame('Моя заметка', $notes[0]['body']);
    }

    public function test_foreign_lesson_is_forbidden(): void
    {
        $teacher = $this->teacher();
        $stranger = $this->teacher();
        $student = $this->student();
        $session = $this->liveSessionWith($teacher, $student);

        $this->actingAs($stranger)
            ->getJson(self::BASE . "/lessons/{$session->id}/students/{$student->id}/notes")
            ->assertForbidden();
    }

    public function test_non_participant_is_rejected(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $outsider = $this->student();
        $session = $this->liveSessionWith($teacher, $student);

        $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/students/{$outsider->id}/notes")
            ->assertStatus(422);
    }

    public function test_state_carries_notes_count_per_participant(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $session = $this->liveSessionWith($teacher, $student);

        $this->note($teacher, $student, 'Раз');
        $this->note($teacher, $student, 'Два');

        $participants = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertOk()
            ->json('participants');

        $this->assertSame(2, $participants[0]['notes_count']);
    }

    public function test_state_counts_only_own_notes(): void
    {
        $teacher = $this->teacher();
        $colleague = $this->teacher();
        $student = $this->student();
        $session = $this->liveSessionWith($teacher, $student);

        $this->note($colleague, $student, 'Чужая');

        $participants = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertOk()
            ->json('participants');

        $this->assertSame(0, $participants[0]['notes_count']);
    }
}
