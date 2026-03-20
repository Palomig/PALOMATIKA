<?php

namespace Tests\Unit;

use App\Models\OgeVariant;
use App\Services\VariantTaskNumberResolver;
use PHPUnit\Framework\TestCase;

class VariantTaskNumberResolverTest extends TestCase
{
    public function test_canonical_fields_returned_when_present(): void
    {
        $task = ['slot' => 3, 'exam_number' => 15, 'task_number' => 99];
        $variant = $this->makeVariant(null, null);

        $result = VariantTaskNumberResolver::resolve($task, 0, $variant);

        $this->assertSame(3, $result['slot']);
        $this->assertSame(15, $result['exam_number']);
    }

    public function test_mini_mode_uses_sequential_slot(): void
    {
        $task = ['task_number' => 15, 'topic_id' => '15'];
        $variant = $this->makeVariant('miniapp', 'mini_mixed');

        $result = VariantTaskNumberResolver::resolve($task, 2, $variant);

        $this->assertSame(3, $result['slot']);         // index 2 → slot 3
        $this->assertSame(15, $result['exam_number']); // from task_number
    }

    public function test_mini_algebra_uses_sequential_slot(): void
    {
        $task = ['task_number' => 8, 'topic_id' => '08'];
        $variant = $this->makeVariant('miniapp', 'mini_algebra');

        $result = VariantTaskNumberResolver::resolve($task, 0, $variant);

        $this->assertSame(1, $result['slot']);
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_full_mode_slot_equals_exam_number(): void
    {
        $task = ['task_number' => 12];
        $variant = $this->makeVariant('miniapp', 'full');

        $result = VariantTaskNumberResolver::resolve($task, 4, $variant);

        $this->assertSame(12, $result['slot']);
        $this->assertSame(12, $result['exam_number']);
    }

    public function test_custom_random_uses_sequential_slot(): void
    {
        $task = ['task_number' => 8, 'test_number' => 5];
        $variant = $this->makeVariant('custom_random', null);

        $result = VariantTaskNumberResolver::resolve($task, 4, $variant);

        $this->assertSame(5, $result['slot']);        // from test_number
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_custom_random_fallback_to_index(): void
    {
        $task = ['task_number' => 8];
        $variant = $this->makeVariant('custom_random', null);

        $result = VariantTaskNumberResolver::resolve($task, 4, $variant);

        $this->assertSame(5, $result['slot']);        // index + 1
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_legacy_hash_variant_slot_equals_exam_number(): void
    {
        $task = ['task_number' => 8];
        $variant = $this->makeVariant(null, null);

        $result = VariantTaskNumberResolver::resolve($task, 2, $variant);

        $this->assertSame(8, $result['slot']);
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_legacy_hash_variant_fallback_when_no_task_number(): void
    {
        $task = [];
        $variant = $this->makeVariant(null, null);

        $result = VariantTaskNumberResolver::resolve($task, 2, $variant);

        $this->assertSame(8, $result['slot']);         // 6 + 2
        $this->assertSame(8, $result['exam_number']);
    }

    public function test_topic_id_used_as_exam_number_fallback(): void
    {
        $task = ['topic_id' => '15'];
        $variant = $this->makeVariant('miniapp', 'mini_mixed');

        $result = VariantTaskNumberResolver::resolve($task, 0, $variant);

        $this->assertSame(1, $result['slot']);
        $this->assertSame(15, $result['exam_number']);
    }

    public function test_resolve_all_returns_indexed_by_slot(): void
    {
        $tasks = [
            ['task_number' => 8, 'topic_id' => '08', 'correct_answer' => '42'],
            ['task_number' => 15, 'topic_id' => '15', 'correct_answer' => '7'],
        ];
        $variant = $this->makeVariant('miniapp', 'mini_mixed');

        $result = VariantTaskNumberResolver::resolveAll($tasks, $variant);

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertSame(1, $result[1]['slot']);
        $this->assertSame(8, $result[1]['exam_number']);
        $this->assertSame(2, $result[2]['slot']);
        $this->assertSame(15, $result[2]['exam_number']);
        $this->assertSame('42', $result[1]['task']['correct_answer']);
    }

    public function test_resolve_all_skips_non_array_tasks(): void
    {
        $tasks = [
            ['task_number' => 8, 'slot' => 1, 'exam_number' => 8],
            'garbage',
            null,
            ['task_number' => 15, 'slot' => 2, 'exam_number' => 15],
        ];
        $variant = $this->makeVariant('miniapp', 'mini_mixed');

        $result = VariantTaskNumberResolver::resolveAll($tasks, $variant);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    public function test_full_with_part2_slot_equals_exam_number(): void
    {
        $task = ['task_number' => 20];
        $variant = $this->makeVariant('miniapp', 'full_with_part2');

        $result = VariantTaskNumberResolver::resolve($task, 14, $variant);

        $this->assertSame(20, $result['slot']);
        $this->assertSame(20, $result['exam_number']);
    }

    public function test_mini_part2_uses_sequential_slot(): void
    {
        $task = ['task_number' => 20];
        $variant = $this->makeVariant('miniapp', 'mini_part2');

        $result = VariantTaskNumberResolver::resolve($task, 0, $variant);

        $this->assertSame(1, $result['slot']);
        $this->assertSame(20, $result['exam_number']);
    }

    private function makeVariant(?string $source, ?string $mode): OgeVariant
    {
        $variant = new OgeVariant();
        $variant->source = $source;
        $variant->mode = $mode;
        return $variant;
    }
}
