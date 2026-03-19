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

            protected function getUsedTaskIdsByTopic(): array
            {
                return [];
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
}
