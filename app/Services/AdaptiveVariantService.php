<?php

namespace App\Services;

use App\Models\OgeVariant;
use App\Models\StudentTopicMastery;

/**
 * Builds personalized OGE variants that adapt to student performance.
 *
 * Algorithm:
 * 1. Compute per-topic weights from mastery data (weak topics get higher weight).
 * 2. Use weighted random sampling to select zadaniya, biasing toward weak areas.
 * 3. Periodically include strong-area tasks for spaced reinforcement (~20%).
 * 4. Falls back to uniform random when no mastery data exists (new student).
 */
class AdaptiveVariantService
{
    /** Fraction of tasks reserved for reinforcement of strong topics */
    private const REINFORCEMENT_RATIO = 0.2;

    public function __construct(
        private readonly TaskDataService $taskDataService,
        private readonly StudentAnalyticsService $analyticsService,
        private readonly OgeVariantBuilderService $variantBuilder,
    ) {
    }

    /**
     * Build an adaptive variant for a student.
     *
     * Returns the same format as OgeVariantBuilderService::build() for compatibility.
     *
     * @return array{tasks: array, variantNumber: int, selectedZadaniya: array, adaptive: bool}
     */
    public function buildAdaptiveVariant(int $studentId, ?string $hash = null): array
    {
        $hash = $hash ?? $this->generateHash();
        $weights = $this->analyticsService->getTopicWeights($studentId);

        // If no mastery data, fall back to standard builder
        if (empty($weights)) {
            $result = $this->variantBuilder->build($hash);
            $result['adaptive'] = false;
            return $result;
        }

        $selectedZadaniya = $this->selectAdaptiveZadaniya($studentId, $weights);

        // Fall back to standard if selection failed
        if (empty($selectedZadaniya)) {
            $result = $this->variantBuilder->build($hash);
            $result['adaptive'] = false;
            return $result;
        }

        $result = $this->variantBuilder->build($hash, $selectedZadaniya);
        $result['adaptive'] = true;
        return $result;
    }

    /**
     * Select zadaniya weighted by student's mastery profile.
     *
     * @param array<string, float> $topicWeights topic_id => weight (higher = weaker)
     * @return array<int, string> Zadaniya IDs like ["06_1_1", "07_2_3", ...]
     */
    private function selectAdaptiveZadaniya(int $studentId, array $topicWeights): array
    {
        $allTopics = ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17'];

        // Build pool of available zadaniya per topic
        $zadaniyaPool = [];
        foreach ($allTopics as $topicId) {
            $blocks = $this->taskDataService->getBlocks($topicId);
            foreach ($blocks as $block) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    $zadaniyaPool[$topicId][] = "{$topicId}_{$block['number']}_{$zadanie['number']}";
                }
            }
        }

        // Assign a weight to every topic (topics with no mastery data get neutral weight 1.0)
        $weightedTopics = [];
        foreach ($allTopics as $topicId) {
            if (!isset($zadaniyaPool[$topicId]) || empty($zadaniyaPool[$topicId])) {
                continue;
            }
            $weightedTopics[$topicId] = $topicWeights[$topicId] ?? 1.0;
        }

        if (empty($weightedTopics)) {
            return [];
        }

        // Separate topics into weak and strong for the reinforcement split
        $weakTopics = [];
        $strongTopics = [];
        $neutralTopics = [];

        $masteryByTopic = StudentTopicMastery::where('student_id', $studentId)
            ->get()
            ->groupBy('topic_id');

        foreach ($weightedTopics as $topicId => $weight) {
            $topicRows = $masteryByTopic->get($topicId);
            if (!$topicRows || $topicRows->sum('attempts_count') < 2) {
                $neutralTopics[] = $topicId;
                continue;
            }

            $avgMastery = $topicRows->avg('mastery_score');
            if ($avgMastery >= 0.75) {
                $strongTopics[] = $topicId;
            } else {
                $weakTopics[] = $topicId;
            }
        }

        // Determine how many topics go to weak practice vs reinforcement
        $totalTopicSlots = count($weightedTopics);
        $reinforcementSlots = max(1, (int) round($totalTopicSlots * self::REINFORCEMENT_RATIO));

        // Pick topics: weak/neutral first, then reinforcement from strong
        $selected = [];

        // Fill weak + neutral slots via weighted sampling
        $weakAndNeutral = array_merge($weakTopics, $neutralTopics);
        $weakSlots = $totalTopicSlots - $reinforcementSlots;

        // If not enough weak topics, reduce reinforcement
        if (count($weakAndNeutral) < $weakSlots) {
            $weakSlots = count($weakAndNeutral);
            $reinforcementSlots = $totalTopicSlots - $weakSlots;
        }

        $pickedWeak = $this->weightedSample($weakAndNeutral, $weightedTopics, $weakSlots);
        foreach ($pickedWeak as $topicId) {
            $pool = $zadaniyaPool[$topicId] ?? [];
            if (!empty($pool)) {
                $selected[] = $pool[array_rand($pool)];
            }
        }

        // Fill reinforcement from strong topics (uniform random)
        if (!empty($strongTopics) && $reinforcementSlots > 0) {
            shuffle($strongTopics);
            $pickedStrong = array_slice($strongTopics, 0, $reinforcementSlots);
            foreach ($pickedStrong as $topicId) {
                $pool = $zadaniyaPool[$topicId] ?? [];
                if (!empty($pool)) {
                    $selected[] = $pool[array_rand($pool)];
                }
            }
        }

        // If we didn't fill all slots (e.g., no strong topics), fill from remaining weak
        $remainingTopics = array_diff(array_keys($weightedTopics), $pickedWeak, $strongTopics);
        while (count($selected) < $totalTopicSlots && !empty($remainingTopics)) {
            $topicId = array_shift($remainingTopics);
            $pool = $zadaniyaPool[$topicId] ?? [];
            if (!empty($pool)) {
                $selected[] = $pool[array_rand($pool)];
            }
        }

        return $selected;
    }

    /**
     * Weighted random sampling without replacement.
     *
     * @param array<int, string> $items
     * @param array<string, float> $weights
     * @return array<int, string>
     */
    private function weightedSample(array $items, array $weights, int $count): array
    {
        if ($count <= 0 || empty($items)) {
            return [];
        }

        $count = min($count, count($items));
        $remaining = $items;
        $selected = [];

        for ($i = 0; $i < $count; $i++) {
            $totalWeight = 0;
            foreach ($remaining as $item) {
                $totalWeight += ($weights[$item] ?? 1.0);
            }

            if ($totalWeight <= 0) {
                break;
            }

            $rand = mt_rand() / mt_getrandmax() * $totalWeight;
            $cumulative = 0;

            foreach ($remaining as $key => $item) {
                $cumulative += ($weights[$item] ?? 1.0);
                if ($rand <= $cumulative) {
                    $selected[] = $item;
                    unset($remaining[$key]);
                    $remaining = array_values($remaining);
                    break;
                }
            }
        }

        return $selected;
    }

    private function generateHash(): string
    {
        return substr(md5(uniqid((string) mt_rand(), true)), 0, 6);
    }
}
