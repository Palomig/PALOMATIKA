<?php

namespace Tests\Feature\Pwa;

use App\Services\MiniAppTaskCanonicalizer;
use App\Services\MiniAppTaskSanitizer;
use App\Services\TaskDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Мини-ОГЭ и полный вариант должны показывать условие задания.
 *
 * Экран варианта берёт задачу не из банка напрямую, а через
 * `MiniAppTaskCanonicalizer`, который вытаскивал `text`, `expression` и `svg`
 * из вложенного `task`. У банка ФИПИ условие лежит в `html`, поэтому задание
 * приходило пустым: только поле ответа или четыре пустые кнопки «А Б В Г».
 */
class PwaVariantShowsFipiTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        if (!file_exists(storage_path('app/imports/bank_katex.json'))) {
            $this->markTestSkipped('нет выгрузки банка ФИПИ');
        }
        Artisan::call('tasks:import-fipi');
    }

    public function test_random_task_reaches_the_screen_with_a_condition(): void
    {
        $canonicalizer = new MiniAppTaskCanonicalizer();
        $sanitizer = new MiniAppTaskSanitizer();
        $service = new TaskDataService();

        foreach (['07', '15', '16', '19'] as $topic) {
            $tasks = $service->getRandomTasks($topic, 3, 'production');
            $this->assertNotEmpty($tasks, "тема {$topic}: не вернулось ни одного задания");

            foreach ($tasks as $raw) {
                $task = $sanitizer->sanitize($canonicalizer->normalizeForUi($raw));

                $condition = trim((string) ($task['text'] ?? ''))
                    . trim((string) ($task['expression'] ?? ''))
                    . trim((string) ($task['svg'] ?? ''));

                $this->assertNotSame('', $condition,
                    "тема {$topic}: задание пришло без условия");
            }
        }
    }

    public function test_multi_answer_statements_are_marked(): void
    {
        // Задание 19 бывает двух видов: «Какое из утверждений является
        // истинным» — один ответ, «Какие … являются» — несколько. Без
        // признака ученик мог выбрать только один вариант и терял балл.
        $canonicalizer = new MiniAppTaskCanonicalizer();
        $tasks = (new TaskDataService())->getRandomTasks('19', 40, 'production');

        $multi = $single = 0;
        foreach ($tasks as $raw) {
            $task = $canonicalizer->normalizeForUi($raw);
            $answer = (string) ($raw['task']['answer'] ?? '');
            if ($answer === '') {
                continue;
            }

            if (strlen($answer) > 1) {
                $multi++;
                $this->assertTrue($task['multi_select'],
                    "ответ «{$answer}» требует нескольких вариантов, но задание помечено как одиночное");
            } else {
                $single++;
                $this->assertFalse($task['multi_select'],
                    "ответ «{$answer}» одиночный, а задание помечено как множественное");
            }
        }

        $this->assertGreaterThan(0, $multi, 'не нашлось заданий с несколькими верными');
        $this->assertGreaterThan(0, $single, 'не нашлось заданий с одним верным');
    }

    public function test_heading_is_not_duplicated_above_the_condition(): void
    {
        // Заголовком задания служит его формулировка, и она же приходит
        // в условии — над текстом она повторялась дословно.
        $task = (new MiniAppTaskCanonicalizer())->normalizeForUi(
            (new TaskDataService())->getRandomTasks('16', 1, 'production')[0]
        );

        $this->assertTrue($task['html_condition'] ?? false,
            'условие из разметки не помечено — заголовок останется дублем');
    }

    public function test_fraction_options_keep_their_braces(): void
    {
        // latexToUnicode выбрасывает фигурные скобки: $\dfrac{45}{19}$
        // превращался в $\dfrac4519$, и KaTeX рисовал «4/5» и отдельно «19».
        $canonicalizer = new MiniAppTaskCanonicalizer();

        $healed = $canonicalizer->normalizeForUi([
            'type' => 'fipi',
            'task' => ['html' => '<p>условие</p>', 'answer' => '1'],
            // Подписи уже испорчены прошлой нормализацией — так выглядит
            // сохранённый вариант, собранный до правки.
            'options' => [[
                'n' => 1,
                'html' => '<p>$\dfrac{45}{19}$</p>',
                'label' => '$\dfrac4519$',
                'text' => '$\dfrac4519$',
                'value' => 'x',
                'id' => 'a',
            ]],
        ]);

        $this->assertStringContainsString('\dfrac{45}{19}', $healed['options'][0]['label']);
        $this->assertSame('1', (string) $healed['options'][0]['id']);
    }

    public function test_options_are_not_empty_buttons(): void
    {
        $canonicalizer = new MiniAppTaskCanonicalizer();
        // Тема 7 — задания с вариантами ответа.
        $tasks = (new TaskDataService())->getRandomTasks('07', 8, 'production');

        $withOptions = 0;
        foreach ($tasks as $raw) {
            $task = $canonicalizer->normalizeForUi($raw);
            if (empty($task['options'])) {
                continue;
            }
            $withOptions++;
            foreach ($task['options'] as $option) {
                $this->assertNotSame('', trim((string) ($option['label'] ?? '')),
                    'вариант ответа пришёл пустым');
                // Ответы банка ФИПИ — номера; id варианта обязан быть числом,
                // иначе выбор ученика не совпадёт с эталоном.
                $this->assertMatchesRegularExpression('/^\d+$/', (string) $option['id']);
            }
        }

        $this->assertGreaterThan(0, $withOptions, 'не нашлось заданий с вариантами');
    }
}
