<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthAuditLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('audit_events');
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

        $migration = require base_path('database/migrations/2026_02_18_000001_create_audit_events_table.php');
        $migration->up();
    }

    public function test_web_login_success_and_failure_are_logged(): void
    {
        User::factory()->create([
            'email' => 'audit.student@example.com',
            'password' => 'StrongPass123',
            'role' => 'student',
        ]);

        $this->postJson('/login', [
            'email' => 'audit.student@example.com',
            'password' => 'bad-pass',
        ])->assertStatus(422);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'login_failed',
            'category' => 'auth',
        ]);

        $this->postJson('/login', [
            'email' => 'audit.student@example.com',
            'password' => 'StrongPass123',
        ])->assertOk();

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'login_success',
            'category' => 'auth',
        ]);
    }
}
