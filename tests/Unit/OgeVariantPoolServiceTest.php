<?php

namespace Tests\Unit;

use App\Services\MiniAppTaskCanonicalizer;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use Tests\TestCase;

class OgeVariantPoolServiceTest extends TestCase
{
    public function test_mixed_variant_backfill_preserves_four_plus_three_split(): void
    {
        $service = new class extends OgeVariantPoolService {
            public function __construct()
            {
                parent::__construct(
                    app(TaskDataService::class),
                    app(MiniAppTaskCanonicalizer::class),
                );
            }

            public function exposeGenerateVariantTasks(string $type): array
            {
                return $this->generateVariantTasks($type);
            }

            protected function pickRandomTopics(array $topics, int $count): array
            {
                return match ($count) {
                    4 => ['06', '07', '08', '09'],
                    3 => ['15', '16', '17'],
                    default => array_slice($topics, 0, $count),
                };
            }

            protected function pickTaskForTopic(string $topicId, ?string $status, array $excludeTaskIds): ?array
            {
                if (in_array($topicId, ['08', '17'], true)) {
                    return null;
                }

                return [
                    'topic_id' => $topicId,
                    'task' => [
                        'id' => (int) $topicId,
                        'answer' => $topicId,
                    ],
                ];
            }

            protected function normalizeTaskForMiniApp(array $task): array
            {
                return $task;
            }
        };

        $tasks = $service->exposeGenerateVariantTasks('mixed');

        $this->assertCount(7, $tasks);
        $algebraCount = collect($tasks)->filter(fn ($task) => (int) $task['task_number'] < 15)->count();
        $geometryCount = collect($tasks)->filter(fn ($task) => (int) $task['task_number'] >= 15)->count();

        $this->assertSame(4, $algebraCount);
        $this->assertSame(3, $geometryCount);
    }

    public function test_generated_tasks_have_canonical_slot_and_exam_number(): void
    {
        $service = new class extends OgeVariantPoolService {
            public function __construct()
            {
                parent::__construct(
                    app(TaskDataService::class),
                    app(MiniAppTaskCanonicalizer::class),
                );
            }

            public function exposeGenerateVariantTasks(string $type): array
            {
                return $this->generateVariantTasks($type);
            }

            protected function pickRandomTopics(array $topics, int $count): array
            {
                return array_slice($topics, 0, $count);
            }

            protected function pickTaskForTopic(string $topicId, ?string $status, array $excludeTaskIds): ?array
            {
                return [
                    'topic_id' => $topicId,
                    'task' => ['id' => (int) $topicId, 'answer' => $topicId],
                ];
            }

            protected function normalizeTaskForMiniApp(array $task): array
            {
                return $task;
            }
        };

        // Mixed: 4 alg + 3 geo = 7 tasks, sequential slots
        $tasks = $service->exposeGenerateVariantTasks('mixed');
        $this->assertCount(7, $tasks);
        foreach ($tasks as $index => $task) {
            $this->assertArrayHasKey('slot', $task, "Task at index {$index} missing 'slot'");
            $this->assertArrayHasKey('exam_number', $task, "Task at index {$index} missing 'exam_number'");
            $this->assertSame($index + 1, $task['slot'], "Task at index {$index} has wrong slot");
            $this->assertGreaterThan(0, $task['exam_number']);
        }

        // Full: slot == exam_number
        $fullTasks = $service->exposeGenerateVariantTasks('full');
        foreach ($fullTasks as $task) {
            $this->assertArrayHasKey('slot', $task);
            $this->assertArrayHasKey('exam_number', $task);
            $this->assertSame($task['exam_number'], $task['slot'],
                "Full variant: slot should equal exam_number");
        }

        // Algebra: sequential slots
        $algTasks = $service->exposeGenerateVariantTasks('algebra');
        $this->assertCount(5, $algTasks);
        foreach ($algTasks as $index => $task) {
            $this->assertSame($index + 1, $task['slot']);
        }

        // Part2: sequential slots
        $part2Tasks = $service->exposeGenerateVariantTasks('part2');
        $this->assertCount(2, $part2Tasks);
        $this->assertSame(1, $part2Tasks[0]['slot']);
        $this->assertSame(2, $part2Tasks[1]['slot']);
    }
}
