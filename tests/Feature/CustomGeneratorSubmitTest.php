<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class CustomGeneratorSubmitTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_can_generate_test_with_topics_14_to_17(): void
    {
        $student = $this->userWithRole('student');

        $response = $this->actingAs($student)->followingRedirects()->post('/test/generator/generate', [
            'topics' => ['14', '15', '16', '17'],
            'tasks_per_topic' => 2,
        ]);

        $response->assertOk();
        $response->assertSee('Вариант №');
    }

    public function test_can_generate_test_with_topics_15_to_19_and_render_each_topic(): void
    {
        $student = $this->userWithRole('student');

        $response = $this->actingAs($student)->followingRedirects()->post('/test/generator/generate', [
            'topics' => ['15', '16', '17', '18', '19'],
            'tasks_per_topic' => 1,
        ]);

        $response->assertOk();
        $response->assertSee('Вариант №');
        $response->assertSee('Задания 6–19');
    }

    public function test_topic_19_generation_renders_three_numbered_statements(): void
    {
        $student = $this->userWithRole('student');

        $response = $this->actingAs($student)->followingRedirects()->post('/test/generator/generate', [
            'topics' => ['19'],
            'tasks_per_topic' => 1,
        ]);

        $response->assertOk();
        $response->assertSee('Вариант №');
        $response->assertSee('Задания 6–19');
    }
}
