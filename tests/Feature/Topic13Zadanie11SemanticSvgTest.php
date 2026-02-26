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

    public function test_topic_13_block1_z11_answer_key_mapping_uses_explicit_answer_field(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $resolver = app(TaskAnswerResolver::class);
        foreach ($z11['tasks'] ?? [] as $task) {
            $taskData = is_array($task) ? $task : [];
            $expected = (string) ($taskData['answer'] ?? '');
            $this->assertNotSame('', $expected);
            $this->assertContains($expected, ['1', '2', '3', '4']);
            $this->assertSame($expected, $resolver->resolveFromTaskAndZadanie($z11, $taskData));
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
        $this->assertStringContainsString('x² - 49 ≤ 0', $html);
        $this->assertStringContainsString('x² + 4 &lt; 0', $html);
    }

    public function test_topic_13_block1_z11_task3_svg_matches_outer_closed_union_with_boundaries_minus8_and_8(): void
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

        // Task 3 must render x <= -8 OR x >= 8 with closed boundary points and labels.
        $this->assertStringContainsString('data-runtime-svg="topic13-b1-z11-prompt-3"', $task3Svg);
        $this->assertStringContainsString('>−8</text>', $task3Svg);
        $this->assertStringContainsString('>8</text>', $task3Svg);
        $this->assertSame(2, substr_count($task3Svg, '<circle '));
        $this->assertStringNotContainsString('fill="url(#hatch-', $task3Svg);

        // A bounded task must still keep endpoint labels/points.
        $this->assertStringContainsString('>−7</text>', $task1Svg);
        $this->assertStringContainsString('>7</text>', $task1Svg);
        $this->assertSame(2, substr_count($task1Svg, '<circle '));
    }

    public function test_topic_13_block1_z11_task5_and_task7_svg_use_closed_bounded_intervals_with_correct_boundaries(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $task5 = $z11['tasks'][4] ?? null;
        $task7 = $z11['tasks'][6] ?? null;
        $this->assertIsArray($task5);
        $this->assertIsArray($task7);

        $task5Svg = (string) ($task5['svg'] ?? '');
        $task7Svg = (string) ($task7['svg'] ?? '');

        // Task 5 must be the closed interval [-4; 4].
        $this->assertStringContainsString('>−4</text>', $task5Svg);
        $this->assertStringContainsString('>4</text>', $task5Svg);
        $this->assertSame(2, substr_count($task5Svg, '<circle '));
        $this->assertStringNotContainsString('fill="#ffffff"', $task5Svg);
        $this->assertStringNotContainsString('fill="#0d1b2a"', $task5Svg);

        // Task 7 must be the closed interval [-9; 9].
        $this->assertStringContainsString('>−9</text>', $task7Svg);
        $this->assertStringContainsString('>9</text>', $task7Svg);
        $this->assertSame(2, substr_count($task7Svg, '<circle '));
        $this->assertStringNotContainsString('fill="#ffffff"', $task7Svg);
        $this->assertStringNotContainsString('fill="#0d1b2a"', $task7Svg);
    }
}
