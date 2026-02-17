<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class CustomGeneratorHashUrlTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_custom_generator_post_redirects_to_hashed_url(): void
    {
        $teacher = $this->userWithRole('teacher');

        $response = $this->actingAs($teacher)->post('/test/generator/generate', [
            'topics' => ['18'],
            'tasks_per_topic' => 1,
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression('#/oge/[a-z0-9]{8}$#', $location);
    }
}
