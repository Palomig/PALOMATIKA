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
                    // Z10 uses pre-baked SVG graph_options (no image field)
                    $this->assertArrayHasKey('graph_options', $task);
                    $this->assertCount(4, $task['graph_options']);
                    $this->assertArrayNotHasKey('image', $task);
                    continue;
                }

                if ($zadanieNumber === 11) {
                    $this->assertArrayHasKey('image', $task);
                    $this->assertArrayNotHasKey('graph_options', $task);
                    $this->assertArrayNotHasKey('svg', $task);
                    $this->assertCount(4, $task['options'] ?? []);
                    continue;
                }

                // Z12-13 still use PNG images
                $this->assertNotEmpty($task['image'] ?? null, "PNG fallback should remain in data for Z{$zadanieNumber}");
            }
        }
    }

    public function test_runtime_choice_view_keeps_single_png_prompt_for_topic_13_z11_and_text_options(): void
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

        $view->assertSee('images/tasks/13/img-035.png');
        $view->assertDontSee('data-runtime-svg="topic13-b1-z11-option"', false);
        $view->assertSee('1) x² - 49 ≤ 0');
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
                $this->assertStringContainsString('viewBox="0 0 250 44"', (string) ($option['svg'] ?? ''));
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
        // SVG pattern IDs use z10t* prefix
        $this->assertStringContainsString('z10t1a', $html);
    }

    public function test_neighboring_topic_13_zadaniya_remain_unmigrated(): void
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

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $z7,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => true,
        ]);

        $view->assertSee('images/tasks/13/img-018.png');
        $view->assertDontSee('<svg', false);
    }
}
