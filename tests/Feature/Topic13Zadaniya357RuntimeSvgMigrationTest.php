<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13Zadaniya357RuntimeSvgMigrationTest extends TestCase
{
    public function test_topic_13_zadanie_3_tasks_use_only_four_svg_options_without_prompt_svg(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][2] ?? null; // Z3

        $this->assertIsArray($zadanie);
        $this->assertSame(3, (int) ($zadanie['number'] ?? 0));

        foreach ($zadanie['tasks'] ?? [] as $task) {
            $this->assertArrayNotHasKey('svg', $task);
            $this->assertArrayHasKey('graph_options', $task);
            $this->assertIsArray($task['graph_options']);
            $this->assertCount(4, $task['graph_options']);
            $texts = array_map(static fn (array $option): string => (string) ($option['text'] ?? ''), $task['graph_options']);
            $this->assertContains(
                $this->normalizeAnswer((string) ($task['answer'] ?? '')),
                array_map(fn (string $v): string => $this->normalizeAnswer($v), $texts),
                'Answer mapping must point to one of 4 options'
            );
            $this->assertArrayNotHasKey('image', $task);
        }

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;
        $this->assertStringNotContainsString('data-runtime-svg="topic13-b1-z3-prompt-', $html);
        $this->assertStringNotContainsString('topic13-z3-prompt-svg-size', $html);
        $this->assertSame(36, substr_count($html, 'data-z10-option-panel="'));
        $this->assertStringNotContainsString('images/tasks/13/img-', $html);
        $this->assertStringNotContainsString('.png', $html);
    }

    public function test_topic_13_zadanie_5_tasks_use_only_four_svg_options_without_prompt_svg(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][4] ?? null; // Z5

        $this->assertIsArray($zadanie);
        $this->assertSame(5, (int) ($zadanie['number'] ?? 0));

        foreach ($zadanie['tasks'] ?? [] as $task) {
            $this->assertArrayNotHasKey('svg', $task);
            $this->assertArrayHasKey('graph_options', $task);
            $this->assertIsArray($task['graph_options']);
            $this->assertCount(4, $task['graph_options']);
            $texts = array_map(static fn (array $option): string => (string) ($option['text'] ?? ''), $task['graph_options']);
            $this->assertContains(
                $this->normalizeAnswer((string) ($task['answer'] ?? '')),
                array_map(fn (string $v): string => $this->normalizeAnswer($v), $texts),
                'Answer mapping must point to one of 4 options'
            );
            $this->assertArrayNotHasKey('image', $task);
        }

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;
        $this->assertStringNotContainsString('data-runtime-svg="topic13-b1-z5-prompt-', $html);
        $this->assertStringNotContainsString('topic13-z5-prompt-svg-size', $html);
        $this->assertSame(36, substr_count($html, 'data-z10-option-panel="'));
        $this->assertStringNotContainsString('images/tasks/13/img-', $html);
        $this->assertStringNotContainsString('.png', $html);
    }

    public function test_topic_13_zadanie_7_tasks_use_only_four_svg_options_without_prompt_svg(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][6] ?? null; // Z7

        $this->assertIsArray($zadanie);
        $this->assertSame(7, (int) ($zadanie['number'] ?? 0));

        foreach ($zadanie['tasks'] ?? [] as $task) {
            $this->assertArrayNotHasKey('svg', $task);
            $this->assertArrayHasKey('graph_options', $task);
            $this->assertIsArray($task['graph_options']);
            $this->assertCount(4, $task['graph_options']);
            $texts = array_map(static fn (array $option): string => (string) ($option['text'] ?? ''), $task['graph_options']);
            $this->assertContains(
                $this->normalizeAnswer((string) ($task['answer'] ?? '')),
                array_map(fn (string $v): string => $this->normalizeAnswer($v), $texts),
                'Answer mapping must point to one of 4 options'
            );
            $this->assertArrayNotHasKey('image', $task);
        }

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;
        $this->assertStringNotContainsString('data-runtime-svg="topic13-b1-z7-prompt-', $html);
        $this->assertStringNotContainsString('topic13-z7-prompt-svg-size', $html);
        $this->assertSame(36, substr_count($html, 'data-z10-option-panel="'));
        $this->assertStringNotContainsString('images/tasks/13/img-', $html);
        $this->assertStringNotContainsString('.png', $html);
    }

    private function normalizeAnswer(string $value): string
    {
        $normalized = trim(str_replace(['−', '–'], '-', $value));
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        return str_replace(',', '.', $normalized);
    }
}
