<?php

namespace Tests\Feature\Audit;

use App\Models\AuditEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PruneAuditEventsCommandTest extends TestCase
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

    public function test_prune_command_deletes_old_events_only(): void
    {
        AuditEvent::query()->create([
            'occurred_at' => now()->subDays(120),
            'event_type' => 'old_event',
            'category' => 'system',
            'severity' => 'info',
        ]);

        AuditEvent::query()->create([
            'occurred_at' => now()->subDays(2),
            'event_type' => 'new_event',
            'category' => 'system',
            'severity' => 'info',
        ]);

        $this->artisan('audit:prune --days=90')->assertSuccessful();

        $this->assertDatabaseMissing('audit_events', ['event_type' => 'old_event']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'new_event']);
    }
}
