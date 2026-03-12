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

    // Part 2 topics: 20-21
    protected array $part2Topics = ['20', '21'];

    public function __construct(
        private readonly TaskDataService $taskData,
        private readonly MiniAppTaskCanonicalizer $taskCanonicalizer,
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
     * Force-create a new variant (used for battle links from Telegram buttons).
     */
    public function createBattleVariant(string $type): OgeVariant
    {
        $variant = $this->generateNewPoolVariant($type);

        // Mark as battle variant so leaderboard is shown on results page
        $config = $variant->config_json ?? [];
        $config['is_battle'] = true;
        $variant->config_json = $config;
        $variant->save();

        return $variant;
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
    protected function generateNewPoolVariant(string $type, int $maxRetries = 8): OgeVariant
    {
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $tasks = $this->generateVariantTasks($type);

            if (empty($tasks)) {
                throw new \RuntimeException("No production tasks available for variant type: {$type}");
            }

            // Build task references for fingerprint and pool_tasks
            $taskRefs = $this->extractTaskRefs($tasks);
            $fingerprint = $this->computeFingerprint($taskRefs);

            // Check uniqueness of full variant composition
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
                    'full_with_part2' => OgeVariant::MODE_FULL_WITH_PART2,
                    'part2' => OgeVariant::MODE_MINI_PART2,
                ];

                $titleMap = [
                    'geometry' => 'Мини-ОГЭ: Геометрия',
                    'algebra' => 'Мини-ОГЭ: Алгебра',
                    'mixed' => 'Мини-ОГЭ: Смешанное',
                    'full' => 'Полный вариант ОГЭ',
                    'full_with_part2' => 'Полный вариант ОГЭ (1+2 часть)',
                    'part2' => 'Мини-ОГЭ: 2-я часть',
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
     *
     * Rule: if a variant repeats the same topic/task-number slot,
     * it should avoid reusing the exact same example (task_id) already present
     * in existing variants in the DB pool when alternatives exist.
     */
    protected function generateVariantTasks(string $type): array
    {
        $topicIds = [];
        $fallbackTopicIds = [];
        $targetCount = null;

        switch ($type) {
            case 'geometry':
                $topicIds = $this->geometryTopics;
                $fallbackTopicIds = $this->geometryTopics;
                $targetCount = 5;
                break;
            case 'algebra':
                $topicIds = $this->pickRandomTopics($this->algebraTopics, 5);
                $fallbackTopicIds = array_values(array_diff($this->algebraTopics, $topicIds));
                $targetCount = 5;
                break;
            case 'mixed':
                // Product rule for mini mixed battle: 5 tasks total (strict split 3 algebra + 2 geometry)
                $alg = $this->pickRandomTopics($this->algebraTopics, 3);
                $geo = $this->pickRandomTopics($this->geometryTopics, 2);
                $topicIds = array_merge($alg, $geo);
                // Keep per-bucket fallback to preserve 3+2 composition.
                $fallbackTopicIds = [
                    'alg' => array_values(array_diff($this->algebraTopics, $alg)),
                    'geo' => array_values(array_diff($this->geometryTopics, $geo)),
                ];
                $targetCount = 5;
                break;
            case 'full':
                $topicIds = array_merge($this->algebraTopics, $this->geometryTopics);
                break;
            case 'full_with_part2':
                $topicIds = array_merge($this->algebraTopics, $this->geometryTopics, $this->part2Topics);
                break;
            case 'part2':
                $topicIds = $this->part2Topics;
                $targetCount = 2;
                break;
            default:
                throw new \InvalidArgumentException("Unknown variant type: {$type}");
        }

        $usedByTopic = $this->getUsedTaskIdsByTopic();
        $result = [];

        $tryPickForTopic = function (string $topicId) use (&$usedByTopic): ?array {
            // 1) Prefer task examples not used in existing pool variants
            $picked = $this->pickTaskForTopic($topicId, 'production', $usedByTopic[$topicId] ?? []);

            // 2) Fallback: any production task
            if ($picked === null) {
                $picked = $this->pickTaskForTopic($topicId, 'production', []);
            }

            return $picked;
        };

        foreach ($topicIds as $topicId) {
            $picked = $tryPickForTopic($topicId);
            if ($picked === null) {
                continue;
            }

            $picked['task_number'] = (int) ltrim($topicId, '0');
            $result[] = $this->normalizeTaskForMiniApp($picked);
        }

        // Backfill to target size for mini modes if some chosen topics had no production tasks.
        if ($targetCount !== null && count($result) < $targetCount) {
            if ($type === 'mixed' && is_array($fallbackTopicIds)) {
                $currentAlg = 0;
                $currentGeo = 0;
                foreach ($result as $row) {
                    $tid = str_pad((string) ($row['topic_id'] ?? ''), 2, '0', STR_PAD_LEFT);
                    if (in_array($tid, $this->algebraTopics, true)) {
                        $currentAlg++;
                    } elseif (in_array($tid, $this->geometryTopics, true)) {
                        $currentGeo++;
                    }
                }

                $needAlg = max(0, 3 - $currentAlg);
                $needGeo = max(0, 2 - $currentGeo);

                $algFallback = $fallbackTopicIds['alg'] ?? [];
                $geoFallback = $fallbackTopicIds['geo'] ?? [];
                shuffle($algFallback);
                shuffle($geoFallback);

                foreach ($algFallback as $topicId) {
                    if ($needAlg <= 0 || count($result) >= $targetCount) break;
                    $picked = $tryPickForTopic($topicId);
                    if ($picked === null) continue;
                    $picked['task_number'] = (int) ltrim($topicId, '0');
                    $result[] = $this->normalizeTaskForMiniApp($picked);
                    $needAlg--;
                }

                foreach ($geoFallback as $topicId) {
                    if ($needGeo <= 0 || count($result) >= $targetCount) break;
                    $picked = $tryPickForTopic($topicId);
                    if ($picked === null) continue;
                    $picked['task_number'] = (int) ltrim($topicId, '0');
                    $result[] = $this->normalizeTaskForMiniApp($picked);
                    $needGeo--;
                }
            } else {
                shuffle($fallbackTopicIds);
                foreach ($fallbackTopicIds as $topicId) {
                    if (count($result) >= $targetCount) {
                        break;
                    }

                    $picked = $tryPickForTopic($topicId);
                    if ($picked === null) {
                        continue;
                    }

                    $picked['task_number'] = (int) ltrim($topicId, '0');
                    $result[] = $this->normalizeTaskForMiniApp($picked);
                }
            }
        }

        return $result;
    }

    /**
     * Pick one random task for a topic, excluding task_ids if possible.
     */
    protected function pickTaskForTopic(string $topicId, ?string $status, array $excludeTaskIds): ?array
    {
        $blocks = $this->taskData->getBlocks($topicId, $status);
        $meta = $this->taskData->getTopicMeta($topicId);
        $all = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                    // Statements mode stored as a single pseudo-task.
                    $all[] = [
                        'topic_id' => $topicId,
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'] ?? '',
                        'type' => 'statements',
                        'section' => $zadanie['section'] ?? null,
                        'statements' => $zadanie['statements'],
                    ];
                    continue;
                }

                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $taskId = (int) ($task['id'] ?? 0);
                    if ($taskId > 0 && !empty($excludeTaskIds) && in_array($taskId, $excludeTaskIds, true)) {
                        continue;
                    }

                    // Skip tasks with missing/placeholder answers
                    $answer = trim((string) ($task['answer'] ?? ''));
                    if ($answer === '' || mb_stripos($answer, 'нет в базе') !== false) {
                        continue;
                    }

                    $all[] = [
                        'topic_id' => $topicId,
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'] ?? '',
                        'type' => $zadanie['type'] ?? 'expression',
                        'svg_type' => $zadanie['svg_type'] ?? null,
                        'options_render_mode' => $zadanie['options_render_mode'] ?? null,
                        'points' => $zadanie['points'] ?? null,
                        'options' => $task['options'] ?? $zadanie['options'] ?? null,
                        'task' => $task,
                    ];
                }
            }
        }

        if (empty($all)) {
            return null;
        }

        shuffle($all);
        return $all[0];
    }

    /**
     * Normalize task payload for mini-app UI.
     *
     * Mini-app template expects top-level keys like text/expression/svg/options,
     * while TaskDataService returns those under nested `task` for many task types.
     */
    protected function normalizeTaskForMiniApp(array $task): array
    {
        $inner = is_array($task['task'] ?? null) ? $task['task'] : [];

        // Keep original nested object for compatibility (answer resolver etc.)
        $normalized = $task;

        $normalized['task_id'] = (int) ($inner['id'] ?? 0);
        $normalized['text'] = $inner['text'] ?? ($task['text'] ?? null);
        $normalized['expression'] = $inner['expression'] ?? ($task['expression'] ?? null);

        // Fallback for topic-7 coordinate tasks where explicit expression may be missing,
        // but scalar target value exists in structured fields.
        if (($normalized['expression'] === null || $normalized['expression'] === '') && isset($inner['point_value'])) {
            $normalized['expression'] = (string) $inner['point_value'];
        }
        if (($normalized['expression'] === null || $normalized['expression'] === '') && isset($inner['target'])) {
            $normalized['expression'] = (string) $inner['target'];
        }
        if (($normalized['expression'] === null || $normalized['expression'] === '') && isset($task['point_value'])) {
            $normalized['expression'] = (string) $task['point_value'];
        }
        if (($normalized['expression'] === null || $normalized['expression'] === '') && isset($task['target'])) {
            $normalized['expression'] = (string) $task['target'];
        }

        $normalized['svg'] = $inner['svg'] ?? ($task['svg'] ?? null);
        $normalized['image'] = $inner['image'] ?? ($task['image'] ?? null);

        // For matching/choice tasks options are often inside task-level options.
        $normalized['options'] = $inner['options'] ?? ($task['options'] ?? null);

        // Graph options (topic 13 number-line choice tasks)
        $normalized['graph_options'] = $inner['graph_options'] ?? ($task['graph_options'] ?? null);
        $normalized['graph_options_mode'] = $inner['graph_options_mode'] ?? ($task['graph_options_mode'] ?? null);

        if (($normalized['type'] ?? '') === 'matching' && array_key_exists('answer', $inner)) {
            $normalized['correct_answer'] = $this->normalizeMatchingAnswer(
                (string) $inner['answer'],
                is_array($normalized['options'] ?? null) ? $normalized['options'] : []
            );
        }

        // Topic 19 statements in mini-OGE: always present exactly 3 statements.
        // If instruction starts with "Какое" => 1 true; "Какие" => 2 true.
        if (($normalized['type'] ?? '') === 'statements' && is_array($normalized['statements'] ?? null)) {
            $picked = $this->pickStatementsForMini($normalized['statements'], (string) ($normalized['instruction'] ?? ''));
            $normalized['selected_statements'] = $picked;

            $digits = [];
            foreach ($picked as $s) {
                if (!empty($s['is_true'])) {
                    $digits[] = (string) ($s['display_number'] ?? '');
                }
            }
            $normalized['correct_answer'] = implode('', array_filter($digits, fn ($v) => $v !== ''));
        }

        // Explicit answer for any future server-side consumers.
        if (!isset($normalized['correct_answer']) && array_key_exists('answer', $inner)) {
            $normalized['correct_answer'] = $inner['answer'];
        }

        return $this->taskCanonicalizer->normalizeForUi($normalized);
    }

    protected function normalizeMatchingAnswer(string $rawAnswer, array $options): string
    {
        $answer = trim($rawAnswer);
        if ($answer === '') {
            return '';
        }

        // Already numeric order like 312.
        if (preg_match('/^[1-9]+$/', $answer)) {
            return $answer;
        }

        if (empty($options)) {
            return $answer;
        }

        // Try to map tokenized equation answers to option indexes.
        $parts = preg_split('/\s*[,;|]\s*|\s{2,}/u', $answer) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== ''));
        if (empty($parts)) {
            return $answer;
        }

        $digits = [];
        foreach ($parts as $part) {
            $found = null;
            foreach ($options as $idx => $opt) {
                $optText = is_string($opt) ? trim($opt) : trim((string) ($opt['label'] ?? $opt['text'] ?? ''));
                if ($optText !== '' && mb_strtolower($optText) === mb_strtolower($part)) {
                    $found = (string) ($idx + 1);
                    break;
                }
            }
            if ($found === null) {
                return $answer;
            }
            $digits[] = $found;
        }

        return implode('', $digits);
    }

    /**
     * For statements-type mini tasks (task 19 style), pick exactly 3 statements.
     *
     * Rule:
     * - "Какое ..." => exactly 1 true statement among the 3
     * - "Какие ..." => exactly 2 true statements among the 3
     * - fallback: any random 3
     *
     * @param array<int,array<string,mixed>> $statements
     * @return array<int,array<string,mixed>>
     */
    protected function pickStatementsForMini(array $statements, string $instruction): array
    {
        if (count($statements) <= 3) {
            return array_map(function ($s, $idx) {
                if (!is_array($s)) {
                    return ['text' => (string) $s, 'is_true' => false, 'display_number' => $idx + 1];
                }
                $s['display_number'] = $idx + 1;
                return $s;
            }, array_values($statements), array_keys(array_values($statements)));
        }

        $lower = mb_strtolower(trim($instruction));
        $desiredTrueCount = 2; // Default: 2 true + 1 false (standard OGE task 19)
        if (str_starts_with($lower, 'какое')) {
            $desiredTrueCount = 1;
        } elseif (str_starts_with($lower, 'какие')) {
            $desiredTrueCount = 2;
        }

        // Split statements into true/false buckets for deterministic selection.
        $trueStatements = [];
        $falseStatements = [];
        foreach ($statements as $idx => $s) {
            if (!is_array($s)) {
                $s = ['text' => (string) $s, 'is_true' => false];
            }
            $s['_original_index'] = $idx;
            if (!empty($s['is_true'])) {
                $trueStatements[] = $s;
            } else {
                $falseStatements[] = $s;
            }
        }

        $desiredFalseCount = 3 - $desiredTrueCount;

        // If not enough true or false statements, adjust desired counts.
        if (count($trueStatements) < $desiredTrueCount || count($falseStatements) < $desiredFalseCount) {
            // Try the other standard OGE ratio (1 true + 2 false).
            if ($desiredTrueCount === 2 && count($trueStatements) >= 1 && count($falseStatements) >= 2) {
                $desiredTrueCount = 1;
                $desiredFalseCount = 2;
            } elseif ($desiredTrueCount === 1 && count($trueStatements) >= 2 && count($falseStatements) >= 1) {
                $desiredTrueCount = 2;
                $desiredFalseCount = 1;
            } else {
                // Last resort: pick whatever is available (at least 1 of each if possible).
                $desiredTrueCount = min(count($trueStatements), 2);
                $desiredFalseCount = 3 - $desiredTrueCount;
                $desiredFalseCount = min($desiredFalseCount, count($falseStatements));
                // If still can't fill 3, just grab any 3.
                if ($desiredTrueCount + $desiredFalseCount < 3) {
                    shuffle($statements);
                    return array_map(function ($s, $idx) {
                        if (!is_array($s)) {
                            return ['text' => (string) $s, 'is_true' => false, 'display_number' => $idx + 1];
                        }
                        $s['display_number'] = $idx + 1;
                        unset($s['_original_index']);
                        return $s;
                    }, array_slice(array_values($statements), 0, 3), [0, 1, 2]);
                }
            }
        }

        // Pick the required number of true and false statements randomly.
        shuffle($trueStatements);
        shuffle($falseStatements);

        $picked = array_merge(
            array_slice($trueStatements, 0, $desiredTrueCount),
            array_slice($falseStatements, 0, $desiredFalseCount)
        );

        // Sort by original index to maintain natural ordering.
        usort($picked, fn ($a, $b) => ($a['_original_index'] ?? 0) <=> ($b['_original_index'] ?? 0));

        // Assign display numbers and clean up.
        return array_values(array_map(function ($s, $idx) {
            $s['display_number'] = $idx + 1;
            unset($s['_original_index']);
            return $s;
        }, $picked, array_keys($picked)));
    }

    /**
     * Gather already used task_ids by topic from pool DB.
     *
     * @return array<string, array<int,int>>
     */
    protected function getUsedTaskIdsByTopic(): array
    {
        $rows = OgeVariantPoolTask::query()
            ->select('topic_id', 'task_id')
            ->where('task_id', '>', 0)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $topic = str_pad((string) $row->topic_id, 2, '0', STR_PAD_LEFT);
            $taskId = (int) $row->task_id;
            if ($taskId <= 0) {
                continue;
            }
            $map[$topic] ??= [];
            $map[$topic][$taskId] = $taskId;
        }

        // Convert set maps to plain arrays
        foreach ($map as $topic => $set) {
            $map[$topic] = array_values($set);
        }

        return $map;
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
            $taskId = (int) ($task['task']['id'] ?? $task['task_id'] ?? 0);

            if ($topicId && $blockNumber > 0 && $zadanieNumber >= 0 && $taskId > 0) {
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
     *
     * Business rule (mini-app uniqueness):
     * - A variant is considered NEW if at least one selected example differs.
     * - In practice: if any tuple (topic_id, block_number, zadanie_number, task_id)
     *   changes, fingerprint changes, therefore the variant is unique.
     * - Two variants are considered the SAME only when all tuples are identical.
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
