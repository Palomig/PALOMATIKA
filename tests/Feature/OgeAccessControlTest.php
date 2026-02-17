<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class OgeAccessControlTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_guest_cannot_open_oge_generators(): void
    {
        $this->get('/oge')->assertRedirect('/login');
        $this->get('/test/oge')->assertRedirect('/login');
    }

    public function test_student_cannot_open_oge_generators(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)->get('/oge')->assertStatus(403);
        $this->actingAs($student)->get('/test/oge')->assertStatus(403);
    }

    public function test_teacher_can_open_oge_generators(): void
    {
        $teacher = $this->userWithRole('teacher');

        $this->actingAs($teacher)->get('/oge')->assertOk();
        $this->actingAs($teacher)->get('/test/oge')->assertOk();
    }

    public function test_student_can_open_variant_pages_but_not_generators(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)->get('/oge/abc123')->assertOk();
        $this->actingAs($student)->get('/test/oge/abc123')->assertOk();
    }

    public function test_oge_teachers_page_has_link_to_new_generator(): void
    {
        $teacher = $this->userWithRole('teacher');
        $this->actingAs($teacher);

        $response = $this->view('teacher.oge.teachers', ['teachers' => collect()]);

        $response->assertSee('Открыть генератор');
        $response->assertSee(route('oge.generator'));
    }
}
