<?php

namespace Tests\Unit;

use App\Services\MiniAppTaskCanonicalizer;
use Tests\TestCase;

class MiniAppTaskCanonicalizerTest extends TestCase
{
    public function test_choice_task_gets_choice_index_canonical_answer(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'choice',
            'task' => ['answer' => '2', 'options' => ['A', 'B', 'C']],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertSame('choice_index', $norm['answer_kind']);
        $this->assertSame('2', $norm['canonical_answer']);
        $this->assertSame('b', $norm['correct_answer']);
    }

    public function test_matching_task_keeps_matching_order_kind(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'matching',
            'task' => ['answer' => '312'],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertSame('matching_order', $norm['answer_kind']);
        $this->assertSame('312', $norm['canonical_answer']);
    }

    public function test_statements_task_builds_mask_from_selected_statements(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'statements',
            'selected_statements' => [
                ['text' => 'A', 'is_true' => true],
                ['text' => 'B', 'is_true' => false],
                ['text' => 'C', 'is_true' => true],
            ],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertSame('statements_mask', $norm['answer_kind']);
        $this->assertSame('13', $norm['canonical_answer']);
    }

    public function test_numeric_text_answer_kind_for_non_index_value(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'input',
            'task' => ['answer' => '0.03'],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertSame('numeric_or_text', $norm['answer_kind']);
        $this->assertSame('0.03', $norm['canonical_answer']);
    }

    public function test_options_get_stable_ids_from_legacy_string_list(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'choice',
            'task' => [
                'answer' => '2',
                'options' => ['A', 'B', 'C'],
            ],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertSame('a', $norm['options'][0]['id']);
        $this->assertSame('b', $norm['options'][1]['id']);
        $this->assertSame('B', $norm['options'][1]['label']);
        $this->assertSame('2', $norm['canonical_answer']); // backward compatible
        $this->assertSame('b', $norm['canonical_option_id']); // forward compatible
    }

    public function test_options_keep_existing_ids_in_object_format(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'choice',
            'task' => [
                'answer' => '1',
                'options' => [
                    ['id' => 'opt-x', 'value' => 'X'],
                    ['id' => 'opt-y', 'text' => 'Y'],
                ],
            ],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertSame('opt-x', $norm['options'][0]['id']);
        $this->assertSame('opt-y', $norm['options'][1]['id']);
        $this->assertSame('X', $norm['options'][0]['label']);
        $this->assertSame('Y', $norm['options'][1]['label']);
    }

    public function test_nested_graph_options_are_preserved_for_topic_13_ui(): void
    {
        $svc = new MiniAppTaskCanonicalizer();
        $task = [
            'type' => 'expression',
            'task' => [
                'answer' => '[2; +∞)',
                'graph_options' => [
                    ['index' => 1, 'text' => '[2; +∞)', 'svg' => '<svg></svg>'],
                    ['index' => 2, 'text' => '(-∞; 2)', 'svg' => '<svg></svg>'],
                ],
                'graph_options_mode' => 'compact_number_line',
            ],
        ];

        $norm = $svc->normalizeForUi($task);

        $this->assertCount(2, $norm['graph_options']);
        $this->assertSame('compact_number_line', $norm['graph_options_mode']);
        $this->assertSame('[2; +∞)', $norm['graph_options'][0]['text']);
    }
}
