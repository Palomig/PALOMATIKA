<?php

namespace Tests\Feature;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    public function test_only_linked_students_are_visible_for_current_teacher(): void
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
    }
}
