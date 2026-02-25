<?php

namespace Tests\Feature;

use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13Zadanie11SemanticSvgTest extends TestCase
{
    public function test_topic_13_block1_z11_uses_single_prompt_graphic_with_text_inequality_options(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;

        $this->assertIsArray($z11);
        $this->assertSame(11, (int) ($z11['number'] ?? 0));

        foreach ($z11['tasks'] ?? [] as $task) {
            $this->assertArrayHasKey('svg', $task);
            $this->assertIsString($task['svg'] ?? null);
            $this->assertStringStartsWith('<svg ', (string) ($task['svg'] ?? ''));
            $this->assertStringContainsString('data-runtime-svg="topic13-b1-z11-prompt-', (string) ($task['svg'] ?? ''));
            $this->assertArrayNotHasKey('image', $task);
            $this->assertArrayNotHasKey('graph_options', $task);
            $this->assertIsArray($task['options'] ?? null);
            $this->assertCount(4, $task['options']);
            $this->assertIsString($task['options'][0] ?? null);
            $this->assertStringContainsString('x', (string) ($task['options'][0] ?? ''));
        }
    }

    public function test_topic_13_block1_z11_answer_key_mapping_stays_unchanged(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $resolver = app(TaskAnswerResolver::class);
        foreach ($z11['tasks'] ?? [] as $task) {
            $this->assertSame('1', $resolver->resolveFromTaskAndZadanie($z11, is_array($task) ? $task : []));
        }
    }

    public function test_topic_13_block1_z11_view_renders_one_graphic_and_text_options_per_task(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $z11,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;

        $this->assertSame(8, substr_count($html, 'data-runtime-svg="topic13-b1-z11-prompt-'));
        $this->assertSame(0, substr_count($html, 'images/tasks/13/img-0'));
        $this->assertSame(0, substr_count($html, '<img src='));
        $this->assertSame(0, substr_count($html, 'data-z10-option-panel='));
        $this->assertSame(8, substr_count($html, 'topic13-z11-prompt-svg-size'));
        $this->assertStringContainsString('1) x² - 49 ≤ 0', $html);
        $this->assertStringContainsString('4) x² + 4 &lt; 0', $html);
    }

    public function test_topic_13_block1_z11_task3_svg_is_visible_non_empty_and_semantically_labeled(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $task3 = $z11['tasks'][2] ?? null;
        $task1 = $z11['tasks'][0] ?? null;
        $this->assertIsArray($task3);
        $this->assertIsArray($task1);

        $task3Svg = (string) ($task3['svg'] ?? '');
        $task1Svg = (string) ($task1['svg'] ?? '');

        // Task 3 is "all real numbers": axis + hatch must be visible on dark card.
        $this->assertStringContainsString('x1="14" y1="16" x2="286" y2="16"', $task3Svg);
        $this->assertStringContainsString('stroke="#c8dce8"', $task3Svg);
        $this->assertStringContainsString('fill="url(#hatch-', $task3Svg);

        // A bounded task must still keep endpoint labels/points.
        $this->assertStringContainsString('>−7</text>', $task1Svg);
        $this->assertStringContainsString('>7</text>', $task1Svg);
        $this->assertSame(2, substr_count($task1Svg, '<circle '));
    }
}
