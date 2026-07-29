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
}
