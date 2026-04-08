<?php
namespace Tests\Unit;

use App\Services\EgeTaskDataService;
use App\Services\EgeVariantBuilderService;
use PHPUnit\Framework\TestCase;

class EgeVariantBuilderServiceTest extends TestCase
{
    private function makeService(): EgeVariantBuilderService
    {
        $taskData = $this->getMockBuilder(EgeTaskDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRandomTaskFromTopic', 'getTopicMeta'])
            ->getMock();

        $taskData->method('getTopicMeta')
            ->willReturnCallback(fn(string $topicId) => [
                'title' => "Задание $topicId", 'description' => '',
                'color' => 'blue', 'icon' => 'calculator',
            ]);

        $taskData->method('getRandomTaskFromTopic')
            ->willReturnCallback(function (string $topicId) {
                return [
                    'task'           => ['id' => 1, 'expression' => 'x+1', 'answer' => '2'],
                    'topic_id'       => $topicId,
                    'block_number'   => 1,
                    'zadanie_number' => 1,
                    'task_number'    => (int) ltrim($topicId, '0'),
                    'type'           => 'expression',
                    'instruction'    => 'Решите',
                ];
            });

        return new EgeVariantBuilderService($taskData);
    }

    public function test_build_returns_20_tasks(): void
    {
        $result = $this->makeService()->build('hash123');
        $this->assertCount(20, $result['tasks']);
    }

    public function test_build_is_deterministic(): void
    {
        $svc = $this->makeService();
        $this->assertSame(
            $svc->build('same-hash')['variantNumber'],
            $svc->build('same-hash')['variantNumber']
        );
    }
}
