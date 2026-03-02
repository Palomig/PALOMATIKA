<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic07RenderPathConsistencyTest extends TestCase
{
    public function test_topic_07_choice_view_uses_task_svg_as_single_source_when_present(): void
    {
        $zadanie = [
            'number' => 1,
            'type' => 'choice',
            'instruction' => 'Проверка единственного источника SVG',
            'svg_type' => 'single_point',
            'points' => [
                ['value' => 1, 'label' => 'a'],
            ],
            'tasks' => [[
                'id' => 1,
                'point_value' => 1.5,
                'point_label' => 'a',
                'svg' => '<svg data-test="task-svg" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3"/></svg>',
                'options' => ['A', 'B'],
            ]],
        ];

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '07',
            'isVariant' => true,
        ]);

        $html = (string) $view;

        $this->assertSame(1, substr_count($html, 'data-test="task-svg"'));
        $this->assertSame(0, substr_count($html, 'class="w-full max-w-md h-16 number-line"'));
    }

    public function test_topic_07_data_keeps_prebaked_task_svg_in_storage_json(): void
    {
        Cache::forget('topic_data_07');

        $blocks = app(TaskDataService::class)->getBlocks('07');

        $firstTaskWithSvg = null;

        foreach ($blocks as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                foreach (($zadanie['tasks'] ?? []) as $task) {
                    if (is_string($task['svg'] ?? null) && $task['svg'] !== '') {
                        $firstTaskWithSvg = $task;
                        break 3;
                    }
                }
            }
        }

        $this->assertIsArray($firstTaskWithSvg);
        $this->assertStringContainsString('<svg', (string) $firstTaskWithSvg['svg']);
    }

    public function test_topic_07_choice_view_does_not_render_duplicate_svg_container_for_task_svg(): void
    {
        $zadanie = [
            'number' => 2,
            'type' => 'choice',
            'instruction' => 'Проверка контейнера SVG',
            'svg_type' => 'two_points',
            'tasks' => [[
                'id' => 1,
                'points' => [
                    ['value' => -1, 'label' => 'x'],
                    ['value' => 2, 'label' => 'y'],
                ],
                'svg' => '<svg data-test="task-svg-wrap" viewBox="0 0 20 20"><rect x="1" y="1" width="18" height="18"/></svg>',
                'options' => ['1', '2'],
            ]],
        ];

        $view = $this->view('tasks.types.choice', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '07',
            'isVariant' => true,
        ]);

        $html = (string) $view;

        $this->assertSame(1, substr_count($html, 'data-test="task-svg-wrap"'));
        $this->assertSame(1, substr_count($html, 'mt-4 mb-2'));
        $this->assertSame(0, substr_count($html, 'class="w-full max-w-md h-16 number-line"'));
    }
}
