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

        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $this->zadanie = $blocks[0]['zadaniya'][9];
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
            $answer = (string) ($task['answer'] ?? '');
            $this->assertContains($answer, ['1', '2', '3', '4'], "Task {$task['id']} answer should be a valid option index");
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

    public function test_svgs_are_valid_and_match_new_number_ray_viewbox(): void
    {
        foreach ($this->tasks as $task) {
            foreach ($task['graph_options'] as $option) {
                $svg = $option['svg'];
                $this->assertStringStartsWith('<svg ', $svg, "SVG must start with <svg tag");
                $this->assertStringContainsString('</svg>', $svg, "SVG must have closing tag");
                $this->assertStringContainsString('viewBox="0 0 420 60"', $svg);
                $this->assertStringNotContainsString('<pattern ', $svg);
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
            $answerIndex = (int) ($task['answer'] ?? 0);
            $this->assertTrue($answerIndex >= 1 && $answerIndex <= 4, "Task {$id}: invalid answer index");
            $correctSvg = $task['graph_options'][$answerIndex - 1]['svg'];
            $type = $expectations[$id];

            if ($type === 'strict') {
                $this->assertStringContainsString('fill="#0d1b2a" stroke="#4d9fdc"', $correctSvg,
                    "Task {$id}: strict inequality should have open endpoints");
            } else {
                $this->assertStringContainsString('fill="#4d9fdc"', $correctSvg,
                    "Task {$id}: non-strict inequality should have closed endpoints");
                $this->assertStringNotContainsString('fill="#0d1b2a" stroke="#4d9fdc"', $correctSvg,
                    "Task {$id}: non-strict inequality should not have open endpoints");
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
            $answerIndex = (int) ($task['answer'] ?? 0);
            $this->assertTrue($answerIndex >= 1 && $answerIndex <= 4, "Task {$id}: invalid answer index");
            $svg = $task['graph_options'][$answerIndex - 1]['svg'];
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
            $singleRayCount = 0;
            foreach ($task['graph_options'] as $option) {
                $svg = (string) ($option['svg'] ?? '');
                $textCount = substr_count($svg, '<text ');
                if ($textCount === 1) {
                    $singleRayCount++;
                }
            }

            $this->assertSame(2, $singleRayCount,
                "Task {$task['id']} should include exactly two single-ray distractors");
        }
    }

    public function test_axis_contains_only_boundary_labels(): void
    {
        foreach ($this->tasks as $task) {
            foreach ($task['graph_options'] as $option) {
                $svg = (string) ($option['svg'] ?? '');
                $textCount = substr_count($svg, '<text ');
                $this->assertTrue(in_array($textCount, [1, 2], true),
                    "Task {$task['id']} option {$option['index']} should contain only boundary labels");
            }
        }
    }

    public function test_answer_resolver_returns_explicit_task_answer_for_all_tasks(): void
    {
        $resolver = app(TaskAnswerResolver::class);

        foreach ($this->tasks as $task) {
            $expected = (string) ($task['answer'] ?? '');
            $this->assertContains($expected, ['1', '2', '3', '4']);
            $answer = $resolver->resolveFromTaskAndZadanie($this->zadanie, $task);
            $this->assertSame($expected, $answer,
                "TaskAnswerResolver should return explicit answer for task {$task['id']}");
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
        $view->assertSee('viewBox="0 0 420 60"', false);

        // Should NOT render the interval-line.blade.php partial
        $view->assertDontSee('<pattern ', false);
    }
}
