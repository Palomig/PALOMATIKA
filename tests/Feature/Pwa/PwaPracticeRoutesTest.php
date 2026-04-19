<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaPracticeRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
            'oauth_provider' => 'vk',
            'oauth_id' => 'practice-test-user',
        ]);
    }

    public function test_dashboard_shows_practice_entry_instead_of_mistake_review_tile(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($student)->get('http://student.palomatika.ru/');

        $response->assertOk();
        $response->assertSee('Практика');
        $response->assertDontSee('Разбор ошибок');
    }

    public function test_practice_pages_are_accessible_for_authenticated_student(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student)
            ->get('http://student.palomatika.ru/practice')
            ->assertOk()
            ->assertSee('Практика')
            ->assertSee('Мини-игры');

        $this->actingAs($student)
            ->get('http://student.palomatika.ru/practice/mini-games')
            ->assertOk()
            ->assertSee('Мини-игры')
            ->assertSee('Уравнения');

        $this->actingAs($student)
            ->get('http://student.palomatika.ru/practice/mini-games/equations')
            ->assertOk()
            ->assertSee('Уравнения')
            ->assertSee('10 секунд на ход')
            ->assertSee('Сыграть ещё');
    }

    public function test_equations_question_api_returns_expected_payload(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($student)
            ->getJson('http://student.palomatika.ru/practice/api/mini-games/equations/question?score=0');

        $response->assertOk();
        $response->assertJsonStructure([
            'game' => ['slug', 'title', 'theory' => ['title', 'items']],
            'question' => [
                'prompt',
                'equation',
                'options' => [
                    '*' => ['id', 'label', 'is_correct'],
                ],
                'level',
                'task_type',
            ],
        ]);
    }
}
