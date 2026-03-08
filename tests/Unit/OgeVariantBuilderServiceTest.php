<?php

namespace Tests\Unit;

use App\Services\OgeVariantBuilderService;
use App\Services\TaskDataService;
use PHPUnit\Framework\TestCase;

class OgeVariantBuilderServiceTest extends TestCase
{
    public function test_topic_11_uses_matching_set_payload(): void
    {
        $taskDataService = $this->getMockBuilder(TaskDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRandomMatchingSet', 'getRandomTasksFromZadanie'])
            ->getMock();

        $taskDataService
            ->expects($this->once())
            ->method('getRandomMatchingSet')
            ->with('11')
            ->willReturn([
                'topic_id' => '11',
                'type' => 'matching',
                'instruction' => 'Установите соответствие между графиками функций и формулами',
                'tasks' => [
                    ['id' => 1, 'image' => 'a.png', 'options' => ['y=x']],
                    ['id' => 2, 'image' => 'b.png', 'options' => ['y=2x']],
                    ['id' => 3, 'image' => 'c.png', 'options' => ['y=3x']],
                ],
                'formulas' => ['y=x', 'y=2x', 'y=3x'],
                'is_matching_set' => true,
            ]);

        $taskDataService
            ->expects($this->never())
            ->method('getRandomTasksFromZadanie');

        $service = new OgeVariantBuilderService($taskDataService);
        $payload = $service->build('test-hash', ['11_1_1']);

        $this->assertCount(1, $payload['tasks']);
        $task = $payload['tasks'][0];

        $this->assertTrue((bool) ($task['is_matching_set'] ?? false));
        $this->assertCount(3, $task['tasks'] ?? []);
        $this->assertCount(3, $task['formulas'] ?? []);
    }

    public function test_statements_selection_is_deterministic_for_same_hash(): void
    {
        $taskDataService = $this->getMockBuilder(TaskDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRandomMatchingSet', 'getRandomTasksFromZadanie'])
            ->getMock();

        $taskDataService
            ->expects($this->never())
            ->method('getRandomMatchingSet');

        $taskDataService
            ->expects($this->exactly(2))
            ->method('getRandomTasksFromZadanie')
            ->with('06', 1, 1, 1)
            ->willReturn([
                [
                    'type' => 'statements',
                    'task' => [
                        'answer' => null,
                    ],
                    'statements' => [
                        ['text' => 'S1', 'is_true' => true],
                        ['text' => 'S2', 'is_true' => false],
                        ['text' => 'S3', 'is_true' => true],
                        ['text' => 'S4', 'is_true' => false],
                        ['text' => 'S5', 'is_true' => true],
                    ],
                ],
            ]);

        $service = new OgeVariantBuilderService($taskDataService);

        $first = $service->build('same-hash', ['06_1_1']);
        $second = $service->build('same-hash', ['06_1_1']);

        $firstTask = $first['tasks'][0] ?? [];
        $secondTask = $second['tasks'][0] ?? [];

        $this->assertSame($firstTask['selected_statements'] ?? null, $secondTask['selected_statements'] ?? null);
        $this->assertSame($firstTask['correct_answer'] ?? null, $secondTask['correct_answer'] ?? null);
    }
}

