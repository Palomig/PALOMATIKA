<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use App\Support\OptionLabelFormatter;
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

    public function test_part_one_uses_a_number_only_group_header(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '06']))
            ->assertOk();

        $response->assertViewHas('zadaniya', static function (array $groups): bool {
            return isset($groups[0]['number'], $groups[0]['title'], $groups[0]['section'])
                && $groups[0]['number'] === 1;
        });

        $html = $response->getContent();
        $this->assertStringContainsString('class="spoiler-num">1</span>', $html);
        $this->assertStringNotContainsString('class="spoiler-num">01</span>', $html);
        $this->assertStringContainsString('class="spoiler-subtitle">40 заданий</span>', $html);
        $this->assertStringContainsString('class="spoiler-chevron">›</span>', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/spoiler-title[^>]*>\s*Задание\s+\d+/u',
            $html,
        );
    }

    public function test_topic_16_shows_pedagogical_sections_without_technical_fipi_heading(): void
    {
        $html = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '16']))
            ->assertOk()
            ->getContent();

        $headings = [
            'Углы в окружности',
            'Вписанные четырёхугольники',
            'Вписанная окружность',
            'Описанная окружность',
        ];
        $previous = -1;
        foreach ($headings as $heading) {
            $this->assertSame(1, substr_count($html, "bank-section-title\">{$heading}<"));
            $position = strpos($html, "bank-section-title\">{$heading}<");
            $this->assertGreaterThan($previous, $position);
            $previous = $position;
        }

        $topic15 = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '15']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('bank-section-title">ФИПИ<', $topic15);
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

    public function test_single_drawing_is_lifted_above_the_text(): void
    {
        // У банка ФИПИ условие и чертёж лежат в соседних ячейках таблицы,
        // и ячейка зажимает рисунок в узкую полоску. Единственный чертёж
        // выносим отдельным блоком над условием.
        $html = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '15']))
            ->getContent();

        $this->assertStringContainsString('fipi-drawing', $html, 'чертёж не вынесен');

        $drawing = strpos($html, 'class="fipi-drawing"');
        $condition = strpos($html, 'fipi-html', $drawing);
        $this->assertNotFalse($condition);
        $this->assertLessThan($condition, $drawing, 'чертёж оказался ниже условия');
    }

    public function test_multi_drawing_tasks_keep_their_layout(): void
    {
        // Тема 11 — соответствие: график привязан к своему номеру, и
        // выносить его из ячейки нельзя.
        $html = $this->actingAs($this->student)
            ->get(route('pwa.student.tasks-part1', ['topic' => '11']))
            ->getContent();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('class="fipi-drawing"', $html,
            'у заданий с несколькими чертежами разметку трогать нельзя');
    }

    public function test_answers_stay_numeric_for_fipi_options(): void
    {
        // В ОГЭ буквенных ответов не бывает. Варианты банка ФИПИ пронумерованы
        // полем `n` и без `id`, и форматтер подставлял букву по порядковому
        // номеру: ответ «3» показывался как «В».
        $fipi = [['n' => 1, 'html' => 'a'], ['n' => 2, 'html' => 'b'], ['n' => 3, 'html' => 'c']];

        $this->assertSame('3', OptionLabelFormatter::formatAnswer('3', $fipi));
        $this->assertSame('3', OptionLabelFormatter::optionLabel($fipi[2], 2));

        // Варианты Паломатики с буквенными id ведут себя как раньше.
        $pal = [['id' => 'a', 'label' => 'x'], ['id' => 'b', 'label' => 'y'], ['id' => 'c', 'label' => 'z']];
        $this->assertSame('В', OptionLabelFormatter::formatAnswer('c', $pal));
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
