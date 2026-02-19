<?php

namespace Tests\Feature\Api;

use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OgeVariantV1ApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('oge_variants');
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

        Schema::create('oge_variants', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 16)->unique();
            $table->foreignId('owner_teacher_id')->nullable();
            $table->string('title')->nullable();
            $table->string('source', 32)->nullable();
            $table->string('external_ref', 255)->nullable();
            $table->string('created_via', 64)->nullable();
            $table->json('config_json')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['owner_teacher_id', 'external_ref']);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->nullable();
            $table->string('event_type', 64);
            $table->string('category', 32);
            $table->string('severity', 16)->default('info');
            $table->foreignId('actor_user_id')->nullable();
            $table->string('actor_role', 32)->nullable();
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();
        });
    }

    public function test_generator_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/oge/variants/generator', [
            'zadaniya' => ['06_1_1'],
        ])->assertStatus(401);
    }

    public function test_generator_endpoint_validates_hash_and_payload(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $this->postJson('/api/oge/variants/generator', [
            'hash' => 'INVALID!',
            'zadaniya' => ['bad-format'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hash', 'zadaniya.0']);
    }

    public function test_student_cannot_access_variant_creation_endpoints(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $this->postJson('/api/oge/variants/generator', [
            'zadaniya' => ['06_1_1'],
        ])->assertStatus(403);

        $this->postJson('/api/oge/variants/custom-random', [
            'topics' => ['06'],
            'tasks_per_topic' => 1,
        ])->assertStatus(403);
    }

    public function test_admin_can_create_variant_via_api_without_session_crash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/oge/variants/generator', [
            'hash' => 'admv1101',
            'zadaniya' => ['06_1_1'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.hash', 'admv1101')
            ->assertJsonPath('data.owner_teacher_id', $admin->id);
    }

    public function test_generator_endpoint_is_idempotent_by_external_ref_per_owner(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $first = $this->postJson('/api/oge/variants/generator', [
            'hash' => 'genv1a1',
            'zadaniya' => ['06_1_1', '07_1_1'],
            'external_ref' => 'crm-123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.hash', 'genv1a1')
            ->json('data');

        $this->postJson('/api/oge/variants/generator', [
            'hash' => 'genv1b2',
            'zadaniya' => ['06_1_1'],
            'external_ref' => 'crm-123',
        ])
            ->assertOk()
            ->assertJsonPath('data.hash', 'genv1a1')
            ->assertJsonPath('data.idempotent', true);

        $this->assertDatabaseCount('oge_variants', 1);
        $this->assertSame('genv1a1', $first['hash']);
    }

    public function test_custom_random_endpoint_returns_conflict_when_hash_taken(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        OgeVariant::create([
            'hash' => 'taken123',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Existing',
            'source' => 'generator',
        ]);

        $this->postJson('/api/oge/variants/custom-random', [
            'hash' => 'taken123',
            'topics' => ['06'],
            'tasks_per_topic' => 1,
        ])->assertStatus(409);
    }

    public function test_generator_duplicate_external_ref_returns_idempotent_instead_of_500(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);
        OgeVariant::create([
            'hash' => 'racepre1',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Preexisting variant',
            'source' => 'generator',
            'external_ref' => 'race-ext-1',
            'created_via' => 'api_v1_generator',
            'config_json' => [
                'zadaniya' => ['06_1_1'],
                'source' => 'generator',
            ],
        ]);

        $this->postJson('/api/oge/variants/generator', [
            'hash' => 'raceut11',
            'zadaniya' => ['06_1_1'],
            'external_ref' => 'race-ext-1',
        ])
            ->assertOk()
            ->assertJsonPath('data.hash', 'racepre1')
            ->assertJsonPath('data.idempotent', true);

        $this->assertDatabaseCount('oge_variants', 1);
    }

    public function test_generator_returns_conflict_when_hash_already_exists(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);
        OgeVariant::create([
            'hash' => 'racehash',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Preexisting hash variant',
            'source' => 'generator',
            'external_ref' => 'race-hash-ext',
            'created_via' => 'api_v1_generator',
            'config_json' => [
                'zadaniya' => ['06_1_1'],
                'source' => 'generator',
            ],
        ]);

        $this->postJson('/api/oge/variants/generator', [
            'hash' => 'racehash',
            'zadaniya' => ['06_1_1'],
        ])
            ->assertStatus(409);
    }

    public function test_generator_and_custom_random_create_successfully(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $generator = $this->postJson('/api/oge/variants/generator', [
            'hash' => 'genok123',
            'zadaniya' => ['06_1_1', '07_1_1'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.hash', 'genok123')
            ->assertJsonPath('data.source', 'generator')
            ->assertJsonPath('data.owner_teacher_id', $teacher->id)
            ->assertJsonPath('data.url', url('/oge/genok123'));

        $this->assertDatabaseHas('oge_variants', [
            'hash' => 'genok123',
            'owner_teacher_id' => $teacher->id,
            'source' => 'generator',
        ]);

        $custom = $this->postJson('/api/oge/variants/custom-random', [
            'hash' => 'cusok123',
            'topics' => ['06', '07'],
            'tasks_per_topic' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('data.hash', 'cusok123')
            ->assertJsonPath('data.source', 'custom_random')
            ->assertJsonPath('data.owner_teacher_id', $teacher->id)
            ->assertJsonPath('data.url', url('/oge/cusok123'));

        $this->assertDatabaseHas('oge_variants', [
            'hash' => 'cusok123',
            'owner_teacher_id' => $teacher->id,
            'source' => 'custom_random',
        ]);

        $this->assertTrue(is_array($custom->json('data.custom_tasks')));
        $this->assertNotEmpty($custom->json('data.custom_tasks'));

        $generator->assertJsonPath('data.idempotent', false);
        $custom->assertJsonPath('data.idempotent', false);
    }

    public function test_get_variant_returns_metadata_by_default_and_full_on_expand(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        OgeVariant::create([
            'hash' => 'meta1234',
            'owner_teacher_id' => $teacher->id,
            'title' => 'Meta variant',
            'source' => 'custom_random',
            'external_ref' => 'ext-1',
            'created_via' => 'api_v1_custom_random',
            'config_json' => [
                'source' => 'custom_random',
                'topics' => ['06'],
                'tasks_per_topic' => 1,
                'custom_tasks' => [['test_number' => 1]],
            ],
        ]);

        $this->getJson('/api/oge/variants/meta1234')
            ->assertOk()
            ->assertJsonPath('data.hash', 'meta1234')
            ->assertJsonPath('data.source', 'custom_random')
            ->assertJsonPath('data.external_ref', 'ext-1')
            ->assertJsonMissingPath('data.config_json');

        $this->getJson('/api/oge/variants/meta1234?expand=full')
            ->assertOk()
            ->assertJsonPath('data.hash', 'meta1234')
            ->assertJsonPath('data.config_json.source', 'custom_random')
            ->assertJsonPath('data.config_json.topics.0', '06');
    }

    public function test_custom_random_endpoint_keeps_all_requested_topics_in_payload(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $response = $this->postJson('/api/oge/variants/custom-random', [
            'hash' => 'topicfix1',
            'topics' => ['06', '08', '09', '15', '16', '18'],
            'tasks_per_topic' => 1,
        ])
            ->assertCreated();

        $taskNumbers = $response->json('data.config_json.custom_task_numbers');
        sort($taskNumbers);
        $this->assertSame([6, 8, 9, 15, 16, 18], $taskNumbers);

        $taskTopics = array_values(array_unique(array_map(
            static fn (array $task): int => (int) ($task['topic_id'] ?? 0),
            $response->json('data.custom_tasks')
        )));
        sort($taskTopics);
        $this->assertSame([6, 8, 9, 15, 16, 18], $taskTopics);
    }
}
