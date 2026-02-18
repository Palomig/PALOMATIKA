<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditPageAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('role')->default('student');
            $table->timestamps();
        });
    }

    public function test_teacher_and_admin_can_open_audit_page_but_student_cannot(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($teacher)->get('/teacher/audit')->assertOk();
        $this->actingAs($admin)->get('/teacher/audit')->assertOk();
        $this->actingAs($student)->get('/teacher/audit')->assertStatus(403);
    }
}
