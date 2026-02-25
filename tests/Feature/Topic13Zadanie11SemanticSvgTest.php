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
            $this->assertArrayHasKey('image', $task);
            $this->assertStringContainsString('img-', (string) ($task['image'] ?? ''));
            $this->assertArrayNotHasKey('graph_options', $task);
            $this->assertArrayNotHasKey('svg', $task);
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

        $this->assertSame(8, substr_count($html, 'images/tasks/13/img-0'));
        $this->assertSame(0, substr_count($html, 'data-z10-option-panel='));
        $this->assertStringContainsString('1) x² - 49 ≤ 0', $html);
        $this->assertStringContainsString('4) x² + 4 &lt; 0', $html);
    }
}
