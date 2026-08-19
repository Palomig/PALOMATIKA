<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Разбор для учителя открывается поверх списка заданий: кнопка тянет
 * фрагмент того же адреса, отдельная страница остаётся рабочей.
 */
class Part2SolutionModalTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'http://student.palomatika.ru';

    protected function setUp(): void
    {
        parent::setUp();

        $group = TaskGroup::create([
            'bank' => 'oge', 'topic' => '21', 'block_number' => 1, 'block_title' => 'Движение',
            'zadanie_number' => 3, 'position' => 3, 'type' => 'word_problem', 'source' => 'fipi',
            'payload' => [
                'number' => 3,
                'instruction' => 'Средняя скорость',
                'taxonomy_key' => 'average_speed',
                'solution' => '<p>Весь путь разделить на всё время.</p>',
            ],
        ]);
        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'word_problem', 'source' => 'fipi',
            'payload' => ['id' => 1, 'text' => 'Первые 450 км автомобиль ехал со скоростью 90 км/ч.'],
            'answer' => '60',
        ]);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => 'U', 'email' => 'u+' . uniqid() . '@t.t', 'password' => 'x', 'role' => $role,
            'onboarding_completed_at' => now(), 'telegram_chat_id' => random_int(100000000, 999999999),
        ]);
    }

    public function test_list_carries_the_modal_and_a_link_that_opens_it(): void
    {
        $response = $this->actingAs($this->user('teacher'))->get(self::BASE . '/part2?topic=21');

        $response->assertOk();
        $response->assertSee('js-solution-open', false);
        $response->assertSee('id="solution-modal"', false);
        // Ссылка остаётся настоящей: без JS разбор всё равно откроется страницей.
        $response->assertSee(self::BASE . '/part2/solution/21/3', false);
    }

    public function test_fragment_returns_the_solution_without_page_chrome(): void
    {
        $response = $this->actingAs($this->user('teacher'))
            ->get(self::BASE . '/part2/solution/21/3?fragment=1');

        $response->assertOk();
        $response->assertSee('Весь путь разделить на всё время', false);
        $response->assertSee('Средняя скорость');
        $response->assertDontSee('<!DOCTYPE', false);
        $response->assertDontSee('<body', false);
    }

    public function test_standalone_page_still_works(): void
    {
        $response = $this->actingAs($this->user('teacher'))->get(self::BASE . '/part2/solution/21/3');

        $response->assertOk();
        $response->assertSee('<!DOCTYPE', false);
        $response->assertSee('Весь путь разделить на всё время', false);
    }

    public function test_student_gets_no_solution_in_any_form(): void
    {
        $student = $this->user('student');

        $this->actingAs($student)->get(self::BASE . '/part2/solution/21/3')->assertForbidden();
        $this->actingAs($student)->get(self::BASE . '/part2/solution/21/3?fragment=1')->assertForbidden();
        // На странице нет ни кнопки, ни адреса разбора (скрипт окна там есть всегда).
        $page = $this->actingAs($student)->get(self::BASE . '/part2?topic=21');
        $page->assertDontSee('class="teacher-solution-btn js-solution-open"', false);
        $page->assertDontSee('/part2/solution/21/3', false);
        $page->assertDontSee('Весь путь разделить на всё время', false);
    }
}
