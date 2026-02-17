<?php

namespace Tests\Feature;

use App\Models\TaskAnswerOverride;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TopicAnswerProvenanceViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('task_answer_overrides');
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

        Schema::create('task_answer_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('task_key')->unique();
            $table->string('answer');
            $table->string('source')->default('manual');
            $table->foreignId('updated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function test_topic_page_shows_manual_provenance_badge_for_overridden_answer(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Ivan']);

        TaskAnswerOverride::create([
            'task_key' => 'topic_06_block_1_zadanie_1_task_1',
            'answer' => '77',
            'source' => 'manual',
            'updated_by_user_id' => $admin->id,
        ]);

        $this->actingAs($teacher)
            ->get('/topics/6')
            ->assertOk()
            ->assertSee('77')
            ->assertSee('Ручной by Ivan');
    }

    public function test_topic_page_shows_ai_badge_for_non_overridden_answer(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get('/topics/6')
            ->assertOk()
            ->assertSee('[AI]');
    }
}
