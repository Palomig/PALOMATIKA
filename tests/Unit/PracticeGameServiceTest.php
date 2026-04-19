<?php

namespace Tests\Unit;

use App\Services\PracticeGameService;
use Tests\TestCase;

class PracticeGameServiceTest extends TestCase
{
    public function test_resolves_level_by_score(): void
    {
        $service = app(PracticeGameService::class);

        $this->assertSame(1, $service->resolveLevel('equations', 0)['level']);
        $this->assertSame(2, $service->resolveLevel('equations', 10)['level']);
        $this->assertSame(4, $service->resolveLevel('equations', 35)['level']);
    }

    public function test_generates_negative_multiplier_transfer_question(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('equations', 'move_negative_multiplier');

        $this->assertSame('move_negative_multiplier', $question['task_type']);
        $this->assertStringContainsString('x', $question['equation']);
        $this->assertCount(2, $question['options']);
        $this->assertSame(1, collect($question['options'])->where('is_correct', true)->count());

        $correct = collect($question['options'])->firstWhere('is_correct', true);
        $this->assertStringContainsString('/', $correct['label']);
        $this->assertStringContainsString('-', $correct['label']);
    }

    public function test_generates_negative_term_transfer_question(): void
    {
        $service = app(PracticeGameService::class);

        $question = $service->generateQuestionByTaskType('equations', 'move_negative_term_before_x');

        $this->assertSame('move_negative_term_before_x', $question['task_type']);
        $this->assertCount(2, $question['options']);

        $correct = collect($question['options'])->firstWhere('is_correct', true);
        $wrong = collect($question['options'])->firstWhere('is_correct', false);

        $this->assertStringContainsString('+', $correct['label']);
        $this->assertStringContainsString('-', $wrong['label']);
    }
}
