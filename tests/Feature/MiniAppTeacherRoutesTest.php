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
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['teacher_id', 'student_id']);
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('source', 32)->nullable();
            $table->boolean('is_curated')->default(false);
            $table->text('config_json')->nullable();
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
            ->assertRedirect('/tg/teacher/dashboard');

        $this->assertSame('teacher', session('view_as_role'));

        $this->actingAs($admin)
            ->post('/tg/mode/student')
            ->assertRedirect('/tg/dashboard');

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
}
