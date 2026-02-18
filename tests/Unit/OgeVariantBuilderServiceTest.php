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
}

