<?php

namespace Tests\Unit;

use App\Services\AuditLogger;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
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
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_02_18_000001_create_audit_events_table.php');
        $migration->up();
    }

    public function test_logger_persists_actor_subject_and_payload(): void
    {
        $user = User::query()->create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'role' => 'student',
        ]);

        $event = app(AuditLogger::class)->log([
            'event_type' => 'login_success',
            'category' => 'auth',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => 'student',
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'payload_json' => ['method' => 'password'],
        ]);

        $this->assertDatabaseHas('audit_events', [
            'id' => $event->id,
            'event_type' => 'login_success',
            'category' => 'auth',
            'actor_role' => 'student',
            'subject_type' => 'user',
            'subject_id' => (string) $user->id,
        ]);
    }
}
