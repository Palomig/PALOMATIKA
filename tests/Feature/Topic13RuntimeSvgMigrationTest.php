<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13RuntimeSvgMigrationTest extends TestCase
{
    public function test_topic_13_block1_zadaniya_10_to_13_have_correct_visual_format(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');

        foreach ([9, 10, 11, 12] as $zadanieIndex) {
            $zadanie = $blocks[0]['zadaniya'][$zadanieIndex] ?? null;
            $this->assertIsArray($zadanie);
            $zadanieNumber = (int) ($zadanie['number'] ?? 0);

            foreach ($zadanie['tasks'] ?? [] as $task) {
                if ($zadanieNumber === 10) {
                    // Z10 uses semantic graph_options (no image field)
                    $this->assertArrayHasKey('graph_options', $task);
                    $this->assertCount(4, $task['graph_options']);
                    $this->assertArrayNotHasKey('image', $task);
                    continue;
                }

                if ($zadanieNumber === 11) {
                    $this->assertArrayHasKey('svg', $task);
                    $this->assertIsString($task['svg'] ?? null);
                    $this->assertStringContainsString('data-runtime-svg="topic13-b1-z11-prompt-', (string) ($task['svg'] ?? ''));
                    $this->assertArrayNotHasKey('image', $task);
                    $this->assertArrayNotHasKey('graph_options', $task);
                    $this->assertCount(4, $task['options'] ?? []);
                    continue;
                }

                if ($zadanieNumber === 12) {
                    $this->assertArrayHasKey('svg', $task);
                    $this->assertIsString($task['svg'] ?? null);
                    $this->assertStringContainsString('data-runtime-svg="topic13-b1-z12-prompt-', (string) ($task['svg'] ?? ''));
                    $this->assertArrayNotHasKey('image', $task);
                    continue;
                }

                if ($zadanieNumber === 13) {
                    $this->assertArrayHasKey('graph_options', $task);
                    $this->assertCount(4, $task['graph_options']);
                    $this->assertArrayNotHasKey('image', $task);
                    continue;
                }
            }
        }
    }

    public function test_runtime_choice_view_uses_single_semantic_svg_prompt_for_topic_13_z11_and_text_options(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][10] ?? null; // Z11

        $this->assertIsArray($zadanie);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $view->assertDontSee('images/tasks/13/img-035.png');
        $view->assertSee('data-runtime-svg="topic13-b1-z11-prompt-', false);
        $view->assertDontSee('<img src=', false);
        $view->assertDontSee('data-runtime-svg="topic13-b1-z11-option"', false);
        $view->assertSee('x² - 49 ≤ 0');
    }

    public function test_topic_13_z10_tasks_receive_four_graph_options_with_prebaked_svg(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][9] ?? null; // Z10

        $this->assertIsArray($zadanie);

        foreach ($zadanie['tasks'] ?? [] as $task) {
            $this->assertArrayHasKey('graph_options', $task);
            $this->assertIsArray($task['graph_options']);
            $this->assertCount(4, $task['graph_options']);
            $this->assertArrayNotHasKey('svg', $task, 'Z10 should not have a single solved SVG');

            foreach ($task['graph_options'] as $index => $option) {
                $this->assertSame($index + 1, $option['index'] ?? null);
                $this->assertIsString($option['svg'] ?? null);
                $this->assertStringContainsString('<svg', (string) ($option['svg'] ?? ''));
                $this->assertStringContainsString('viewBox="0 0 420 60"', (string) ($option['svg'] ?? ''));
            }
        }
    }

    public function test_topic_13_z10_view_renders_compact_option_panels(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][9] ?? null; // Z10
        $this->assertIsArray($zadanie);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;

        // 9 tasks × 4 options = 36 option panels
        $this->assertSame(36, substr_count($html, 'data-z10-option-panel="'));
        $this->assertStringContainsString('data-graph-options="topic13-z10"', $html);
        $this->assertStringContainsString('viewBox="0 0 420 60"', $html);
        $this->assertStringNotContainsString('<pattern ', $html);
    }

    public function test_topic_13_z13_tasks_receive_four_graph_options_with_svg_and_answer_mapping(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][12] ?? null; // Z13

        $this->assertIsArray($zadanie);
        $this->assertSame(13, (int) ($zadanie['number'] ?? 0));

        foreach ($zadanie['tasks'] ?? [] as $task) {
            $this->assertArrayHasKey('graph_options', $task);
            $this->assertIsArray($task['graph_options']);
            $this->assertCount(4, $task['graph_options']);

            $answer = (int) ($task['answer'] ?? 0);
            $this->assertTrue($answer >= 1 && $answer <= 4, 'Answer index must be between 1 and 4');
            $this->assertArrayHasKey($answer - 1, $task['graph_options']);

            foreach ($task['graph_options'] as $index => $option) {
                $this->assertSame($index + 1, (int) ($option['index'] ?? 0));
                $this->assertIsString($option['svg'] ?? null);
                $this->assertStringContainsString('<svg', (string) ($option['svg'] ?? ''));
                $this->assertStringContainsString('viewBox="0 0 420 60"', (string) ($option['svg'] ?? ''));
            }

            $correctOption = $task['graph_options'][$answer - 1] ?? [];
            $this->assertNotEmpty($correctOption['text'] ?? null, 'Correct option text must not be empty');
        }
    }

    public function test_topic_13_z13_view_renders_svg_option_panels_for_every_task(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][12] ?? null; // Z13
        $this->assertIsArray($zadanie);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $html = (string) $view;

        // 8 tasks × 4 options = 32 option panels
        $this->assertSame(32, substr_count($html, 'data-z10-option-panel="'));
        $this->assertStringContainsString('data-graph-options="topic13-z10"', $html);
        $this->assertStringContainsString('viewBox="0 0 420 60"', $html);
    }

    public function test_topic_13_z13_task5_uses_minus_six_sevenths_semantics_and_stacked_fraction_labels(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][12] ?? null; // Z13
        $this->assertIsArray($zadanie);

        $task5 = null;
        foreach (($zadanie['tasks'] ?? []) as $task) {
            if ((int) ($task['id'] ?? 0) === 5) {
                $task5 = $task;
                break;
            }
        }

        $this->assertIsArray($task5);
        $options = $task5['graph_options'] ?? null;
        $this->assertIsArray($options);
        $this->assertCount(4, $options);

        $this->assertSame('[-6/7; +∞)', (string) ($options[0]['text'] ?? ''));
        $this->assertSame('(-∞; -6/7]', (string) ($options[1]['text'] ?? ''));
        $this->assertSame('[6/7; +∞)', (string) ($options[2]['text'] ?? ''));
        $this->assertSame('[-6/7; 6/7]', (string) ($options[3]['text'] ?? ''));

        foreach ($options as $option) {
            $svg = (string) ($option['svg'] ?? '');
            $this->assertStringContainsString('data-label-format="stacked-fraction"', $svg);
            $this->assertStringContainsString('data-fraction="6/7"', $svg);
            $this->assertStringContainsString('font-size="15"', $svg);
            $this->assertStringContainsString('y="47"', $svg);
            $this->assertStringContainsString('y="61"', $svg);
            $this->assertStringNotContainsString('font-size="14"', $svg);
            $this->assertStringNotContainsString('y="45"', $svg);
            $this->assertStringNotContainsString('y="59"', $svg);
            $this->assertStringNotContainsString('-0.857', $svg);
            $this->assertStringNotContainsString('−0,857', $svg);
        }
        $allSvgs = implode("\n", array_map(static fn (array $option): string => (string) ($option['svg'] ?? ''), $options));
        $this->assertStringContainsString('font-size="18"', $allSvgs);
        $this->assertStringNotContainsString('font-size="17"', $allSvgs);

        // Focused semantic checks for exact task-5 interval set.
        $this->assertSame(1, substr_count((string) $options[0]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(1, substr_count((string) $options[0]['svg'], '>−</text>'));
        $this->assertSame(0, substr_count((string) $options[0]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        $this->assertSame(1, substr_count((string) $options[1]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(1, substr_count((string) $options[1]['svg'], '>−</text>'));
        $this->assertSame(0, substr_count((string) $options[1]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        $this->assertSame(1, substr_count((string) $options[2]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(0, substr_count((string) $options[2]['svg'], '>−</text>'));
        $this->assertSame(0, substr_count((string) $options[2]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        $this->assertSame(2, substr_count((string) $options[3]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(1, substr_count((string) $options[3]['svg'], '>−</text>'));
        $this->assertSame(1, substr_count((string) $options[3]['svg'], '<clipPath '));
        $this->assertSame(0, substr_count((string) $options[3]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        // Rotation must preserve correctness mapping for task id=5.
        $this->assertSame('2', (string) ($task5['answer'] ?? ''));
    }

    public function test_topic_13_z13_task7_uses_four_ninths_fraction_labels_and_interval_texts(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][12] ?? null; // Z13
        $this->assertIsArray($zadanie);

        $task7 = null;
        foreach (($zadanie['tasks'] ?? []) as $task) {
            if ((int) ($task['id'] ?? 0) === 7) {
                $task7 = $task;
                break;
            }
        }

        $this->assertIsArray($task7);
        $options = $task7['graph_options'] ?? null;
        $this->assertIsArray($options);
        $this->assertCount(4, $options);

        $this->assertSame('[4/9; +∞)', (string) ($options[0]['text'] ?? ''));
        $this->assertSame('[-4/9; +∞)', (string) ($options[1]['text'] ?? ''));
        $this->assertSame('[-4/9; 4/9]', (string) ($options[2]['text'] ?? ''));
        $this->assertSame('(-∞; -4/9] ∪ [4/9; +∞)', (string) ($options[3]['text'] ?? ''));

        foreach ($options as $option) {
            $svg = (string) ($option['svg'] ?? '');
            $this->assertStringContainsString('data-label-format="stacked-fraction"', $svg);
            $this->assertStringContainsString('data-fraction="4/9"', $svg);
            $this->assertStringContainsString('font-size="15"', $svg);
            $this->assertStringContainsString('y="47"', $svg);
            $this->assertStringContainsString('y="61"', $svg);
            $this->assertStringNotContainsString('font-size="14"', $svg);
            $this->assertStringNotContainsString('y="45"', $svg);
            $this->assertStringNotContainsString('y="59"', $svg);
            $this->assertStringNotContainsString('0.444', $svg);
            $this->assertStringNotContainsString('0,444', $svg);
        }
        $allSvgs = implode("\n", array_map(static fn (array $option): string => (string) ($option['svg'] ?? ''), $options));
        $this->assertStringContainsString('font-size="18"', $allSvgs);
        $this->assertStringNotContainsString('font-size="17"', $allSvgs);

        // Focused semantic checks for 4/9 and -4/9 option geometry.
        $this->assertSame(1, substr_count((string) $options[0]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(0, substr_count((string) $options[0]['svg'], '>−</text>'));
        $this->assertSame(0, substr_count((string) $options[0]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        $this->assertSame(1, substr_count((string) $options[1]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(1, substr_count((string) $options[1]['svg'], '>−</text>'));
        $this->assertSame(0, substr_count((string) $options[1]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        $this->assertSame(2, substr_count((string) $options[2]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(1, substr_count((string) $options[2]['svg'], '>−</text>'));
        $this->assertSame(1, substr_count((string) $options[2]['svg'], '<clipPath '));
        $this->assertSame(0, substr_count((string) $options[2]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        $this->assertSame(2, substr_count((string) $options[3]['svg'], 'data-label-format="stacked-fraction"'));
        $this->assertSame(1, substr_count((string) $options[3]['svg'], '>−</text>'));
        $this->assertSame(2, substr_count((string) $options[3]['svg'], '<clipPath '));
        $this->assertSame(0, substr_count((string) $options[3]['svg'], 'fill="#0d1b2a" stroke="#4d9fdc"'));

        // Rotation must preserve correctness mapping for task id=7.
        $this->assertSame('3', (string) ($task7['answer'] ?? ''));
    }

    public function test_topic_13_svg_cards_use_dark_wrapper_for_prompt_and_option_svg_blocks(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');

        $z10 = $blocks[0]['zadaniya'][9] ?? null;
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z10);
        $this->assertIsArray($z11);

        $z10View = $this->view('tasks.types.choice', [
            'zadanie' => $z10,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);
        $z10Html = (string) $z10View;
        $this->assertSame(36, substr_count($z10Html, 'topic13-svg-card'));
        $this->assertSame(0, substr_count($z10Html, 'bg-white rounded p-1 overflow-hidden'));

        $z11View = $this->view('tasks.types.choice', [
            'zadanie' => $z11,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);
        $z11Html = (string) $z11View;
        $this->assertSame(8, substr_count($z11Html, 'topic13-svg-card'));
        $this->assertStringNotContainsString('bg-white', $z11Html);
    }

    public function test_topic_13_z9_remains_unmigrated_while_z7_is_runtime_svg_migrated(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z9 = $blocks[0]['zadaniya'][8] ?? null;
        $z7 = $blocks[0]['zadaniya'][6] ?? null;

        $this->assertIsArray($z9);
        $this->assertIsArray($z7);

        foreach ($z9['tasks'] ?? [] as $task) {
            $this->assertArrayNotHasKey('svg', $task);
            $this->assertArrayNotHasKey('image', $task);
        }

        foreach ($z7['tasks'] ?? [] as $task) {
            $this->assertArrayHasKey('svg', $task);
            $this->assertArrayNotHasKey('image', $task);
        }

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $z7,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $view->assertDontSee('images/tasks/13/img-018.png');
        $view->assertSee('data-runtime-svg="topic13-b1-z7-prompt-', false);
    }
}
