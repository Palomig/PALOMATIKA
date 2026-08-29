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

    /** В профиле 19 номеров заданий — двадцатого не существует. */
    public function test_build_returns_a_task_per_exam_number(): void
    {
        $result = $this->makeService()->build('hash123');

        $this->assertCount(19, $result['tasks']);
        $this->assertSame(
            range(1, 19),
            array_column($result['tasks'], 'task_number')
        );
    }

    public function test_build_is_deterministic(): void
    {
        $svc = $this->makeService();
        $this->assertSame(
            $svc->build('same-hash')['variantNumber'],
            $svc->build('same-hash')['variantNumber']
        );
    }

    public function test_mini_mode_maps_match_both_exam_levels(): void
    {
        $profile = EgeVariantBuilderService::miniModes(EgeTaskDataService::LEVEL_PROF);
        $base = EgeVariantBuilderService::miniModes(EgeTaskDataService::LEVEL_BASE);

        $this->assertSame(range(1, 12), array_map('intval', $profile['part1']['topics']));
        $this->assertSame(5, $profile['part1']['count']);
        $this->assertSame([1, 2, 3], array_map('intval', $profile['geometry']['topics']));
        $this->assertSame(3, $profile['geometry']['count']);
        $this->assertSame(range(1, 8), array_map('intval', $base['practical']['topics']));
        $this->assertSame(range(14, 21), array_map('intval', $base['calculation']['topics']));
        $this->assertSame(5, $base['mixed']['count']);
    }

    public function test_build_mini_chooses_distinct_topics_in_the_mode_range(): void
    {
        $service = $this->makeService();
        $mode = EgeVariantBuilderService::miniModes(EgeTaskDataService::LEVEL_PROF)['part1'];

        $result = $service->buildMini('mini-profile', $mode['topics'], $mode['count']);
        $numbers = array_column($result['tasks'], 'task_number');

        $this->assertCount(5, $numbers);
        $this->assertCount(5, array_unique($numbers));
        $this->assertEmpty(array_diff($numbers, range(1, 12)));
        $this->assertSame(
            $numbers,
            array_column($service->buildMini('mini-profile', $mode['topics'], $mode['count'])['tasks'], 'task_number')
        );
    }

    public function test_build_mini_takes_every_available_topic_when_mode_is_smaller_than_count(): void
    {
        $result = $this->makeService()->buildMini('small-mode', ['01', '02', '03'], 5);

        $this->assertCount(3, $result['tasks']);
        $this->assertSame([1, 2, 3], array_values(array_unique(array_column($result['tasks'], 'task_number'))));
    }
}
