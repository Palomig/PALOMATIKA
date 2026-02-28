<?php

namespace App\Services;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use App\Models\OgeVariantPoolEntry;
use App\Models\OgeVariantPoolTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OgeVariantPoolService
{
    // Algebra topics: 06-14
    protected array $algebraTopics = ['06', '07', '08', '09', '10', '11', '12', '13', '14'];

    // Geometry topics: 15-19
    protected array $geometryTopics = ['15', '16', '17', '18', '19'];

    public function __construct(
        private readonly TaskDataService $taskData,
    ) {
    }

    /**
     * Get an existing unresolved variant from the pool, or create a new one.
     */
    public function getOrCreateVariant(User $user, string $type): OgeVariant
    {
        // 1. Try to find an active pool variant the user hasn't attempted
        $poolEntry = $this->findUnsolvedVariant($user, $type);

        if ($poolEntry) {
            return $poolEntry->variant;
        }

        // 2. No unsolved variants — generate a new one
        return $this->generateNewPoolVariant($type);
    }

    /**
     * Find an active pool variant the user hasn't attempted yet.
     */
    protected function findUnsolvedVariant(User $user, string $type): ?OgeVariantPoolEntry
    {
        $attemptedVariantIds = OgeAttempt::where('student_id', $user->id)
            ->pluck('variant_id');

        return OgeVariantPoolEntry::active()
            ->ofType($type)
            ->whereNotIn('variant_id', $attemptedVariantIds)
            ->inRandomOrder()
            ->first();
    }

    /**
     * Generate a new variant, add it to the pool, and return the OgeVariant.
     */
    protected function generateNewPoolVariant(string $type, int $maxRetries = 5): OgeVariant
    {
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $tasks = $this->generateVariantTasks($type);

            if (empty($tasks)) {
                throw new \RuntimeException("No production tasks available for variant type: {$type}");
            }

            // Build task references for fingerprint and pool_tasks
            $taskRefs = $this->extractTaskRefs($tasks);
            $fingerprint = $this->computeFingerprint($taskRefs);

            // Check uniqueness
            if (OgeVariantPoolEntry::where('task_fingerprint', $fingerprint)->exists()) {
                continue; // Duplicate — retry with different random selection
            }

            return DB::transaction(function () use ($type, $tasks, $taskRefs, $fingerprint) {
                $hash = $this->generateUniqueHash();

                $modeMap = [
                    'geometry' => OgeVariant::MODE_MINI_GEOMETRY,
                    'algebra' => OgeVariant::MODE_MINI_ALGEBRA,
                    'mixed' => OgeVariant::MODE_MINI_MIXED,
                    'full' => OgeVariant::MODE_FULL,
                ];

                $titleMap = [
                    'geometry' => 'Мини-ОГЭ: Геометрия',
                    'algebra' => 'Мини-ОГЭ: Алгебра',
                    'mixed' => 'Мини-ОГЭ: Смешанное',
                    'full' => 'Полный вариант ОГЭ',
                ];

                // Create the OgeVariant
                $variant = OgeVariant::create([
                    'hash' => $hash,
                    'title' => $titleMap[$type] ?? 'Вариант ОГЭ',
                    'mode' => $modeMap[$type] ?? null,
                    'source' => OgeVariant::SOURCE_MINIAPP,
                    'config_json' => ['tasks' => $tasks, 'mode' => $type],
                ]);

                // Create pool entry
                $poolEntry = OgeVariantPoolEntry::create([
                    'variant_id' => $variant->id,
                    'type' => $type,
                    'status' => 'active',
                    'task_fingerprint' => $fingerprint,
                    'created_at' => now(),
                ]);

                // Create pool task records
                foreach ($taskRefs as $index => $ref) {
                    OgeVariantPoolTask::create([
                        'pool_id' => $poolEntry->id,
                        'topic_id' => $ref['topic_id'],
                        'block_number' => $ref['block_number'],
                        'zadanie_number' => $ref['zadanie_number'],
                        'task_id' => $ref['task_id'],
                        'sort_order' => $index + 1,
                    ]);
                }

                return $variant;
            });
        }

        throw new \RuntimeException("Could not generate unique variant after {$maxRetries} retries for type: {$type}");
    }

    /**
     * Generate tasks for a variant type using only production tasks.
     */
    protected function generateVariantTasks(string $type): array
    {
        $topicIds = match ($type) {
            'geometry' => $this->geometryTopics,
            'algebra' => $this->pickRandomTopics($this->algebraTopics, 5),
            'mixed' => array_merge(
                $this->pickRandomTopics($this->algebraTopics, 4),
                $this->pickRandomTopics($this->geometryTopics, 3),
            ),
            'full' => array_merge($this->algebraTopics, $this->geometryTopics),
            default => throw new \InvalidArgumentException("Unknown variant type: {$type}"),
        };

        $result = [];

        foreach ($topicIds as $topicId) {
            $tasks = $this->taskData->getRandomTasks($topicId, 1, 'production');
            if (!empty($tasks)) {
                $task = $tasks[0];
                $task['task_number'] = (int) ltrim($topicId, '0');
                $result[] = $task;
            }
        }

        return $result;
    }

    /**
     * Pick N random topics from a list.
     */
    protected function pickRandomTopics(array $topics, int $count): array
    {
        shuffle($topics);
        return array_slice($topics, 0, min($count, count($topics)));
    }

    /**
     * Extract task reference tuples from generated tasks.
     */
    protected function extractTaskRefs(array $tasks): array
    {
        $refs = [];

        foreach ($tasks as $task) {
            $topicId = $task['topic_id'] ?? '';
            $blockNumber = (int) ($task['block_number'] ?? 0);
            $zadanieNumber = (int) ($task['zadanie_number'] ?? 0);
            $taskId = (int) ($task['task']['id'] ?? 0);

            if ($topicId && $blockNumber > 0 && $zadanieNumber > 0 && $taskId > 0) {
                $refs[] = [
                    'topic_id' => $topicId,
                    'block_number' => $blockNumber,
                    'zadanie_number' => $zadanieNumber,
                    'task_id' => $taskId,
                ];
            }
        }

        return $refs;
    }

    /**
     * Compute a unique fingerprint for a set of task references.
     */
    public function computeFingerprint(array $taskRefs): string
    {
        $keys = array_map(
            fn ($ref) => "{$ref['topic_id']}_{$ref['block_number']}_{$ref['zadanie_number']}_{$ref['task_id']}",
            $taskRefs
        );

        sort($keys);

        return hash('sha256', implode('|', $keys));
    }

    /**
     * Generate a unique hash for an OgeVariant.
     */
    protected function generateUniqueHash(): string
    {
        do {
            $hash = strtolower(Str::random(10));
        } while (OgeVariant::where('hash', $hash)->exists());

        return $hash;
    }

    /**
     * Deactivate all pool variants containing a specific task.
     */
    public function deactivateVariantsWithTask(string $topicId, int $taskId): int
    {
        $poolIds = OgeVariantPoolTask::where('topic_id', $topicId)
            ->where('task_id', $taskId)
            ->pluck('pool_id')
            ->unique();

        if ($poolIds->isEmpty()) {
            return 0;
        }

        $count = OgeVariantPoolEntry::whereIn('id', $poolIds)
            ->where('status', 'active')
            ->update([
                'status' => 'deactivated',
                'deactivated_at' => now(),
            ]);

        if ($count > 0) {
            Log::info("Deactivated {$count} pool variants due to task {$topicId}:{$taskId} going to draft");
        }

        return $count;
    }

    /**
     * Check all deactivated variants and reactivate those where all tasks are production.
     */
    public function reactivateEligibleVariants(): int
    {
        $deactivated = OgeVariantPoolEntry::where('status', 'deactivated')
            ->with('poolTasks')
            ->get();

        $reactivatedCount = 0;

        foreach ($deactivated as $entry) {
            $allProduction = true;

            foreach ($entry->poolTasks as $poolTask) {
                if (!$this->taskData->isTaskProduction($poolTask->topic_id, $poolTask->task_id)) {
                    $allProduction = false;
                    break;
                }
            }

            if ($allProduction) {
                $entry->reactivate();
                $reactivatedCount++;
            }
        }

        if ($reactivatedCount > 0) {
            Log::info("Reactivated {$reactivatedCount} pool variants (all tasks now production)");
        }

        return $reactivatedCount;
    }

    /**
     * Full sync: check all pool entries and update their status based on current task statuses.
     */
    public function syncAllVariantStatuses(): array
    {
        $entries = OgeVariantPoolEntry::with('poolTasks')->get();

        $activated = 0;
        $deactivated = 0;

        foreach ($entries as $entry) {
            $allProduction = true;

            foreach ($entry->poolTasks as $poolTask) {
                if (!$this->taskData->isTaskProduction($poolTask->topic_id, $poolTask->task_id)) {
                    $allProduction = false;
                    break;
                }
            }

            if ($allProduction && $entry->status === 'deactivated') {
                $entry->reactivate();
                $activated++;
            } elseif (!$allProduction && $entry->status === 'active') {
                $entry->deactivate();
                $deactivated++;
            }
        }

        return [
            'total' => $entries->count(),
            'activated' => $activated,
            'deactivated' => $deactivated,
        ];
    }

    /**
     * Get pool statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => OgeVariantPoolEntry::count(),
            'active' => OgeVariantPoolEntry::active()->count(),
            'deactivated' => OgeVariantPoolEntry::where('status', 'deactivated')->count(),
            'by_type' => [
                'full' => OgeVariantPoolEntry::active()->ofType('full')->count(),
                'geometry' => OgeVariantPoolEntry::active()->ofType('geometry')->count(),
                'algebra' => OgeVariantPoolEntry::active()->ofType('algebra')->count(),
                'mixed' => OgeVariantPoolEntry::active()->ofType('mixed')->count(),
            ],
        ];
    }
}
