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
        $this->assertSame('2', $norm['correct_answer']);
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
}
