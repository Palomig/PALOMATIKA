<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptAnswer;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\OgeVariantBuilderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class TeacherStudentsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropAllTables();
        $this->createMinimalSchema();
    }

    private function createMinimalSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 32)->default('student');
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 32)->default('manual');
            $table->string('student_alias', 80)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['teacher_id', 'student_id']);
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('source', 32)->nullable();
            $table->string('external_ref')->nullable();
            $table->string('created_via')->nullable();
            $table->text('config_json')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('oge_variants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('active');
            $table->text('device_meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('oge_attempts')->cascadeOnDelete();
            $table->unsignedTinyInteger('task_number');
            $table->string('current_answer')->nullable();
            $table->unsignedInteger('commits_count')->default(0);
            $table->timestamp('first_committed_at')->nullable();
            $table->timestamp('last_committed_at')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
            $table->unique(['attempt_id', 'task_number']);
        });

        Schema::create('oge_attempt_scorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('oge_attempts')->cascadeOnDelete();
            $table->unsignedTinyInteger('task_number');
            $table->boolean('is_correct')->nullable();
            $table->string('correct_answer')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['attempt_id', 'task_number']);
        });
    }

    public function test_all_students_are_visible_by_default_and_link_state_is_preserved(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);

        $visibleStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Visible Student',
            'email' => 'visible-student@example.com',
            'last_active_at' => now()->subHour(),
        ]);

        $otherTeacherStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Other Teacher Student',
            'email' => 'other-teacher@example.com',
        ]);

        $unlinkedStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Unlinked Student',
            'email' => 'unlinked@example.com',
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $visibleStudent->id,
            'source' => 'manual',
        ]);

        TeacherStudent::create([
            'teacher_id' => $otherTeacher->id,
            'student_id' => $otherTeacherStudent->id,
            'source' => 'manual',
        ]);

        $variant = OgeVariant::query()->create([
            'hash' => Str::random(16),
            'owner_teacher_id' => $teacher->id,
            'title' => 'Test Variant',
        ]);

        $attempt = OgeAttempt::query()->create([
            'variant_id' => $variant->id,
            'student_id' => $visibleStudent->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinutes(20),
            'last_seen_at' => now()->subMinutes(10),
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $attempt->id,
            'task_number' => 1,
            'is_correct' => true,
            'checked_at' => now()->subMinutes(9),
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $attempt->id,
            'task_number' => 2,
            'is_correct' => false,
            'checked_at' => now()->subMinutes(8),
        ]);

        $response = $this->actingAs($teacher)->get('/teacher/students');

        $response->assertOk();
        $response->assertSee('Visible Student');
        $response->assertSee('visible-student@example.com');
        $response->assertSee('50%');
        $response->assertSee('Other Teacher Student');
        $response->assertSee('Unlinked Student');
        $response->assertSee('привязан');
        $response->assertSee('не привязан');

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            sprintf('/data-roster-student-id="%d"[^>]*data-linked-state="linked"/', $visibleStudent->id),
            $content
        );
        $this->assertMatchesRegularExpression(
            sprintf('/data-roster-student-id="%d"[^>]*data-linked-state="unlinked"/', $otherTeacherStudent->id),
            $content
        );
        $this->assertMatchesRegularExpression(
            sprintf('/data-roster-student-id="%d"[^>]*data-linked-state="unlinked"/', $unlinkedStudent->id),
            $content
        );
    }

    public function test_linked_only_filter_shows_only_students_linked_to_current_teacher(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);

        $linkedStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Linked Student',
            'email' => 'linked@example.com',
        ]);

        $otherTeacherStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Other Teacher Student',
            'email' => 'other-teacher@example.com',
        ]);

        $unlinkedStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Unlinked Student',
            'email' => 'unlinked@example.com',
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $linkedStudent->id,
            'source' => 'manual',
        ]);

        TeacherStudent::create([
            'teacher_id' => $otherTeacher->id,
            'student_id' => $otherTeacherStudent->id,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($teacher)->get('/teacher/students?scope=linked');

        $response->assertOk();
        $response->assertSee('Linked Student');
        $response->assertDontSee('Other Teacher Student');
        $response->assertDontSee('Unlinked Student');
    }

    public function test_student_role_is_denied_access_to_teacher_students_page(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get('/teacher/students')
            ->assertStatus(403);
    }

    public function test_search_and_pagination_work_for_teacher_students_roster(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        for ($i = 1; $i <= 12; $i++) {
            $student = User::factory()->create([
                'role' => 'student',
                'name' => sprintf('Roster Student %02d', $i),
                'email' => sprintf('roster%02d@example.com', $i),
            ]);

            TeacherStudent::create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'source' => 'manual',
            ]);
        }

        $searchMatch = User::factory()->create([
            'role' => 'student',
            'name' => 'Unique Search Match',
            'email' => 'special-find@example.com',
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $searchMatch->id,
            'source' => 'manual',
        ]);

        $pageOne = $this->actingAs($teacher)->get('/teacher/students');
        $pageOne->assertOk();
        $pageOne->assertSee('Roster Student 01');
        $pageOne->assertDontSee('Roster Student 12');
        $pageOne->assertSee('?page=2');

        $pageTwo = $this->actingAs($teacher)->get('/teacher/students?page=2');
        $pageTwo->assertOk();
        $pageTwo->assertSee('Roster Student 12');

        $search = $this->actingAs($teacher)->get('/teacher/students?search=Unique');
        $search->assertOk();
        $search->assertSee('Unique Search Match');
        $search->assertDontSee('Roster Student 01');

        TeacherStudent::where('teacher_id', $teacher->id)
            ->where('student_id', $searchMatch->id)
            ->update(['student_alias' => 'Саша']);

        $searchByAlias = $this->actingAs($teacher)->get('/teacher/students?search=Саша');
        $searchByAlias->assertOk();
        $searchByAlias->assertSee('Unique Search Match');
        $searchByAlias->assertDontSee('Roster Student 01');
    }

    public function test_roster_rows_include_student_drilldown_links(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Link Target Student',
            'email' => 'link-target@example.com',
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($teacher)->get('/teacher/students');

        $response->assertOk();
        $response->assertSee(route('teacher.students.show', ['id' => $student->id]), false);
    }

    public function test_student_role_is_denied_access_to_teacher_student_drilldown_page(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $studentViewer = User::factory()->create(['role' => 'student']);
        $studentTarget = User::factory()->create(['role' => 'student']);

        $variant = OgeVariant::query()->create([
            'hash' => Str::random(16),
            'owner_teacher_id' => $teacher->id,
        ]);

        OgeAttempt::query()->create([
            'variant_id' => $variant->id,
            'student_id' => $studentTarget->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($studentViewer)
            ->get("/teacher/students/{$studentTarget->id}")
            ->assertStatus(403);
    }

    public function test_teacher_student_drilldown_shows_wrong_task_details_and_filters_other_teachers_variants(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Drilldown Student',
            'email' => 'drilldown@example.com',
        ]);

        $visibleVariant = OgeVariant::query()->create([
            'hash' => Str::random(16),
            'owner_teacher_id' => $teacher->id,
            'title' => 'Visible Variant',
            'source' => 'custom_random',
            'config_json' => [
                'source' => 'custom_random',
                'custom_tasks' => [
                    [
                        'attempt_task_number' => 1,
                        'zadanie_number' => 15,
                        'instruction' => 'Найдите значение выражения',
                        'task' => ['text' => '2 + 2 = ?'],
                    ],
                    [
                        'attempt_task_number' => 2,
                        'zadanie_number' => 6,
                        'task' => [
                            'question' => 'Выберите правильный рисунок',
                            'image' => 'task06_preview.png',
                        ],
                        'topic_id' => '06',
                    ],
                    [
                        'attempt_task_number' => 3,
                        'zadanie_number' => 9,
                        'task' => [
                            'svg' => '<svg data-test-svg="wrong-task" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4"/></svg>',
                        ],
                    ],
                ],
            ],
        ]);

        $hiddenVariant = OgeVariant::query()->create([
            'hash' => Str::random(16),
            'owner_teacher_id' => $otherTeacher->id,
            'title' => 'Hidden Variant',
        ]);

        $visibleAttempt = OgeAttempt::query()->create([
            'variant_id' => $visibleVariant->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'last_seen_at' => now()->subMinutes(50),
        ]);

        $hiddenAttempt = OgeAttempt::query()->create([
            'variant_id' => $hiddenVariant->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subHours(3),
            'submitted_at' => now()->subHours(2),
            'last_seen_at' => now()->subHours(2),
        ]);

        OgeAttemptAnswer::query()->create([
            'attempt_id' => $visibleAttempt->id,
            'task_number' => 1,
            'current_answer' => '5',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $visibleAttempt->id,
            'task_number' => 1,
            'is_correct' => false,
            'correct_answer' => '4',
            'checked_at' => now()->subMinutes(49),
        ]);

        OgeAttemptAnswer::query()->create([
            'attempt_id' => $visibleAttempt->id,
            'task_number' => 2,
            'current_answer' => '13',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $visibleAttempt->id,
            'task_number' => 2,
            'is_correct' => false,
            'correct_answer' => '12',
            'checked_at' => now()->subMinutes(48),
        ]);

        OgeAttemptAnswer::query()->create([
            'attempt_id' => $visibleAttempt->id,
            'task_number' => 3,
            'current_answer' => '1',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $visibleAttempt->id,
            'task_number' => 3,
            'is_correct' => false,
            'correct_answer' => '2',
            'checked_at' => now()->subMinutes(47),
        ]);

        OgeAttemptAnswer::query()->create([
            'attempt_id' => $hiddenAttempt->id,
            'task_number' => 1,
            'current_answer' => 'hidden-answer-zeta',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $hiddenAttempt->id,
            'task_number' => 1,
            'is_correct' => false,
            'correct_answer' => 'hidden-correct-zeta',
            'checked_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($teacher)->get("/teacher/students/{$student->id}");

        $response->assertOk();
        $response->assertSee('Drilldown Student');
        $response->assertSee('Visible Variant');
        $response->assertDontSee('Hidden Variant');
        $response->assertSee('Задание <span class="font-semibold" data-wrong-task-number>15</span>', false);
        $response->assertSee('2 + 2 = ?');
        $response->assertSee('5');
        $response->assertSee('4');
        $response->assertSee('Задание <span class="font-semibold" data-wrong-task-number>6</span>', false);
        $response->assertSee('Выберите правильный рисунок');
        $response->assertSee('images/tasks/06/task06_preview.png');
        $response->assertSee('13');
        $response->assertSee('12');
        $response->assertSee('Задание <span class="font-semibold" data-wrong-task-number>9</span>', false);
        $response->assertSee('<svg data-test-svg="wrong-task"', false);
        $response->assertSee('Текст задания недоступен');
        $response->assertSee('debug:');
        $response->assertDontSee('hidden-answer-zeta');
        $response->assertDontSee('hidden-correct-zeta');
    }

    public function test_teacher_student_drilldown_resolves_generator_variant_task_payload_via_builder(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $variant = OgeVariant::query()->create([
            'hash' => 'genhash01abcdefg',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Generator Variant',
            'source' => 'generator',
            'config_json' => [
                'source' => 'generator',
                'zadaniya' => ['06_1_1'],
            ],
        ]);

        $attempt = OgeAttempt::query()->create([
            'variant_id' => $variant->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'submitted_at' => now()->subMinute(),
        ]);

        OgeAttemptAnswer::query()->create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'current_answer' => '7',
            'commits_count' => 1,
            'is_final' => true,
        ]);

        OgeAttemptScoring::query()->create([
            'attempt_id' => $attempt->id,
            'task_number' => 6,
            'is_correct' => false,
            'correct_answer' => '8',
            'checked_at' => now(),
        ]);

        $builder = Mockery::mock(OgeVariantBuilderService::class);
        $builder->shouldReceive('build')
            ->once()
            ->with('genhash01abcdefg', ['06_1_1'])
            ->andReturn([
                'tasks' => [[
                    'task_number' => 6,
                    'topic_id' => '06',
                    'task' => [
                        'question' => 'Сколько будет 3 + 5?',
                        'svg' => '<svg data-test-svg="generator-task" viewBox="0 0 10 10"></svg>',
                    ],
                    'options' => ['7', '8', '9'],
                ]],
                'variantNumber' => 1,
                'selectedZadaniya' => ['06_1_1'],
            ]);
        $this->app->instance(OgeVariantBuilderService::class, $builder);

        $response = $this->actingAs($teacher)->get("/teacher/students/{$student->id}");

        $response->assertOk();
        $response->assertSee('Задание <span class="font-semibold" data-wrong-task-number>6</span>', false);
        $response->assertSee('Сколько будет 3 + 5?');
        $response->assertSee('7, 8, 9');
        $response->assertSee('<svg data-test-svg="generator-task"', false);
    }
}
