<?php

namespace Tests\Feature;

use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MiniAppTeacherRoutesTest extends TestCase
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
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->string('avatar')->nullable();
            $table->unsignedTinyInteger('grade_num')->nullable();
            $table->string('grade_letter', 5)->nullable();
            $table->string('school_number', 20)->nullable();
            $table->string('city', 80)->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 32)->default('manual');
            $table->string('student_alias', 80)->nullable();
            $table->string('evrium_name', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['teacher_id', 'student_id']);
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('mode', 32)->nullable();
            $table->string('source', 32)->nullable();
            $table->boolean('is_curated')->default(false);
            $table->text('config_json')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('student_id')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('oge_attempts')->cascadeOnDelete();
            $table->unsignedInteger('task_number');
            $table->text('current_answer')->nullable();
            $table->unsignedInteger('commits_count')->default(0);
            $table->timestamp('first_committed_at')->nullable();
            $table->timestamp('last_committed_at')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('oge_attempt_scorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('oge_attempts')->cascadeOnDelete();
            $table->unsignedInteger('task_number');
            $table->boolean('is_correct')->nullable();
            $table->text('correct_answer')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->useCurrent();
            $table->string('event_type', 80);
            $table->string('category', 40)->default('system');
            $table->string('severity', 20)->default('info');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role', 32)->nullable();
            $table->string('subject_type', 80)->nullable();
            $table->string('subject_id', 191)->nullable();
            $table->string('request_id', 100)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload_json')->nullable();
            $table->timestamps();
        });
    }

    public function test_teacher_dashboard_route_is_available_in_tg_namespace(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get('/tg/teacher/dashboard')
            ->assertOk();
    }

    public function test_teacher_dashboard_renders_classic_teacher_navigation(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get('/tg/teacher/dashboard')
            ->assertOk()
            ->assertSee('Кабинет учителя')
            ->assertSee('Ученики и алиасы')
            ->assertSee('Домашка')
            ->assertSee('Последние варианты');
    }

    public function test_teacher_lessons_page_renders_slot_statuses(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get('/tg/teacher/lessons')
            ->assertOk()
            ->assertSee('Уроки сегодня')
            ->assertSee('прошёл')
            ->assertSee('идёт')
            ->assertSee('будет');
    }

    public function test_tg_dashboard_redirects_teacher_to_teacher_dashboard(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get('/tg/dashboard')
            ->assertRedirect('/tg/teacher/dashboard');
    }

    public function test_admin_can_switch_miniapp_mode_to_teacher_and_back_to_student(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/tg/mode/teacher')
            ->assertRedirect('https://teacher.palomatika.ru/dashboard');

        $this->assertSame('teacher', session('view_as_role'));

        $this->actingAs($admin)
            ->post('/tg/mode/student')
            ->assertRedirect('https://student.palomatika.ru/');

        $this->assertSame('student', session('view_as_role'));
    }

    public function test_teacher_students_search_matches_teacher_scoped_alias(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Иван Петров',
            'onboarding_completed_at' => now(),
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
            'student_alias' => 'Петя',
        ]);

        $this->actingAs($teacher)
            ->get('/tg/teacher/students?search=Петя')
            ->assertOk()
            ->assertSee('Иван Петров')
            ->assertSee('Петя');
    }

    public function test_teacher_can_update_alias_with_audit_log(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create(['role' => 'student']);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $this->actingAs($teacher)
            ->patchJson("/tg/teacher/students/{$student->id}/alias", ['alias' => 'Новый псевдоним'])
            ->assertOk()
            ->assertJsonPath('alias', 'Новый псевдоним');

        $this->assertDatabaseHas('teacher_students', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'student_alias' => 'Новый псевдоним',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'teacher_student_alias_updated',
            'actor_user_id' => $teacher->id,
            'subject_type' => 'teacher_student',
            'subject_id' => $teacher->id . ':' . $student->id,
        ]);
    }

    public function test_teacher_alias_validation_rejects_too_long_values(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create(['role' => 'student']);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $this->actingAs($teacher)
            ->patchJson("/tg/teacher/students/{$student->id}/alias", ['alias' => str_repeat('a', 81)])
            ->assertStatus(422);
    }

    public function test_teacher_student_profile_shows_instruction_condition_and_task_locator_for_wrong_task(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'source' => 'manual',
        ]);

        $variantId = DB::table('oge_variants')->insertGetId([
            'hash' => 'nnzzuijfqo',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Word Problems',
            'mode' => 'full',
            'source' => 'miniapp',
            'is_curated' => false,
            'config_json' => json_encode([
                'tasks' => [[
                    'task_number' => 12,
                    'topic_id' => '12',
                    'block_number' => 1,
                    'zadanie_number' => 3,
                    'instruction' => 'Физика: мощность тока',
                    'task' => [
                        'id' => 5,
                        'text' => 'В фирме «Родник» стоимость оборудования составляет 47000 рублей.',
                        'answer' => '47000',
                    ],
                ]],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attemptId = DB::table('oge_attempts')->insertGetId([
            'variant_id' => $variantId,
            'student_id' => $student->id,
            'status' => 'submitted',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oge_attempt_answers')->insert([
            'attempt_id' => $attemptId,
            'task_number' => 12,
            'current_answer' => '45000',
            'commits_count' => 1,
            'is_final' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oge_attempt_scorings')->insert([
            'attempt_id' => $attemptId,
            'task_number' => 12,
            'is_correct' => false,
            'correct_answer' => '47000',
            'checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Profile page shows history list with variant label and score
        $response = $this->actingAs($teacher)->get("/tg/teacher/students/{$student->id}");

        $response->assertOk();
        $response->assertSee('История вариантов');
        $response->assertSee('Темы/задания: точность');
        $response->assertSee('Полный вариант');
        $response->assertSee('Задание 12');
        $response->assertSee('nnzzuijfqo');
        $response->assertSee("/tg/teacher/students/{$student->id}/attempt/{$attemptId}");

        // Attempt detail page shows errors with task content
        $detail = $this->actingAs($teacher)->get("/tg/teacher/students/{$student->id}/attempt/{$attemptId}");

        $detail->assertOk();
        $detail->assertSee('Физика: мощность тока');
        $detail->assertSee('В фирме «Родник» стоимость оборудования составляет 47000 рублей.');
        $detail->assertSee('45000');
        $detail->assertSee('47000');
    }
}
