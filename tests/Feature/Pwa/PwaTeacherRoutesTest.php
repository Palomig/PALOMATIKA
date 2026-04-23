<?php

namespace Tests\Feature\Pwa;

use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PwaTeacherRoutesTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->string('role', 32)->default('student');
            $table->string('avatar')->nullable();
            $table->unsignedTinyInteger('grade_num')->nullable();
            $table->string('grade_letter', 5)->nullable();
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
            $table->timestamp('created_at')->nullable();
            $table->unique(['teacher_id', 'student_id']);
        });

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->unsignedBigInteger('owner_teacher_id')->nullable();
            $table->string('title')->nullable();
            $table->string('mode')->nullable();
            $table->boolean('is_curated')->default(false);
            $table->text('config_json')->nullable();
            $table->timestamps();
        });

        Schema::create('oge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('oge_variants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('active');
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
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_teacher_dashboard_requires_auth(): void
    {
        $response = $this->get('http://teacher.palomatika.ru/dashboard');

        $response->assertRedirect('https://teacher.palomatika.ru/login');
    }

    public function test_teacher_dashboard_accessible_for_teacher_role(): void
    {
        $user = User::factory()->create([
            'oauth_provider' => 'vk',
            'oauth_id' => '789',
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('http://teacher.palomatika.ru/dashboard');

        $response->assertOk();
    }

    public function test_unlinked_filter_includes_fresh_students_without_attempts(): void
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'onboarding_completed_at' => now(),
        ]);

        $linkedStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Linked Student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);

        $freshStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Fresh Student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);

        TeacherStudent::create([
            'teacher_id' => $teacher->id,
            'student_id' => $linkedStudent->id,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($teacher)
            ->get('http://teacher.palomatika.ru/students?filter=unlinked');

        $response->assertOk();
        $response->assertSee('Fresh Student');
        $response->assertDontSee('Linked Student');
    }
}
