<?php

namespace App\Services;

use App\Models\StudentTopicMastery;
use Illuminate\Support\Collection;

class StudentAnalyticsService
{
    /**
     * Get all mastery records for a student, grouped by topic_id.
     *
     * @return Collection<string, Collection<int, StudentTopicMastery>>
     */
    public function getMasteryByTopic(int $studentId): Collection
    {
        return StudentTopicMastery::where('student_id', $studentId)
            ->orderBy('topic_id')
            ->orderBy('task_type')
            ->get()
            ->groupBy('topic_id');
    }

    /**
     * Get weak zones: task types where mastery_score < threshold.
     * Returns sorted weakest-first.
     *
     * @return Collection<int, StudentTopicMastery>
     */
    public function getWeakZones(int $studentId, float $threshold = 0.5, int $minAttempts = 2): Collection
    {
        return StudentTopicMastery::where('student_id', $studentId)
            ->where('mastery_score', '<', $threshold)
            ->where('attempts_count', '>=', $minAttempts)
            ->orderBy('mastery_score', 'asc')
            ->get();
    }

    /**
     * Get strong zones: task types where mastery_score >= threshold.
     * Returns sorted strongest-first.
     *
     * @return Collection<int, StudentTopicMastery>
     */
    public function getStrongZones(int $studentId, float $threshold = 0.75, int $minAttempts = 2): Collection
    {
        return StudentTopicMastery::where('student_id', $studentId)
            ->where('mastery_score', '>=', $threshold)
            ->where('attempts_count', '>=', $minAttempts)
            ->orderBy('mastery_score', 'desc')
            ->get();
    }

    /**
     * Get a summary profile for the student: per-topic accuracy, time, mastery.
     *
     * @return array{
     *   topics: array<string, array{accuracy: float, avg_ms: int, mastery: float, attempts: int}>,
     *   overall_accuracy: float,
     *   total_attempts: int,
     *   weak_count: int,
     *   strong_count: int
     * }
     */
    public function getStudentProfile(int $studentId): array
    {
        $allMastery = StudentTopicMastery::where('student_id', $studentId)->get();

        if ($allMastery->isEmpty()) {
            return [
                'topics' => [],
                'overall_accuracy' => 0,
                'total_attempts' => 0,
                'weak_count' => 0,
                'strong_count' => 0,
            ];
        }

        $topicSummaries = [];
        $totalCorrect = 0;
        $totalAttempts = 0;
        $weakCount = 0;
        $strongCount = 0;

        foreach ($allMastery->groupBy('topic_id') as $topicId => $rows) {
            $topicAttempts = $rows->sum('attempts_count');
            $topicCorrect = $rows->sum('correct_count');
            $topicTotalMs = $rows->sum('total_active_ms');

            $topicAccuracy = $topicAttempts > 0 ? round($topicCorrect / $topicAttempts, 4) : 0;
            $topicAvgMs = $topicAttempts > 0 ? (int) ($topicTotalMs / $topicAttempts) : 0;
            $topicMastery = $rows->avg('mastery_score');

            $topicSummaries[$topicId] = [
                'accuracy' => $topicAccuracy,
                'avg_ms' => $topicAvgMs,
                'mastery' => round($topicMastery, 4),
                'attempts' => $topicAttempts,
                'subtypes' => $rows->map(fn ($r) => [
                    'task_type' => $r->task_type,
                    'svg_type' => $r->svg_type,
                    'section' => $r->section,
                    'accuracy' => $r->accuracy,
                    'mastery' => $r->mastery_score,
                    'attempts' => $r->attempts_count,
                    'avg_ms' => $r->avg_active_ms,
                ])->values()->all(),
            ];

            $totalCorrect += $topicCorrect;
            $totalAttempts += $topicAttempts;
        }

        foreach ($allMastery as $row) {
            if ($row->attempts_count >= 2) {
                if ($row->mastery_score < 0.5) {
                    $weakCount++;
                } elseif ($row->mastery_score >= 0.75) {
                    $strongCount++;
                }
            }
        }

        return [
            'topics' => $topicSummaries,
            'overall_accuracy' => $totalAttempts > 0 ? round($totalCorrect / $totalAttempts, 4) : 0,
            'total_attempts' => $totalAttempts,
            'weak_count' => $weakCount,
            'strong_count' => $strongCount,
        ];
    }

    /**
     * Get topic_ids where the student needs the most practice.
     * Used by AdaptiveVariantService to weight task selection.
     *
     * Returns an array of [topic_id => weight] where higher weight = more practice needed.
     *
     * @return array<string, float>
     */
    public function getTopicWeights(int $studentId): array
    {
        $allMastery = StudentTopicMastery::where('student_id', $studentId)->get();

        if ($allMastery->isEmpty()) {
            return [];
        }

        $weights = [];

        foreach ($allMastery->groupBy('topic_id') as $topicId => $rows) {
            // Average mastery for the topic; invert so low mastery = high weight
            $avgMastery = $rows->avg('mastery_score');

            // Recency factor: topics not practiced recently get a boost
            $lastAttempted = $rows->max('last_attempted_at');
            $daysSince = $lastAttempted ? now()->diffInDays($lastAttempted) : 30;
            $recencyBoost = min($daysSince / 14, 1.0); // max boost after 14 days

            // Weight = (1 - mastery) + recency boost
            // Range: 0.0 (mastered, just practiced) to 2.0 (failing, not practiced)
            $weights[$topicId] = round((1.0 - $avgMastery) + $recencyBoost * 0.5, 4);
        }

        return $weights;
    }
}
