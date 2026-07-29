<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Банк заданий в PWA должен показывать задания ФИПИ.
 *
 * Экраны собирались под прежний формат: условие лежало в `text` или
 * `expression`, и задача без них молча отбрасывалась. Банк ФИПИ несёт
 * условие готовой разметкой (`html`) — без этих правок разделы «1я часть»
 * и «2я часть» оказывались пустыми.
 */
class PwaFipiTaskBankTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        if (!file_exists(storage_path('app/imports/bank_katex.json'))) {
            $this->markTestSkipped('нет выгрузки банка ФИПИ');
        }
        Artisan::call('tasks:import-fipi');

        // Без отметки об онбординге middleware уводит на /onboarding,
        // и до экрана банка дело не доходит.
        $this->student = User::factory()->create([
            'role' => 'student',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_part_one_shows_fipi_tasks(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '16']));

        $response->assertOk();
        $response->assertViewHas('taskCount', fn ($count) => $count > 300);
    }

    public function test_practical_block_is_reachable(): void
    {
        // Заданий 1–5 в банке Паломатики не было вовсе: раздел появился
        // вместе с ФИПИ, и список тем экрана начинался с шестой.
        $response = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '01']));

        $response->assertOk();
        $response->assertViewHas('selectedTopic', '01');
        $response->assertViewHas('taskCount', fn ($count) => $count > 0);
    }

    public function test_part_two_shows_fipi_tasks_including_graphs(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('pwa.student.part2', ['topic' => '22']));

        $response->assertOk();
        $response->assertViewHas('zadaniya', fn ($zadaniya) => collect($zadaniya)
            ->sum(fn ($z) => count($z['tasks'] ?? [])) > 0);
    }

    public function test_drawings_get_an_explicit_size(): void
    {
        // В PWA нет Tailwind (он в head-config, который сюда не подключается),
        // поэтому классы `max-w-[350px]` на инлайновых SVG не работают. Без
        // собственных правил чертёж схлопывается в нулевую высоту, и на
        // экране остаётся условие без рисунка.
        $html = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '15']))
            ->getContent();

        $this->assertStringContainsString('<svg', $html);
        $this->assertMatchesRegularExpression(
            '/\.fipi-html svg\s*\{[^}]*width:\s*100%/',
            $html,
            'у чертежей нет собственного правила ширины'
        );
    }

    public function test_condition_markup_is_not_escaped(): void
    {
        // Если разметку экранировать, ученик увидит теги и доллары вместо
        // формул и чертежей.
        $html = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '16']))
            ->getContent();

        $this->assertStringContainsString('<svg', $html, 'чертежи не отрисовались');
        $this->assertStringNotContainsString('&lt;svg', $html, 'разметка условия экранирована');
    }
}
