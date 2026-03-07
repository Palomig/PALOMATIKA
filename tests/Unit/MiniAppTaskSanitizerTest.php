<?php

namespace Tests\Unit;

use App\Services\MiniAppTaskSanitizer;
use Tests\TestCase;

class MiniAppTaskSanitizerTest extends TestCase
{
    public function test_it_strips_sensitive_answer_fields_from_task_payload(): void
    {
        $sanitizer = new MiniAppTaskSanitizer();

        $task = [
            'task_number' => 11,
            'correct_answer' => '213',
            'answer' => '123',
            'task' => [
                'answer' => 'should_be_removed',
                'correct_answer' => 'also_removed',
            ],
            'selected_statements' => [
                ['text' => 'A', 'is_true' => true],
            ],
            'statements' => [
                ['text' => 'B', 'is_true' => false],
            ],
        ];

        $clean = $sanitizer->sanitize($task);

        $this->assertArrayNotHasKey('correct_answer', $clean);
        $this->assertArrayNotHasKey('answer', $clean);
        $this->assertArrayNotHasKey('is_true', $clean);
        $this->assertArrayNotHasKey('answer', $clean['task']);
        $this->assertArrayNotHasKey('correct_answer', $clean['task']);
        $this->assertArrayNotHasKey('is_true', $clean['selected_statements'][0]);
        $this->assertArrayNotHasKey('is_true', $clean['statements'][0]);
    }
}
