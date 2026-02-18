<?php

namespace Tests\Feature\Audit;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditApiFiltersTest extends TestCase
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

    public function test_teacher_can_filter_audit_events_by_date_and_type(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        AuditEvent::query()->create([
            'occurred_at' => now()->subDay(),
            'event_type' => 'task_focused',
            'category' => 'oge',
            'severity' => 'info',
        ]);

        AuditEvent::query()->create([
            'occurred_at' => now()->subDay(),
            'event_type' => 'login_success',
            'category' => 'auth',
            'severity' => 'info',
        ]);

        $response = $this->actingAs($teacher)->getJson('/api/audit/events?event_type[]=task_focused');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.event_type', 'task_focused');
    }
}
