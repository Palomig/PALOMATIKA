<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalWebLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_via_web_login_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'student.test@example.com',
            'password' => 'StrongPass123',
            'role' => 'student',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'student.test@example.com',
            'password' => 'StrongPass123',
            'remember' => true,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertAuthenticatedAs($user);
    }
}
