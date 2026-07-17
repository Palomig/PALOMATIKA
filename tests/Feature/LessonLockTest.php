<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\User;
use App\Services\LessonSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Middleware EnforceLessonLock: ученик с активным локом урока не может
 * пользоваться другими страницами student PWA — редирект на страницу урока
 * (JSON — 423). Страница урока и её API доступны.
 */
class LessonLockTest extends TestCase
{
    use RefreshDatabase;

    private const STUDENT_BASE = 'http://student.palomatika.ru';

    private function teacher(): User
    {
        return User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
    }

    /** Live-сессия с одной задачей + вход ученика по коду (ставит лок на 60 минут). */
    private function makeLockedSession(User $teacher, User $student): array
    {
        $svc = app(LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $task = $svc->addTask($session, 'alg-skill', [
            'grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1,
        ]);
        $svc->start($session);
        $session->refresh();
        $svc->joinByCode($session->join_code, $student);
        return [$session, $task];
    }

    public function test_locked_student_is_redirected_from_dashboard_to_lesson(): void
    {
        $student = $this->student();
        [$session] = $this->makeLockedSession($this->teacher(), $student);

        $this->actingAs($student)
            ->get(self::STUDENT_BASE . '/')
            ->assertRedirectContains("/lessons/{$session->id}");
    }

    public function test_locked_student_json_request_gets_423(): void
    {
        $student = $this->student();
        [$session] = $this->makeLockedSession($this->teacher(), $student);

        $this->actingAs($student)
            ->getJson(self::STUDENT_BASE . '/')
            ->assertStatus(423)
            ->assertJson(['error' => 'lesson_lock', 'lesson_id' => $session->id]);
    }

    public function test_locked_student_can_use_lesson_page_and_its_api(): void
    {
        $student = $this->student();
        [$session, $task] = $this->makeLockedSession($this->teacher(), $student);

        $this->actingAs($student)
            ->get(self::STUDENT_BASE . "/lessons/{$session->id}")
            ->assertOk();

        $this->actingAs($student)
            ->getJson(self::STUDENT_BASE . "/lessons/{$session->id}/state")
            ->assertOk();

        $this->actingAs($student)
            ->postJson(self::STUDENT_BASE . "/lessons/{$session->id}/answer", [
                'task_id' => $task->id, 'answer' => '-2',
            ])->assertOk();
    }

    public function test_dashboard_available_again_after_release(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        [$session] = $this->makeLockedSession($teacher, $student);

        app(LessonSessionService::class)->release($session, $student->id, $teacher);

        $this->actingAs($student)
            ->get(self::STUDENT_BASE . '/')
            ->assertOk();
    }

    public function test_dashboard_available_again_after_session_end(): void
    {
        $student = $this->student();
        [$session] = $this->makeLockedSession($this->teacher(), $student);

        app(LessonSessionService::class)->end($session);

        $this->actingAs($student)
            ->get(self::STUDENT_BASE . '/')
            ->assertOk();
    }

    public function test_dashboard_available_again_after_lock_expires(): void
    {
        $student = $this->student();
        $this->makeLockedSession($this->teacher(), $student);

        $this->travel(61)->minutes();

        $this->actingAs($student)
            ->get(self::STUDENT_BASE . '/')
            ->assertOk();
    }

    public function test_teacher_is_not_affected_by_lock_middleware(): void
    {
        // Учитель — не student: middleware пропускает без запроса лока.
        $teacher = $this->teacher();

        $this->actingAs($teacher)
            ->get(self::STUDENT_BASE . '/')
            ->assertOk();
    }

    public function test_guest_is_not_affected_by_lock_middleware(): void
    {
        // Гость режется auth-middleware (redirect на login), лок не участвует.
        $this->get(self::STUDENT_BASE . '/')
            ->assertRedirect();
    }
}
