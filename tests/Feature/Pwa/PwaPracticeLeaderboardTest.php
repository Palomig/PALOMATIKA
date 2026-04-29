<?php

namespace Tests\Feature\Pwa;

use App\Models\PracticeGameRun;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\PracticeLeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaPracticeLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function makeStudent(array $attrs = []): User
    {
        self::$seq++;
        return User::factory()->create(array_merge([
            'role' => 'student',
            'grade_num' => 9,
            'grade_letter' => 'А',
            'school_number' => '7',
            'city' => 'Чехов',
            'onboarding_completed_at' => now(),
            'oauth_provider' => 'vk',
            'oauth_id' => 'lb-test-' . self::$seq,
        ], $attrs));
    }

    private function logRun(User $user, string $slug, int $score, ?\Carbon\Carbon $endedAt = null): PracticeGameRun
    {
        return PracticeGameRun::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'score' => $score,
            'started_at' => ($endedAt ?? now())->copy()->subMinute(),
            'ended_at' => $endedAt ?? now(),
            'end_reason' => PracticeGameRun::REASON_WRONG,
        ]);
    }

    public function test_class_scope_only_includes_classmates(): void
    {
        $viewer = $this->makeStudent(['name' => 'Иван Петров']);
        $classmate = $this->makeStudent(['name' => 'Аня Сидорова']);
        $otherClass = $this->makeStudent(['name' => 'Маша Иванова', 'grade_letter' => 'Б']);
        $otherSchool = $this->makeStudent(['name' => 'Петя Кузнецов', 'school_number' => '12']);

        $this->logRun($viewer, 'equations', 5);
        $this->logRun($classmate, 'equations', 8);
        $this->logRun($otherClass, 'equations', 99);
        $this->logRun($otherSchool, 'equations', 99);

        $service = app(PracticeLeaderboardService::class);
        $board = $service->topRuns('equations', 'class', 'all_time', $viewer);

        $this->assertTrue($board['available']);
        $this->assertCount(2, $board['entries']);
        $names = array_column($board['entries'], 'name');
        $this->assertContains('Аня С.', $names);
        $this->assertContains('Иван П.', $names);
        $this->assertNotContains('Маша И.', $names);
        $this->assertNotContains('Петя К.', $names);
        $this->assertSame('Аня С.', $board['entries'][0]['name']);
        $this->assertSame(8, $board['entries'][0]['score']);
    }

    public function test_school_scope_includes_other_classes_same_school(): void
    {
        $viewer = $this->makeStudent();
        $sameSchool = $this->makeStudent(['grade_letter' => 'Б']);
        $otherSchool = $this->makeStudent(['school_number' => '12']);

        $this->logRun($viewer, 'equations', 1);
        $this->logRun($sameSchool, 'equations', 4);
        $this->logRun($otherSchool, 'equations', 99);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'school', 'all_time', $viewer);

        $this->assertTrue($board['available']);
        $this->assertCount(2, $board['entries']);
    }

    public function test_all_scope_includes_every_student(): void
    {
        $viewer = $this->makeStudent();
        $otherSchool = $this->makeStudent(['school_number' => '12']);
        $teacher = User::factory()->create(['role' => 'teacher', 'oauth_provider' => 'vk', 'oauth_id' => 'teacher-x']);

        $this->logRun($viewer, 'equations', 1);
        $this->logRun($otherSchool, 'equations', 4);
        $this->logRun($teacher, 'equations', 99);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'all', 'all_time', $viewer);

        $this->assertTrue($board['available']);
        $this->assertCount(2, $board['entries'], 'teachers should be excluded');
        $this->assertSame(4, $board['entries'][0]['score']);
    }

    public function test_week_period_filters_out_old_runs(): void
    {
        $viewer = $this->makeStudent();
        $classmate = $this->makeStudent(['grade_letter' => 'А']);

        $this->logRun($viewer, 'equations', 3, now()->copy());
        $this->logRun($classmate, 'equations', 99, now()->copy()->subWeeks(2));

        $weekBoard = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'class', 'week', $viewer);
        $allTimeBoard = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'class', 'all_time', $viewer);

        $this->assertCount(1, $weekBoard['entries']);
        $this->assertCount(2, $allTimeBoard['entries']);
        $this->assertSame(3, $weekBoard['entries'][0]['score']);
    }

    public function test_class_scope_unavailable_when_viewer_has_no_class(): void
    {
        $viewer = $this->makeStudent(['grade_letter' => null]);
        $this->logRun($viewer, 'equations', 5);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'class', 'all_time', $viewer);

        $this->assertFalse($board['available']);
        $this->assertSame([], $board['entries']);
    }

    public function test_open_runs_and_zero_scores_are_excluded(): void
    {
        $viewer = $this->makeStudent();
        $other = $this->makeStudent(['grade_letter' => 'А']);

        PracticeGameRun::create([
            'user_id' => $other->id,
            'slug' => 'equations',
            'score' => 50,
            'started_at' => now(),
            'ended_at' => null,
        ]);
        $this->logRun($viewer, 'equations', 0);
        $this->logRun($other, 'equations', 7);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'class', 'all_time', $viewer);

        $this->assertCount(1, $board['entries']);
        $this->assertSame(7, $board['entries'][0]['score']);
    }

    public function test_group_scope_for_teacher_filters_to_linked_students(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'oauth_provider' => 'vk',
            'oauth_id' => 'lb-teacher-' . self::$seq++,
        ]);
        $linked1 = $this->makeStudent(['name' => 'Линк Один', 'school_number' => '1']);
        $linked2 = $this->makeStudent(['name' => 'Линк Два', 'school_number' => '2']);
        $unlinked = $this->makeStudent(['name' => 'Чужой Ученик', 'school_number' => '3']);

        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $linked1->id, 'source' => 'manual']);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $linked2->id, 'source' => 'manual']);

        $this->logRun($linked1, 'equations', 8);
        $this->logRun($linked2, 'equations', 5);
        $this->logRun($unlinked, 'equations', 99);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'group', 'all_time', $teacher);

        $this->assertTrue($board['available']);
        $this->assertCount(2, $board['entries']);
        $names = array_column($board['entries'], 'name');
        $this->assertContains('Линк О.', $names);
        $this->assertContains('Линк Д.', $names);
        $this->assertNotContains('Чужой У.', $names);
    }

    public function test_group_scope_for_student_includes_classmates_under_same_teacher(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'oauth_provider' => 'vk',
            'oauth_id' => 'lb-teacher-' . self::$seq++,
        ]);
        $viewer = $this->makeStudent(['name' => 'Я Сам', 'school_number' => '1']);
        $peer = $this->makeStudent(['name' => 'Сосед По Группе', 'school_number' => '2']);
        $stranger = $this->makeStudent(['name' => 'Чужой Ученик', 'school_number' => '3']);

        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $viewer->id, 'source' => 'manual']);
        TeacherStudent::create(['teacher_id' => $teacher->id, 'student_id' => $peer->id, 'source' => 'manual']);

        $this->logRun($viewer, 'equations', 4);
        $this->logRun($peer, 'equations', 9);
        $this->logRun($stranger, 'equations', 99);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'group', 'all_time', $viewer);

        $this->assertTrue($board['available']);
        $this->assertCount(2, $board['entries']);
        $this->assertSame('Сосед П.', $board['entries'][0]['name']);
    }

    public function test_group_scope_unavailable_without_link(): void
    {
        $viewer = $this->makeStudent();
        $this->logRun($viewer, 'equations', 5);

        $service = app(PracticeLeaderboardService::class);

        $this->assertFalse($service->availableScopes($viewer)['group']);

        $board = $service->topRuns('equations', 'group', 'all_time', $viewer);
        $this->assertFalse($board['available']);
    }

    public function test_admin_default_scope_is_all_and_school_class_disabled(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Админ',
            'oauth_provider' => 'vk',
            'oauth_id' => 'lb-admin-' . self::$seq++,
            'onboarding_completed_at' => now(),
            'school_number' => '1',
            'grade_num' => 9,
            'grade_letter' => 'Д',
            'city' => 'Чехов',
        ]);
        $student = $this->makeStudent(['name' => 'Один Ученик', 'school_number' => '8', 'grade_letter' => 'Д']);
        $this->logRun($student, 'equations', 12);

        $this->actingAs($admin)
            ->get('http://student.palomatika.ru/practice/mini-games/equations')
            ->assertOk()
            ->assertSee('все ученики')
            ->assertSee('Один У.')
            ->assertDontSee('Здесь пока пусто');
    }

    public function test_only_best_score_per_user_appears(): void
    {
        $viewer = $this->makeStudent();
        $this->logRun($viewer, 'equations', 3);
        $this->logRun($viewer, 'equations', 7);
        $this->logRun($viewer, 'equations', 5);

        $board = app(PracticeLeaderboardService::class)
            ->topRuns('equations', 'class', 'all_time', $viewer);

        $this->assertCount(1, $board['entries']);
        $this->assertSame(7, $board['entries'][0]['score']);
    }

    public function test_leaderboard_page_renders_with_tabs(): void
    {
        $viewer = $this->makeStudent();
        $classmate = $this->makeStudent(['grade_letter' => 'А']);
        $this->logRun($classmate, 'equations', 4);

        $this->actingAs($viewer)
            ->get('http://student.palomatika.ru/practice/mini-games/equations/leaderboard?scope=class&period=all_time')
            ->assertOk()
            ->assertSee('Лидерборд')
            ->assertSee('Класс')
            ->assertSee('Школа')
            ->assertSee('Все')
            ->assertSee('Группа')
            ->assertSee('За всё время')
            ->assertSee('На этой неделе');
    }

    public function test_intro_screen_defaults_to_all_scope(): void
    {
        $viewer = $this->makeStudent(['name' => 'Иван Петров']);
        $classmate = $this->makeStudent(['name' => 'Аня Сидорова']);
        $this->logRun($classmate, 'equations', 7);

        $this->actingAs($viewer)
            ->get('http://student.palomatika.ru/practice/mini-games/equations')
            ->assertOk()
            ->assertSee('🏆 Лидерборд', false)
            ->assertSee('все ученики')
            ->assertSee('Аня С.')
            ->assertDontSee('Что будет после игры');
    }

    public function test_intro_screen_class_scope_via_query_param(): void
    {
        $viewer = $this->makeStudent(['name' => 'Иван Петров']);
        $classmate = $this->makeStudent(['name' => 'Аня Сидорова']);
        $otherClass = $this->makeStudent(['name' => 'Маша Иванова', 'grade_letter' => 'Б']);
        $this->logRun($classmate, 'equations', 7);
        $this->logRun($otherClass, 'equations', 99);

        $this->actingAs($viewer)
            ->get('http://student.palomatika.ru/practice/mini-games/equations?scope=class')
            ->assertOk()
            ->assertSee('твой класс')
            ->assertSee('Аня С.')
            ->assertDontSee('Маша И.');
    }

    public function test_intro_screen_falls_back_to_all_scope_for_user_without_class(): void
    {
        $viewer = $this->makeStudent(['grade_letter' => null, 'school_number' => null]);
        $other = $this->makeStudent(['grade_letter' => 'Б']);
        $this->logRun($other, 'equations', 3);

        $this->actingAs($viewer)
            ->get('http://student.palomatika.ru/practice/mini-games/equations?scope=class')
            ->assertOk()
            ->assertSee('все ученики')
            ->assertDontSee('твой класс');
    }

    public function test_intro_screen_does_not_show_topbar_leaderboard_button(): void
    {
        $viewer = $this->makeStudent();

        $response = $this->actingAs($viewer)
            ->get('http://student.palomatika.ru/practice/mini-games/equations')
            ->assertOk();

        $body = $response->getContent();
        $this->assertStringNotContainsString('🏆 Лидерборд</a>', $body, 'topbar pill should be removed');
    }
}
