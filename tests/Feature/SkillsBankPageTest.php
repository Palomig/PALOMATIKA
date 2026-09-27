<?php

namespace Tests\Feature;

use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\TaskBankRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * «Скиллы» в базе заданий — раздел учителя.
 *
 * Скиллы сквозные, поэтому вход в них одинаковый из ОГЭ, ЕГЭ и ВПР любого
 * класса. Ученик раздела не видит и не открывает: он готовится к своему
 * экзамену, и чужой банк в его базе только мешает.
 */
class SkillsBankPageTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        TaskBankRepository::forgetTableCheck();
        $this->artisan('tasks:import-skills')->assertSuccessful();
        Cache::flush();

        // grade_num у учителя: дашборд ученика роутится по классу, и без него
        // «/» уводит в ВПР, где листа «База заданий» нет вовсе.
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
        ]);
        $this->student = User::factory()->create([
            'role' => 'student',
            'grade_num' => 9,
            'onboarding_completed_at' => now(),
            'telegram_chat_id' => 245710727,
        ]);
        TeacherStudent::create([
            'teacher_id' => $this->teacher->id,
            'student_id' => $this->student->id,
            'source' => 'manual',
        ]);
    }

    private function url(string $path = '/skills'): string
    {
        return 'https://student.' . config('app.base_domain') . $path;
    }

    public function test_teacher_sees_the_list_of_skills(): void
    {
        $response = $this->actingAs($this->teacher)->get($this->url());

        $response->assertOk();
        // Оба навыка — переключателем наверху, первый раскрыт по умолчанию.
        $response->assertSee('Десятичные дроби');
        $response->assertSee('Сокращение дробей');
        $response->assertSee('Сложение, вычитание и умножение');
    }

    public function test_skill_with_grades_shows_class_and_levels(): void
    {
        $response = $this->actingAs($this->teacher)->get($this->url('/skills?topic=02'));

        $response->assertOk();
        $response->assertSee('8 класс');
        $response->assertSee('Уровень 1 — одночлены и степени');
        $response->assertSee('Уровень 2 — общий множитель и формулы');
        $response->assertSee('Уровень 3 — знаки, группировка, кубы');
    }

    public function test_chosen_skill_shows_its_examples_and_answers(): void
    {
        $response = $this->actingAs($this->teacher)->get($this->url('/skills?topic=02'));

        $response->assertOk();
        $response->assertSee('\dfrac', false);
        $response->assertSee('Ответ:');
        // 300 примеров темы приехали на страницу целиком.
        $this->assertSame(300, substr_count($response->getContent(), 'class="answer-value"'));
    }

    public function test_unknown_skill_falls_back_to_the_first(): void
    {
        $response = $this->actingAs($this->teacher)->get($this->url('/skills?topic=99'));

        $response->assertOk();
        $response->assertSee('Десятичные дроби');
    }

    public function test_student_cannot_open_the_skills_bank(): void
    {
        $this->actingAs($this->student)->get($this->url())->assertForbidden();
    }

    public function test_entry_button_is_teacher_only_everywhere_in_the_bank(): void
    {
        // ОГЭ: лист «База заданий» на главной ученика.
        $teacherHome = $this->actingAs($this->teacher)->get($this->url('/'));
        $teacherHome->assertOk()->assertSee('Скиллы');

        $studentHome = $this->actingAs($this->student)->get($this->url('/'));
        $studentHome->assertOk()->assertDontSee('Скиллы');

        // Страницы самих банков: ОГЭ, ЕГЭ и ВПР — вход один и тот же.
        // В ВПР и базе ЕГЭ листа выбора нет вовсе, плитка ведёт прямо сюда,
        // поэтому на странице банка кнопка обязана быть.
        foreach (['/tasks-part1', '/part2', '/ege-app/tasks?level=prof&part=1', '/vpr/tasks?grade=6'] as $path) {
            $this->actingAs($this->teacher)->get($this->url($path))
                ->assertOk()->assertSee('Скиллы');
        }

        $this->actingAs($this->student)->get($this->url('/tasks-part1'))
            ->assertOk()->assertDontSee('Скиллы');
    }
}
