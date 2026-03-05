<?php

namespace Tests\Unit;

use App\Services\AdaptiveVariantService;
use App\Services\OgeVariantBuilderService;
use App\Services\StudentAnalyticsService;
use App\Services\TaskDataService;
use PHPUnit\Framework\TestCase;

class AdaptiveVariantServiceTest extends TestCase
{
    public function test_build_adaptive_variant_prioritizes_weak_topics_and_keeps_strong_reinforcement(): void
    {
        $taskDataService = $this->mockTaskDataService([
            '06' => ['06_1_1'],
            '15' => ['15_1_1'],
        ]);

        $analyticsService = $this->createMock(StudentAnalyticsService::class);
        $analyticsService->method('getTopicWeights')->willReturn([
            '06' => 1.7,
            '15' => 0.2,
        ]);
        $analyticsService->method('getTopicMasterySnapshot')->willReturn([
            '06' => ['avg_mastery' => 0.3, 'min_mastery' => 0.2, 'attempts' => 12, 'days_since' => 1],
            '15' => ['avg_mastery' => 0.9, 'min_mastery' => 0.85, 'attempts' => 12, 'days_since' => 1],
        ]);

        $variantBuilder = $this->createMock(OgeVariantBuilderService::class);
        $variantBuilder->method('build')->willReturnCallback(
            fn (string $hash, ?array $selected = null) => [
                'tasks' => [],
                'variantNumber' => 1,
                'selectedZadaniya' => $selected ?? [],
            ]
        );

        $service = new AdaptiveVariantService($taskDataService, $analyticsService, $variantBuilder);
        $result = $service->buildAdaptiveVariant(42, 'abc123');
        $selected = $result['selectedZadaniya'];

        $this->assertTrue($result['adaptive']);
        $this->assertCount(2, $selected);
        $this->assertContains('06_1_1', $selected);
        $this->assertContains('15_1_1', $selected);
    }

    public function test_subtype_weakness_is_treated_as_weak_priority_not_strong_reinforcement(): void
    {
        $taskDataService = $this->mockTaskDataService([
            '06' => ['06_1_1'],
            '16' => ['16_1_1'],
            '17' => ['17_1_1'],
        ]);

        $analyticsService = $this->createMock(StudentAnalyticsService::class);
        $analyticsService->method('getTopicWeights')->willReturn([
            '06' => 1.6,
            '16' => 1.1,
            '17' => 0.2,
        ]);
        $analyticsService->method('getTopicMasterySnapshot')->willReturn([
            '06' => ['avg_mastery' => 0.4, 'min_mastery' => 0.35, 'attempts' => 8, 'days_since' => 1],
            '16' => ['avg_mastery' => 0.83, 'min_mastery' => 0.55, 'attempts' => 15, 'days_since' => 1],
            '17' => ['avg_mastery' => 0.86, 'min_mastery' => 0.8, 'attempts' => 15, 'days_since' => 1],
        ]);

        $variantBuilder = $this->createMock(OgeVariantBuilderService::class);
        $variantBuilder->method('build')->willReturnCallback(
            fn (string $hash, ?array $selected = null) => [
                'tasks' => [],
                'variantNumber' => 1,
                'selectedZadaniya' => $selected ?? [],
            ]
        );

        $service = new AdaptiveVariantService($taskDataService, $analyticsService, $variantBuilder);
        $result = $service->buildAdaptiveVariant(42, 'def456');
        $selected = $result['selectedZadaniya'];

        $this->assertTrue($result['adaptive']);
        $this->assertCount(3, $selected);
        $this->assertContains('06_1_1', $selected);
        $this->assertContains('16_1_1', $selected);
        $this->assertContains('17_1_1', $selected);
    }

    /**
     * @param array<string, array<int, string>> $topicToZadaniya
     */
    private function mockTaskDataService(array $topicToZadaniya): TaskDataService
    {
        $service = $this->getMockBuilder(TaskDataService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBlocks'])
            ->getMock();

        $service->method('getBlocks')->willReturnCallback(function (string $topicId) use ($topicToZadaniya): array {
            $zadaniya = $topicToZadaniya[$topicId] ?? [];
            if ($zadaniya === []) {
                return [];
            }

            return [[
                'number' => 1,
                'zadaniya' => array_map(function (string $zadanieId): array {
                    $parts = explode('_', $zadanieId);
                    return ['number' => (int) ($parts[2] ?? 1)];
                }, $zadaniya),
            ]];
        });

        return $service;
    }
}
