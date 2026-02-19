<?php

namespace Tests\Feature;

use App\Models\JarvisMaterial;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JarvisMaterialsPagesTest extends TestCase
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

    public function test_public_pages_show_only_published_materials(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        JarvisMaterial::create([
            'owner_teacher_id' => $teacher->id,
            'title' => 'Draft only',
            'slug' => 'draft-only',
            'source_content' => 'draft',
            'status' => JarvisMaterial::STATUS_DRAFT,
        ]);

        JarvisMaterial::create([
            'owner_teacher_id' => $teacher->id,
            'title' => 'Published one',
            'slug' => 'published-one',
            'source_content' => 'eq: $x^2$',
            'status' => JarvisMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->get('/materials')
            ->assertOk()
            ->assertSee('Published one')
            ->assertDontSee('Draft only');

        $this->get('/materials/draft-only')->assertNotFound();

        $this->get('/materials/published-one')
            ->assertOk()
            ->assertSee('cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css', false)
            ->assertSee('renderMathInElement(', false)
            ->assertSee("{left: '$', right: '$', display: false}", false)
            ->assertSee("{left: '$$', right: '$$', display: true}", false);
    }

    public function test_teacher_materials_page_requires_teacher_or_admin(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $this->get('/teacher/materials')->assertRedirect('/login');
        $this->actingAs($student)->get('/teacher/materials')->assertStatus(403);
        $this->actingAs($teacher)->get('/teacher/materials')->assertOk();
    }
}
