<?php

namespace Tests\Feature\Api;

use App\Models\JarvisMaterial;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JarvisMaterialsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropIfExists('jarvis_materials');
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

        Schema::create('jarvis_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('source_content');
            $table->string('status', 24)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function test_materials_api_requires_authentication(): void
    {
        $this->getJson('/api/materials')->assertStatus(401);
        $this->postJson('/api/materials', [])->assertStatus(401);
    }

    public function test_student_cannot_use_materials_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'student']));

        $this->getJson('/api/materials')->assertStatus(403);
    }

    public function test_teacher_can_crud_own_material_and_publish_and_archive(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $created = $this->postJson('/api/materials', [
            'title' => 'Quadratic formulas',
            'source_content' => 'Use $x^2$ and $$a^2+b^2=c^2$$',
            'excerpt' => 'Algebra quick notes',
        ])->assertCreated();

        $materialId = (int) $created->json('data.id');

        $this->assertDatabaseHas('jarvis_materials', [
            'id' => $materialId,
            'owner_teacher_id' => $teacher->id,
            'slug' => 'quadratic-formulas',
            'status' => 'draft',
        ]);

        $this->patchJson("/api/materials/{$materialId}", [
            'title' => 'Quadratic formulas updated',
        ])->assertOk()->assertJsonPath('data.title', 'Quadratic formulas updated');

        $this->postJson("/api/materials/{$materialId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.slug', 'quadratic-formulas-updated');

        $this->postJson("/api/materials/{$materialId}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_teacher_cannot_manage_other_teacher_material(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);

        $material = JarvisMaterial::create([
            'owner_teacher_id' => $owner->id,
            'title' => 'Owner only',
            'slug' => 'owner-only',
            'source_content' => 'Content',
            'status' => JarvisMaterial::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($other);

        $this->getJson("/api/materials/{$material->id}")->assertStatus(403);
        $this->patchJson("/api/materials/{$material->id}", [
            'title' => 'Hack',
        ])->assertStatus(403);
        $this->postJson("/api/materials/{$material->id}/publish")->assertStatus(403);
    }

    public function test_admin_can_manage_any_material(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $admin = User::factory()->create(['role' => 'admin']);

        $material = JarvisMaterial::create([
            'owner_teacher_id' => $owner->id,
            'title' => 'Shared',
            'slug' => 'shared',
            'source_content' => 'Body',
            'status' => JarvisMaterial::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/materials/{$material->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
    }

    public function test_slug_generation_is_unique_for_same_title(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $first = $this->postJson('/api/materials', [
            'title' => 'Math Intro',
            'source_content' => 'One',
        ])->assertCreated();

        $second = $this->postJson('/api/materials', [
            'title' => 'Math Intro',
            'source_content' => 'Two',
        ])->assertCreated();

        $this->assertSame('math-intro', $first->json('data.slug'));
        $this->assertNotSame('math-intro', $second->json('data.slug'));
        $this->assertStringStartsWith('math-intro-', $second->json('data.slug'));
    }
}
