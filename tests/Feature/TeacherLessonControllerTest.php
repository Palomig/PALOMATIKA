<?php

namespace Tests\Feature;

use App\Models\LessonSchedule;
use App\Models\LessonSession;
use App\Models\LessonSessionTask;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherLessonControllerTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://teacher.palomatika.ru';

    private function teacher(): User
    {
        return User::create([
            'name' => 'T', 'email' => 't+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'teacher',
        ]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'S', 'email' => 's+' . uniqid() . '@t.t', 'password' => 'x', 'role' => 'student',
            'onboarding_completed_at' => now(), // для middleware pwa.onboarding
        ]);
    }

    public function test_create_adhoc_session_returns_join_code(): void
    {
        $resp = $this->actingAs($this->teacher())
            ->postJson(self::BASE . '/lessons');

        $resp->assertCreated()
            ->assertJsonStructure(['session' => ['id', 'status', 'join_code']]);

        $this->assertMatchesRegularExpression('/^\d{4}$/', $resp->json('session.join_code'));
        $this->assertSame('draft', $resp->json('session.status'));
    }

    public function test_create_session_from_schedule_creates_no_participants(): void
    {
        // Автоучастники упразднены — ученики входят сами по коду.
        $teacher = $this->teacher();
        $student = $this->student();
        $slot = LessonSchedule::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'day_of_week' => (int) now()->format('N'),
            'start_time' => '10:00', 'end_time' => '11:00', 'is_active' => true,
        ]);

        $resp = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertCreated();

        $sessionId = $resp->json('session.id');
        $this->assertDatabaseMissing('lesson_session_participants', [
            'lesson_session_id' => $sessionId,
        ]);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $resp->json('session.join_code'));
    }

    public function test_lessons_page_renders_for_teacher(): void
    {
        // В testing fetchEvriumSchedule() возвращает [], поэтому проверяем
        // только что страница компилируется и отдаёт каркас (кнопка + пустое состояние).
        $resp = $this->actingAs($this->teacher())
            ->get(self::BASE . '/lessons')
            ->assertOk();

        $html = $resp->getContent();
        $this->assertStringContainsString('Начать новый урок', $html);
        $this->assertStringContainsString('lessonsBoard', $html);
    }

    public function test_from_slot_creates_draft_without_participants(): void
    {
        // Автоучастники упразднены — ученики входят сами по коду.
        $teacher = $this->teacher();
        $student = $this->student();
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $student->id, 'evrium_name' => 'Вася']);

        $resp = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons/from-slot', [
                'starts_at'   => '2026-05-29 14:00:00',
                'ends_at'     => '2026-05-29 15:00:00',
                'student_ids' => [$student->id],
            ])
            ->assertCreated();

        $sessionId = $resp->json('session.id');
        $this->assertSame('draft', $resp->json('session.status'));
        $this->assertDatabaseHas('lesson_sessions', [
            'id' => $sessionId, 'teacher_id' => $teacher->id, 'starts_at' => '2026-05-29 14:00:00',
        ]);
        $this->assertDatabaseMissing('lesson_session_participants', [
            'lesson_session_id' => $sessionId,
        ]);
    }

    public function test_from_slot_is_idempotent_per_start_time(): void
    {
        $teacher = $this->teacher();

        $first = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons/from-slot', ['starts_at' => '2026-05-29 14:00:00'])
            ->assertCreated()->json('session.id');

        $second = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons/from-slot', ['starts_at' => '2026-05-29 14:00:00'])
            ->assertCreated()->json('session.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, LessonSession::where('teacher_id', $teacher->id)->count());
    }

    public function test_from_slot_requires_starts_at(): void
    {
        $this->actingAs($this->teacher())
            ->postJson(self::BASE . '/lessons/from-slot', [])
            ->assertStatus(422);
    }

    public function test_other_teacher_cannot_use_others_slot(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $slot = LessonSchedule::create([
            'teacher_id' => $owner->id, 'student_id' => $this->student()->id,
            'day_of_week' => 1, 'start_time' => '10:00', 'is_active' => true,
        ]);

        $this->actingAs($intruder)
            ->postJson(self::BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertForbidden();
    }

    public function test_full_flow_create_add_start_state_end(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $slot = LessonSchedule::create([
            'teacher_id' => $teacher->id, 'student_id' => $student->id,
            'day_of_week' => (int) now()->format('N'),
            'start_time' => '10:00', 'is_active' => true,
        ]);

        $sessionId = $this->actingAs($teacher)
            ->postJson(self::BASE . '/lessons', ['schedule_id' => $slot->id])
            ->assertCreated()->json('session.id');

        $taskResp = $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$sessionId}/tasks", [
                'bank' => 'alg-skill',
                'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1],
            ])->assertCreated();
        $this->assertSame('-2', $taskResp->json('task.correct_answer'));
        $this->assertSame('-8 + 6', $taskResp->json('task.task_payload.expression'));

        $startResp = $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$sessionId}/start")
            ->assertOk()
            ->assertJsonPath('session.status', 'live');

        // Ученик входит по коду (автоучастников из расписания больше нет)
        $this->actingAs($student)
            ->postJson('http://student.palomatika.ru/lessons/join', [
                'code' => $startResp->json('session.join_code'),
            ])->assertOk();

        $state = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$sessionId}/state")
            ->assertOk();
        $this->assertSame('live', $state->json('session.status'));
        $this->assertCount(1, $state->json('tasks'));
        $this->assertCount(1, $state->json('participants'));
        $this->assertSame([], $state->json('grid'));

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$sessionId}/end")
            ->assertOk()
            ->assertJsonPath('session.status', 'ended');
    }

    public function test_state_returns_404_for_unknown_session(): void
    {
        $this->actingAs($this->teacher())
            ->getJson(self::BASE . '/lessons/9999/state')
            ->assertNotFound();
    }

    public function test_other_teacher_cannot_read_state(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $owner->id, 'status' => 'draft']);

        $this->actingAs($intruder)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertForbidden();
    }

    public function test_start_fails_with_no_tasks(): void
    {
        $teacher = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft']);

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/start")
            ->assertStatus(422);
    }

    public function test_prep_view_renders_for_owner(): void
    {
        $teacher = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft', 'join_code' => '1234']);

        $resp = $this->actingAs($teacher)
            ->get(self::BASE . "/lessons/{$session->id}")
            ->assertOk();

        $html = $resp->getContent();
        $this->assertStringContainsString('Урок #' . $session->id, $html);
        $this->assertStringContainsString('lessonPrep', $html, 'Alpine wrapper present');
        // Бандл навыков больше не инлайнится — picker грузит опции через
        // GET /lessons/picker-options; проверяем сам компонент picker'а.
        $this->assertStringContainsString('taskPicker(', $html, 'task picker component present');
    }

    public function test_prep_view_forbidden_for_other_teacher(): void
    {
        $owner = $this->teacher();
        $intruder = $this->teacher();
        $session = LessonSession::create(['teacher_id' => $owner->id, 'status' => 'draft']);

        $this->actingAs($intruder)
            ->get(self::BASE . "/lessons/{$session->id}")
            ->assertForbidden();
    }

    public function test_picker_options_returns_sections_and_filters_topics(): void
    {
        $teacher = $this->teacher();

        $r = $this->actingAs($teacher)
            ->getJson(self::BASE . '/lessons/picker-options?bank=oge');
        $r->assertOk()->assertJsonPath('sections.0.id', 'part1');

        $r2 = $this->actingAs($teacher)
            ->getJson(self::BASE . '/lessons/picker-options?bank=oge&section=part2');
        $ids = array_column($r2->json('topics'), 'id');
        $this->assertSame(['20', '21', '23', '24', '25'], $ids);
    }

    public function test_picker_options_rejects_unknown_section(): void
    {
        $this->actingAs($this->teacher())
            ->getJson(self::BASE . '/lessons/picker-options?bank=oge&section=part9')
            ->assertStatus(422)
            ->assertJsonPath('error', 'Unknown section');
    }

    public function test_remove_task_from_another_session_404(): void
    {
        $teacher = $this->teacher();
        $sessionA = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft']);
        $sessionB = LessonSession::create(['teacher_id' => $teacher->id, 'status' => 'draft']);
        $taskInB = LessonSessionTask::create([
            'lesson_session_id' => $sessionB->id, 'position' => 1, 'bank' => 'alg-skill',
            'task_ref' => '{}', 'task_payload' => [], 'correct_answer' => '0',
        ]);

        $this->actingAs($teacher)
            ->deleteJson(self::BASE . "/lessons/{$sessionA->id}/tasks/{$taskInB->id}")
            ->assertNotFound();
    }

    // --- планируемое время, «следующий урок», заметка ---

    public function test_create_adhoc_with_starts_at(): void
    {
        $at = now()->addDay()->setTime(16, 0)->setSeconds(0);

        $resp = $this->actingAs($this->teacher())
            ->postJson(self::BASE . '/lessons', ['starts_at' => $at->format('Y-m-d H:i')])
            ->assertCreated();

        $session = LessonSession::findOrFail($resp->json('session.id'));
        $this->assertTrue($session->starts_at->equalTo($at->copy()->setSeconds(0)));
    }

    public function test_create_adhoc_rejects_invalid_starts_at(): void
    {
        $this->actingAs($this->teacher())
            ->postJson(self::BASE . '/lessons', ['starts_at' => 'не дата'])
            ->assertStatus(422);
    }

    public function test_next_creates_follow_up_week_later(): void
    {
        $teacher = $this->teacher();
        $at = now()->setTime(15, 0)->setSeconds(0)->setMicroseconds(0);
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher, $at);

        $resp = $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/next")
            ->assertCreated();

        $next = LessonSession::findOrFail($resp->json('session.id'));
        $this->assertTrue($next->starts_at->equalTo($at->copy()->addWeek()));
        $this->assertSame('draft', $next->status);

        // Чужой урок — 403.
        $this->actingAs($this->teacher())
            ->postJson(self::BASE . "/lessons/{$session->id}/next")
            ->assertForbidden();
    }

    public function test_note_saved_and_returned_in_state(): void
    {
        $teacher = $this->teacher();
        $session = app(\App\Services\LessonSessionService::class)->createAdhoc($teacher);

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/note", ['note' => 'спросить про контрольную'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertOk()
            ->assertJsonPath('session.note', 'спросить про контрольную');

        // Пустая заметка стирается в null.
        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/note", ['note' => '  '])
            ->assertOk();
        $this->assertNull($session->fresh()->note);
    }

    public function test_add_personal_task_for_participant(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->joinByCode($session->join_code, $student);

        $resp = $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/tasks", [
                'bank' => 'alg-skill',
                'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1],
                'assigned_student_id' => $student->id,
            ])
            ->assertCreated();

        $this->assertSame($student->id, $resp->json('task.assigned_student_id'));
        $this->assertDatabaseHas('lesson_session_tasks', [
            'lesson_session_id'   => $session->id,
            'assigned_student_id' => $student->id,
        ]);
    }

    public function test_add_personal_task_rejects_non_participant(): void
    {
        $teacher = $this->teacher();
        $stranger = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/tasks", [
                'bank' => 'alg-skill',
                'refs' => ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1],
                'assigned_student_id' => $stranger->id,
            ])
            ->assertStatus(422);
    }

    public function test_state_task_includes_assigned_name(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->joinByCode($session->join_code, $student);
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1], $student->id);

        $task = collect($this->actingAs($teacher)->getJson(self::BASE . "/lessons/{$session->id}/state")->json('tasks'))->first();
        $this->assertSame($student->id, $task['assigned_student_id']);
        $this->assertSame($student->name, $task['assigned_name']);
    }

    public function test_state_participants_include_activity(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);
        $svc->joinByCode($session->fresh()->join_code, $student);
        $svc->recordActivity($session, $student, true);

        $resp = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertOk();

        $p = collect($resp->json('participants'))->firstWhere('id', $student->id);
        $this->assertSame('present', $p['activity']['state']);
        $this->assertArrayHasKey('away_count', $p['activity']);
        $this->assertArrayHasKey('away_seconds', $p['activity']);
        $this->assertArrayHasKey('present_seconds', $p['activity']);
    }

    public function test_lessons_page_shows_adhoc_session_with_note(): void
    {
        $teacher = $this->teacher();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher, now()->addDay()->setTime(17, 45));
        $svc->updateNote($session, 'Повторить теорему Виета');

        $this->actingAs($teacher)
            ->get(self::BASE . '/lessons')
            ->assertOk()
            ->assertSee('17:45')
            ->assertSee('внеплановый урок')
            ->assertSee('Повторить теорему Виета');
    }

    // --- release (Task C4) ---

    /** Live-сессия с задачей + ученик вошёл по коду (лок активен). */
    private function makeLockedLesson(User $teacher, User $student): LessonSession
    {
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);
        $svc->start($session);
        $svc->joinByCode($session->fresh()->join_code, $student);
        return $session->fresh();
    }

    public function test_release_unlocks_participant(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $session = $this->makeLockedLesson($teacher, $student);

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/participants/{$student->id}/release")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $p = $session->participants()->where('student_id', $student->id)->first();
        $this->assertNotNull($p->released_at);
        $this->assertSame($teacher->id, $p->released_by);
    }

    public function test_release_foreign_session_403(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $session = $this->makeLockedLesson($teacher, $student);

        $this->actingAs($this->teacher())
            ->postJson(self::BASE . "/lessons/{$session->id}/participants/{$student->id}/release")
            ->assertForbidden();
    }

    public function test_release_unknown_participant_404(): void
    {
        $teacher = $this->teacher();
        $session = $this->makeLockedLesson($teacher, $this->student());

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/participants/999999/release")
            ->assertNotFound();
    }

    public function test_state_participants_include_locked_flag(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $session = $this->makeLockedLesson($teacher, $student);

        $resp = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/state")
            ->assertOk();

        $participant = collect($resp->json('participants'))->firstWhere('id', $student->id);
        $this->assertTrue($participant['locked']);

        app(\App\Services\LessonSessionService::class)->release($session, $student->id, $teacher);

        $resp = $this->actingAs($teacher)
            ->getJson(self::BASE . "/lessons/{$session->id}/state");
        $participant = collect($resp->json('participants'))->firstWhere('id', $student->id);
        $this->assertFalse($participant['locked']);
    }

    // --- «не понимает» (Task A3) ---

    public function test_dont_understand_records_note_for_participant(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $svc->joinByCode($session->join_code, $student);
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);

        $resp = $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/dont-understand", [
                'student_id' => $student->id,
                'task_id'    => $task->id,
            ])
            ->assertCreated();

        $this->assertSame('weakness', $resp->json('note.kind'));
        $this->assertSame('lesson_button', $resp->json('note.source'));
        $this->assertDatabaseHas('student_notes', [
            'student_id'        => $student->id,
            'teacher_id'        => $teacher->id,
            'lesson_session_id' => $session->id,
            'kind'              => 'weakness',
            'source'            => 'lesson_button',
            'topic_tag'         => 'signed-add',
        ]);
    }

    public function test_dont_understand_rejects_non_participant(): void
    {
        $teacher = $this->teacher();
        $stranger = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($teacher);
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);

        $this->actingAs($teacher)
            ->postJson(self::BASE . "/lessons/{$session->id}/dont-understand", [
                'student_id' => $stranger->id,
                'task_id'    => $task->id,
            ])
            ->assertStatus(422);
    }

    public function test_dont_understand_forbidden_for_other_teacher(): void
    {
        $owner = $this->teacher();
        $student = $this->student();
        $svc = app(\App\Services\LessonSessionService::class);
        $session = $svc->createAdhoc($owner);
        $svc->joinByCode($session->join_code, $student);
        $task = $svc->addTask($session, 'alg-skill', ['grade' => 7, 'skill_slug' => 'signed-add', 'level_id' => 'simple', 'task_id' => 1]);

        $this->actingAs($this->teacher())
            ->postJson(self::BASE . "/lessons/{$session->id}/dont-understand", [
                'student_id' => $student->id,
                'task_id'    => $task->id,
            ])
            ->assertForbidden();
    }
}
