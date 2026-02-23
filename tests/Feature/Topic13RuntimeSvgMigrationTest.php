<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13RuntimeSvgMigrationTest extends TestCase
{
    public function test_topic_13_block1_zadaniya_10_to_13_receive_runtime_svg_without_removing_png_fallback_data(): void
    {
        Cache::forget('topic_data_13');

        $blocks = app(TaskDataService::class)->getBlocks('13');

        foreach ([9, 10, 11, 12] as $zadanieIndex) {
            $zadanie = $blocks[0]['zadaniya'][$zadanieIndex] ?? null;
            $this->assertIsArray($zadanie);

            foreach ($zadanie['tasks'] ?? [] as $task) {
                $this->assertNotEmpty($task['image'] ?? null, 'PNG fallback should remain in data');
                $this->assertIsString($task['svg'] ?? null, 'Runtime SVG should be injected');
                $this->assertStringContainsString('<svg', $task['svg']);
            }
        }
    }

    public function test_runtime_choice_view_prefers_svg_for_migrated_topic_13_task_and_keeps_text_options(): void
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

        $view->assertSee('<svg', false);
        $view->assertSee('x² - 49 ≤ 0');
        $view->assertDontSee('images/tasks/13/img-035.png');
        $view->assertDontSee('pattern id="hatch_', false);
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
