<?php

namespace Tests\Feature;

use App\Services\OptionRenderModePolicy;
use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13Zadanie10Test extends TestCase
{
    private array $zadanie;
    private array $tasks;

    protected function setUp(): void
    {
        parent::setUp();

        $path = storage_path('app/tasks/topic_13.json');
        $data = json_decode((string) file_get_contents($path), true);
        $this->zadanie = $data['blocks'][0]['zadaniya'][9];
        $this->tasks = $this->zadanie['tasks'];
    }

    public function test_zadanie_10_has_exactly_9_tasks(): void
    {
        $this->assertSame(10, $this->zadanie['number']);
        $this->assertCount(9, $this->tasks);
    }

    public function test_each_task_has_exactly_4_graph_options(): void
    {
        foreach ($this->tasks as $i => $task) {
            $this->assertArrayHasKey('graph_options', $task, "Task {$task['id']} missing graph_options");
            $this->assertCount(4, $task['graph_options'], "Task {$task['id']} should have exactly 4 options");
        }
    }

    public function test_no_task_has_image_field(): void
    {
        foreach ($this->tasks as $task) {
            $this->assertArrayNotHasKey('image', $task, "Task {$task['id']} should not have image field");
        }
    }

    public function test_each_task_has_answer_field(): void
    {
        foreach ($this->tasks as $task) {
            $this->assertSame('1', $task['answer'], "Task {$task['id']} answer should be '1'");
        }
    }

    public function test_graph_option_structure(): void
    {
        foreach ($this->tasks as $task) {
            foreach ($task['graph_options'] as $j => $option) {
                $this->assertArrayHasKey('index', $option, "Option missing index in task {$task['id']}");
                $this->assertArrayHasKey('svg', $option, "Option missing svg in task {$task['id']}");
                $this->assertSame($j + 1, $option['index']);
            }
        }
    }

    public function test_svgs_are_valid_and_have_unique_pattern_ids(): void
    {
        $allPatternIds = [];

        foreach ($this->tasks as $task) {
            foreach ($task['graph_options'] as $option) {
                $svg = $option['svg'];
                $this->assertStringStartsWith('<svg ', $svg, "SVG must start with <svg tag");
                $this->assertStringContainsString('</svg>', $svg, "SVG must have closing tag");
                $this->assertStringContainsString('viewBox="0 0 250 44"', $svg);

                // Extract pattern IDs
                if (preg_match('/pattern id="([^"]+)"/', $svg, $m)) {
                    $pid = $m[1];
                    $this->assertNotContains($pid, $allPatternIds, "Duplicate pattern ID: {$pid}");
                    $allPatternIds[] = $pid;

                    // Verify the same ID is referenced in fill url
                    $this->assertStringContainsString("url(#{$pid})", $svg,
                        "Pattern {$pid} defined but not referenced");
                }
            }
        }
    }

    public function test_endpoint_semantics_match_inequality_sign(): void
    {
        // Tasks with strict inequalities (< or >) should have hollow circles (fill="white")
        // Tasks with non-strict (≤ or ≥) should have filled circles (fill="#3b82f6")
        $expectations = [
            1 => 'strict',      // 7x - x² < 0
            2 => 'nonstrict',   // 4x - x² ≤ 0
            3 => 'nonstrict',   // x - x² ≥ 0
            4 => 'strict',      // 6x - x² > 0
            5 => 'nonstrict',   // 7x - x² ≥ 0
            6 => 'strict',      // 2x - x² > 0
            7 => 'strict',      // 8x - x² < 0
            8 => 'nonstrict',   // 5x - x² ≥ 0
            9 => 'nonstrict',   // 3x - x² ≤ 0
        ];

        foreach ($this->tasks as $task) {
            $id = $task['id'];
            $correctSvg = $task['graph_options'][0]['svg'];
            $type = $expectations[$id];

            if ($type === 'strict') {
                $this->assertStringContainsString('fill="white"', $correctSvg,
                    "Task {$id}: strict inequality should have hollow (white) endpoints");
                $this->assertStringNotContainsString('fill="#3b82f6"/>', $correctSvg,
                    "Task {$id}: strict inequality should not have filled endpoints");
            } else {
                $this->assertStringContainsString('fill="#3b82f6"/>', $correctSvg,
                    "Task {$id}: non-strict inequality should have filled endpoints");
            }
        }
    }

    public function test_correct_option_boundary_labels(): void
    {
        // Each task has roots at 0 and a, correct option (index 1) should show both labels
        $roots = [
            1 => 7, 2 => 4, 3 => 1, 4 => 6, 5 => 7,
            6 => 2, 7 => 8, 8 => 5, 9 => 3,
        ];

        foreach ($this->tasks as $task) {
            $id = $task['id'];
            $svg = $task['graph_options'][0]['svg'];
            $a = $roots[$id];

            $this->assertStringContainsString('>0</text>', $svg,
                "Task {$id} correct option should show label '0'");
            $this->assertStringContainsString(">{$a}</text>", $svg,
                "Task {$id} correct option should show label '{$a}'");
        }
    }

    public function test_single_ray_options_show_only_one_label(): void
    {
        foreach ($this->tasks as $task) {
            // Options 3 and 4 are single-ray distractors
            for ($oi = 2; $oi <= 3; $oi++) {
                $svg = $task['graph_options'][$oi]['svg'];
                // Should have exactly one <text> element
                $textCount = substr_count($svg, '<text ');
                $this->assertSame(1, $textCount,
                    "Task {$task['id']} option " . ($oi + 1) . " should have exactly 1 boundary label, got {$textCount}");
            }
        }
    }

    public function test_answer_resolver_returns_1_for_all_tasks(): void
    {
        $resolver = app(TaskAnswerResolver::class);

        foreach ($this->tasks as $task) {
            $answer = $resolver->resolveFromTaskAndZadanie($this->zadanie, $task);
            $this->assertSame('1', $answer,
                "TaskAnswerResolver should return '1' for task {$task['id']}");
        }
    }

    public function test_choice_view_renders_graph_options_not_interval_svg(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');
        $zadanie = $blocks[0]['zadaniya'][9];

        $this->assertSame(10, $zadanie['number']);

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '13',
            'isVariant' => false,
        ]);

        // Should render graph_options SVGs (with pattern IDs like z10t*)
        $view->assertSee('data-graph-options="topic13-z10"', false);
        $view->assertSee('z10t1a', false);

        // Should NOT render the interval-line.blade.php partial
        $view->assertDontSee('hatch_', false);
    }
}
