<?php

namespace Tests\Feature;

use App\Services\LessonTaskPickerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Выбор заданий 9 класса при создании урока.
 *
 * Picker отбирал задачи по типу и требовал непустое условие в
 * `expression`/`prompt`/`question`/`text`. У банка ФИПИ тип `fipi`, а условие
 * лежит в `html` — учитель видел пустой список и не мог собрать урок.
 */
class LessonPickerShowsFipiTasksTest extends TestCase
{
    use RefreshDatabase;

    private LessonTaskPickerService $picker;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        if (!file_exists(storage_path('app/imports/bank_katex.json'))) {
            $this->markTestSkipped('нет выгрузки банка ФИПИ');
        }
        Artisan::call('tasks:import-fipi');

        $this->picker = app(LessonTaskPickerService::class);
    }

    /**
     * @dataProvider topics
     */
    public function test_topic_offers_tasks(string $topic, string $section, int $expected): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => $topic], $section);

        $this->assertCount($expected, $tasks, "тема {$topic}: picker вернул не то количество задач");
        foreach ($tasks as $task) {
            $this->assertNotSame('', trim($task['expression']),
                "тема {$topic}: задача без текста условия не даст выбрать её на урок");
        }
    }

    /** @return array<string, array{0:string,1:string,2:int}> */
    public static function topics(): array
    {
        return [
            'координатная прямая' => ['07', 'part1', 171],
            'окружность' => ['16', 'part1', 322],
            'высказывания' => ['19', 'part1', 150],
            'текстовые задачи (ч.2)' => ['21', 'part2', 190],
            // Доказательства идут без эталонного ответа, но на урок попадают:
            // учитель видит ответ ученика без автопроверки.
            'доказательства (ч.2)' => ['24', 'part2', 60],
        ];
    }

    public function test_drawing_is_offered_as_preview(): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => '16'], 'part1');

        $withDrawing = array_filter($tasks, static fn (array $t) => $t['image_svg'] !== '');
        $this->assertNotEmpty($withDrawing, 'у геометрии не оказалось превью-чертежа');
    }

    public function test_condition_preview_is_plain_text(): void
    {
        // В списке выбора нужен короткий текст, а не разметка с тегами.
        $tasks = $this->picker->tasks('oge', ['topic_id' => '16'], 'part1');

        $this->assertStringNotContainsString('<p>', $tasks[0]['expression']);
        $this->assertStringNotContainsString('<svg', $tasks[0]['expression']);
    }

    public function test_topic_16_groups_follow_the_pedagogical_order(): void
    {
        $tasks = $this->picker->tasks('oge', ['topic_id' => '16'], 'part1');
        $labels = array_values(array_unique(array_column($tasks, 'group_label')));

        $this->assertSame([
            '№1 · Центральный и вписанный углы: вписанный угол вдвое меньше',
            '№2 · Два диаметра: связь центрального и вписанного углов',
            '№3 · Угол, опирающийся на диаметр: 90°',
            '№4 · Две касательные: углы и радиусы',
            '№5 · Центр окружности лежит на стороне треугольника: найти угол',
            '№6 · Противоположные углы вписанного четырёхугольника',
            '№7 · Углы при параллельных основаниях трапеции',
            '№8 · Вписанный четырёхугольник: равные вписанные углы и сумма 180°',
            '№9 · Квадрат: найти радиус по стороне',
            '№10 · Квадрат: найти площадь по радиусу',
            '№11 · Трапеция: высота равна диаметру вписанной окружности',
            '№12 · Описанный четырёхугольник: суммы противоположных сторон',
            '№13 · Квадрат: найти диагональ по радиусу вписанной окружности',
            '№14 · Площадь треугольника по формуле S = pr',
            '№15 · Равносторонний треугольник и вписанная окружность: сторона ↔ радиус',
            '№16 · Ромб: радиус через диагональ и тангенс угла',
            '№17 · Прямоугольный треугольник: R = c / 2',
            '№18 · Квадрат и описанная окружность: сторона ↔ радиус',
            '№19 · Равносторонний треугольник и описанная окружность: сторона ↔ радиус',
            '№20 · Центр на гипотенузе: найти сторону по теореме Пифагора',
            '№21 · Расширенная теорема синусов',
            '№22 · Прямоугольник: площадь через диагональ и синус угла',
            '№23 · Квадрат и окружность с центром на середине стороны: теорема Пифагора',
        ], $labels);
    }
}
