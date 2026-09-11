<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\TaskBankRepository;
use App\Services\TaskBankResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Задание ЕГЭ, добавленное в урок, приезжает вместе с рисунками.
 *
 * У банка ЕГЭ чертёж — растр внутри разметки условия, а не отдельное поле и
 * не инлайновый SVG, как в ОГЭ. Разбор условия вырезал все теги, поэтому
 * чертёж пропадал молча, а обозначения внутри предложения («SABCD»,
 * «AM = 2» — тоже растры) оставляли в тексте дыры.
 */
class EgeLessonTaskImagesTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(string $html, string $topic = '01'): void
    {
        TaskTopic::create([
            'bank' => 'ege', 'grade' => null, 'topic' => $topic,
            'payload' => ['topic_id' => $topic, 'meta' => ['title' => 'Тема']],
        ]);
        $group = TaskGroup::create([
            'bank' => 'ege', 'grade' => null, 'topic' => $topic,
            'block_number' => 1, 'block_title' => 'ФИПИ', 'zadanie_number' => 1,
            'position' => 0, 'instruction' => 'Задание', 'type' => 'fipi',
            'payload' => ['instruction' => 'Задание', 'type' => 'fipi', 'status' => 'production'],
            'status' => 'production', 'source' => 'fipi',
        ]);
        Task::create([
            'task_group_id' => $group->id, 'position' => 0, 'type' => 'fipi',
            'payload' => ['id' => 1, 'status' => 'production', 'answer' => '7', 'html' => $html],
            'answer' => '7', 'answer_src' => 'codex', 'status' => 'production',
            'source' => 'fipi', 'fipi_guid' => str_pad($topic, 32, 'A'),
        ]);
    }

    private function resolve(string $topic = '01'): array
    {
        Cache::flush();
        TaskBankRepository::forgetTableCheck();

        return app(TaskBankResolver::class)->resolve('ege', [
            'topic_id' => $topic, 'zadanie_number' => 1, 'task_id' => 1,
        ]);
    }

    public function test_drawing_travels_as_a_picture(): void
    {
        $this->makeTask('<p>Четырёхугольник $ABCD$ вписан в окружность.'
            . '<img class="fipi-figure" src="/ege-bank/img/AAA/pic.png" alt="рисунок"></p>');

        $resolved = $this->resolve();

        $this->assertSame('/ege-bank/img/AAA/pic.png', $resolved['image_url'] ?? null,
            'без этого урок показывал одно условие: чертёж вырезался вместе с тегами');
        $this->assertStringContainsString('Четырёхугольник', $resolved['expression']);
    }

    public function test_condition_travels_with_its_markup(): void
    {
        // Соответствие «графики ↔ характеристики»: четыре чертежа-варианта.
        // Плоское условие показывало ученику один график из четырёх — первый
        // `fipi-figure` уезжал в image_url, остальные вырезались с тегами.
        $html = '<table><tr><td><b>А)</b></td><td><img class="fipi-figure" src="/ege-bank/img-base/G/a.png" alt="рисунок"></td>'
            . '<td><b>Б)</b></td><td><img class="fipi-figure" src="/ege-bank/img-base/G/b.png" alt="рисунок"></td></tr></table>'
            . '<p>1) функция возрастает на отрезке $[-1;1]$</p>';
        $this->makeTask($html, '07');

        $resolved = $this->resolve('07');

        $this->assertSame($html, $resolved['condition_html']);
        $this->assertSame('/ege-bank/img-base/G/a.png', $resolved['image_url'],
            'плоские поля остаются для уроков и подписей, собранных до появления разметки');
    }

    public function test_condition_markup_is_kept_from_the_student(): void
    {
        // Ученику `raw` не отдаётся (там ответ) — разметка лежит отдельным полем.
        $this->makeTask('<p>Условие <img class="fipi-inline" src="/ege-bank/img/B/s.png" alt="рисунок">.</p>', '14');

        $resolved = $this->resolve('14');
        unset($resolved['answer'], $resolved['raw']);

        $this->assertArrayHasKey('condition_html', $resolved);
    }

    public function test_inline_labels_stay_in_the_condition(): void
    {
        $this->makeTask('<p>В пирамиде <img class="fipi-inline" src="/ege-bank/img/B/s.png" alt="рисунок">'
            . ' сторона <img class="fipi-inline" src="/ege-bank/img/B/ab.png" alt="рисунок"> равна 8.</p>', '14');

        $resolved = $this->resolve('14');

        // Вырезанные, обозначения оставляли дыры: «В пирамиде сторона равна 8».
        $this->assertSame(2, substr_count($resolved['expression'], '<img'),
            'обозначения набраны растрами и должны остаться в тексте');
        $this->assertStringContainsString('fipi-inline', $resolved['expression']);
        $this->assertArrayNotHasKey('image_url', $resolved,
            'обозначение внутри предложения — не иллюстрация к задаче');
    }
}
