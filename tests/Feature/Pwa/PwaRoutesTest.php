<?php

namespace Tests\Feature\Pwa;

use Tests\TestCase;

class PwaRoutesTest extends TestCase
{
    public function test_student_login_page_is_accessible(): void
    {
        $response = $this->get('http://student.palomatika.ru/login');
        $response->assertStatus(200);
    }

    public function test_teacher_login_page_is_accessible(): void
    {
        $response = $this->get('http://teacher.palomatika.ru/login');
        $response->assertStatus(200);
    }

    public function test_student_manifest_is_accessible(): void
    {
        $response = $this->get('http://student.palomatika.ru/manifest.json');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/manifest+json');
    }

    public function test_student_dashboard_requires_auth(): void
    {
        $response = $this->get('http://student.palomatika.ru/');
        $response->assertRedirect();
    }

    public function test_root_landing_links_to_student_and_teacher_pwas(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('student.palomatika.ru', false);
        $response->assertSee('teacher.palomatika.ru', false);
    }
}
