<?php

namespace Tests\Feature\Audit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditEventsMigrationTest extends TestCase
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

    public function test_audit_events_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('audit_events'));
        $this->assertTrue(Schema::hasColumns('audit_events', [
            'occurred_at',
            'event_type',
            'category',
            'severity',
            'actor_user_id',
            'actor_role',
            'subject_type',
            'subject_id',
            'request_id',
            'ip',
            'user_agent',
            'payload_json',
        ]));
    }
}
