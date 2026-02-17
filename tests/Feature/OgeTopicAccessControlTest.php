<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class OgeTopicAccessControlTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_guest_cannot_open_topics_base(): void
    {
        $this->get('/topics')->assertRedirect('/login');
        $this->get('/topics/6')->assertRedirect('/login');
    }

    public function test_student_cannot_open_topics_base(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)->get('/topics')->assertStatus(403);
        $this->actingAs($student)->get('/topics/6')->assertStatus(403);
    }

    public function test_teacher_can_open_topics_base(): void
    {
        $teacher = $this->userWithRole('teacher');

        $this->actingAs($teacher)->get('/topics')->assertOk();
        $this->actingAs($teacher)->get('/topics/6')->assertOk();
    }
}
