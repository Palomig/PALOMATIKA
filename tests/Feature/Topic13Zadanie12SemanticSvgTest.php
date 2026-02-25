<?php

namespace Tests\Feature;

use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13Zadanie12SemanticSvgTest extends TestCase
{
    public function test_topic_13_block1_z12_runtime_uses_semantic_svg_without_png_field(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z12 = $blocks[0]['zadaniya'][11] ?? null;

        $this->assertIsArray($z12);
        $this->assertSame(12, (int) ($z12['number'] ?? 0));

        foreach ($z12['tasks'] ?? [] as $task) {
            $this->assertArrayHasKey('svg', $task);
            $this->assertIsString($task['svg'] ?? null);
            $this->assertStringStartsWith('<svg ', (string) ($task['svg'] ?? ''));
            $this->assertStringContainsString('semantic-runtime-svg', (string) ($task['svg'] ?? ''));
            $this->assertStringContainsString('data-runtime-svg="topic13-b1-z12-prompt-', (string) ($task['svg'] ?? ''));
            $this->assertArrayNotHasKey('image', $task);
            $this->assertArrayNotHasKey('graph_options', $task);
            $this->assertIsArray($task['options'] ?? null);
            $this->assertCount(4, $task['options']);
        }
    }

    public function test_topic_13_block1_z12_view_renders_svg_and_not_png_path(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z12 = $blocks[0]['zadaniya'][11] ?? null;

        $this->assertIsArray($z12);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $z12,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;

        $this->assertSame(8, substr_count($html, 'data-runtime-svg="topic13-b1-z12-prompt-'));
        $this->assertStringNotContainsString('images/tasks/13/img-043.png', $html);
        $this->assertSame(0, substr_count($html, '<img src='));
        $this->assertStringContainsString('1) x² - 5x ≤ 0', $html);
    }

    public function test_topic_13_block1_z12_answer_mapping_stays_unchanged(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z12 = $blocks[0]['zadaniya'][11] ?? null;

        $this->assertIsArray($z12);

        $resolver = app(TaskAnswerResolver::class);
        foreach ($z12['tasks'] ?? [] as $task) {
            $this->assertSame('1', $resolver->resolveFromTaskAndZadanie($z12, is_array($task) ? $task : []));
        }
    }
}
