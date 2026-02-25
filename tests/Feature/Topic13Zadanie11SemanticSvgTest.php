<?php

namespace Tests\Feature;

use App\Services\TaskAnswerResolver;
use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Topic13Zadanie11SemanticSvgTest extends TestCase
{
    public function test_topic_13_block1_z11_uses_four_graph_options_and_no_png_images(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;

        $this->assertIsArray($z11);
        $this->assertSame(11, (int) ($z11['number'] ?? 0));

        foreach ($z11['tasks'] ?? [] as $task) {
            $this->assertArrayHasKey('graph_options', $task);
            $this->assertCount(4, $task['graph_options']);
            $this->assertArrayNotHasKey('image', $task);

            foreach ($task['graph_options'] as $optionIndex => $option) {
                $this->assertSame($optionIndex + 1, (int) ($option['index'] ?? 0));
                $this->assertIsString($option['svg'] ?? null);
                $this->assertStringContainsString('<svg', (string) ($option['svg'] ?? ''));
                $this->assertStringNotContainsString('data-label-role="tick"', (string) ($option['svg'] ?? ''));
            }
        }
    }

    public function test_topic_13_block1_z11_compact_svg_boundary_semantics_match_interval_inclusion(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $task1 = $z11['tasks'][0] ?? null; // x^2 - 49 <= 0 => [-7;7]
        $task2 = $z11['tasks'][1] ?? null; // x^2 - 36 > 0 => (-inf;-6) U (6;+inf)
        $this->assertIsArray($task1);
        $this->assertIsArray($task2);

        $closedSvg = (string) (($task1['graph_options'][0]['svg'] ?? ''));
        $openSvg = (string) (($task2['graph_options'][0]['svg'] ?? ''));

        // Included boundary => filled black point.
        $this->assertStringContainsString('fill="#111827" stroke="#111827"', $closedSvg);
        // Excluded boundary => hollow white point.
        $this->assertStringContainsString('fill="#ffffff" stroke="#111827"', $openSvg);
        $this->assertStringNotContainsString('width="7" height="7"', $openSvg);
    }

    public function test_topic_13_block1_z11_none_solution_option_is_text_only(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');
        $z11 = $blocks[0]['zadaniya'][10] ?? null;
        $this->assertIsArray($z11);

        $task8 = $z11['tasks'][7] ?? null; // x^2 + 4 < 0 => no solutions
        $this->assertIsArray($task8);

        $noneSvg = (string) (($task8['graph_options'][3]['svg'] ?? ''));
        $this->assertStringContainsString('нет решений', $noneSvg);
        $this->assertStringNotContainsString('marker-start=', $noneSvg);
        $this->assertStringNotContainsString('marker-end=', $noneSvg);
    }

    public function test_topic_13_block1_z11_correct_option_mapping_stays_unchanged(): void
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
}
