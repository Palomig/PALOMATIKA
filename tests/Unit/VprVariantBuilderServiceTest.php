<?php
namespace Tests\Unit;

use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use PHPUnit\Framework\TestCase;

class VprVariantBuilderServiceTest extends TestCase
{
    private function makeService(int $grade, array $fakeTopicData = []): VprVariantBuilderService
    {
        $taskData = $this->getMockBuilder(VprTaskDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRandomTaskFromTopic', 'getTopicMeta'])
            ->getMock();

        $taskData->method('getTopicMeta')
            ->willReturnCallback(fn(string $topicId) => [
                'title' => "Задание $topicId", 'description' => '',
                'color' => 'blue', 'icon' => 'calculator',
            ]);

        $taskData->method('getRandomTaskFromTopic')
            ->willReturnCallback(function (string $topicId) use ($grade) {
                return [
                    'task'           => ['id' => 1, 'expression' => '2+2', 'answer' => '4'],
                    'topic_id'       => $topicId,
                    'block_number'   => 1,
                    'zadanie_number' => 1,
                    'task_number'    => (int) ltrim($topicId, '0'),
                    'type'           => 'expression',
                    'instruction'    => 'Вычислите',
                ];
            });

        return new VprVariantBuilderService($taskData);
    }

    public function test_build_returns_18_tasks(): void
    {
        $builder = $this->makeService(5);
        $result  = $builder->build('abc123');
        $this->assertCount(18, $result['tasks']);
    }

    public function test_build_is_deterministic(): void
    {
        $builder = $this->makeService(5);
        $r1 = $builder->build('hash-xyz');
        $r2 = $builder->build('hash-xyz');
        $this->assertSame($r1['variantNumber'], $r2['variantNumber']);
    }

    public function test_build_task_numbers_are_1_to_18(): void
    {
        $builder  = $this->makeService(5);
        $result   = $builder->build('test-hash');
        $numbers  = array_column($result['tasks'], 'task_number');
        sort($numbers);
        $this->assertSame(range(1, 18), $numbers);
    }
}
