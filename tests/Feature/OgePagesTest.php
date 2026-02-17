<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class OgePagesTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        return User::factory()->make(['role' => $role]);
    }

    public function test_topics_index_page_loads_for_teacher(): void
    {
        $response = $this->actingAs($this->userWithRole('teacher'))->get('/topics');

        $response->assertOk();
        $response->assertSee('База заданий ОГЭ');
    }

    public function test_legacy_oge_generator_page_loads(): void
    {
        $response = $this->actingAs($this->userWithRole('teacher'))->get('/test/oge');

        $response->assertOk();
        $response->assertSee('Генератор вариантов ОГЭ');
    }

    public function test_new_oge_generator_page_loads(): void
    {
        $response = $this->actingAs($this->userWithRole('teacher'))->get('/oge');

        $response->assertOk();
        $response->assertSee('Генератор вариантов ОГЭ');
    }

    public function test_legacy_oge_variant_page_loads(): void
    {
        $response = $this->actingAs($this->userWithRole('student'))->get('/test/oge/abc123');

        $response->assertOk();
        $response->assertSee('Вариант');
    }

    public function test_new_oge_variant_page_loads(): void
    {
        $response = $this->actingAs($this->userWithRole('student'))->get('/oge/abc123');

        $response->assertOk();
        $response->assertSee('Вариант');
    }
}
