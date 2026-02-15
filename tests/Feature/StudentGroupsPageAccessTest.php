<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class StudentGroupsPageAccessTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_guest_cannot_open_teacher_groups_page(): void
    {
        $this->get('/teacher/groups')->assertRedirect('/login');
    }

    public function test_student_cannot_open_teacher_groups_page(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)->get('/teacher/groups')->assertStatus(403);
    }

    public function test_teacher_and_admin_can_open_teacher_groups_page(): void
    {
        $teacher = $this->userWithRole('teacher');
        $admin = $this->userWithRole('admin');

        $this->actingAs($teacher)->get('/teacher/groups')->assertOk();
        $this->actingAs($admin)->get('/teacher/groups')->assertOk();
    }
}
