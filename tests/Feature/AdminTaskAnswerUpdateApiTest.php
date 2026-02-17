<?php

namespace Tests\Feature;

use App\Models\TaskAnswerOverride;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminTaskAnswerUpdateApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('task_answer_override_logs');
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

        Schema::create('task_answer_override_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('override_id')->constrained('task_answer_overrides')->cascadeOnDelete();
            $table->text('old_answer')->nullable();
            $table->text('new_answer');
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function test_teacher_cannot_patch_task_answer(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->patchJson('/api/topics/06/answers', [
                'task_key' => 'topic_06_block_1_zadanie_1_task_1',
                'answer' => '10',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_patch_task_answer_and_persist_override(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Maria']);

        $payload = [
            'task_key' => 'topic_06_block_1_zadanie_1_task_1',
            'answer' => '42',
        ];

        $this->actingAs($admin)
            ->patchJson('/api/topics/06/answers', $payload)
            ->assertOk()
            ->assertJsonPath('source', 'manual')
            ->assertJsonPath('source_label', 'Ручной by Maria')
            ->assertJsonPath('updated_by_name', 'Maria');

        $this->assertDatabaseHas('task_answer_overrides', [
            'task_key' => $payload['task_key'],
            'answer' => '42',
            'source' => 'manual',
            'updated_by_user_id' => $admin->id,
        ]);

        $override = TaskAnswerOverride::where('task_key', $payload['task_key'])->firstOrFail();

        $this->assertDatabaseHas('task_answer_override_logs', [
            'override_id' => $override->id,
            'old_answer' => null,
            'new_answer' => '42',
            'changed_by_user_id' => $admin->id,
        ]);
    }

    public function test_patch_requires_non_empty_answer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson('/api/topics/06/answers', [
                'task_key' => 'topic_06_block_1_zadanie_1_task_1',
                'answer' => '',
            ])
            ->assertStatus(422);
    }
}
