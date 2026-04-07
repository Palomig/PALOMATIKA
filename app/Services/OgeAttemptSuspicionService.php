<?php

namespace App\Services;

use App\Models\OgeAttemptEvent;

class OgeAttemptSuspicionService
{
    /**
     * Analyze a single attempt. Returns:
     * ['is_suspicious' => bool, 'score' => int, 'reasons' => string[]]
     */
    public function analyze(int $attemptId): array
    {
        $events = OgeAttemptEvent::where('attempt_id', $attemptId)
            ->whereIn('event_type', ['answer_pasted', 'tab_away'])
            ->orderBy('seq')
            ->get();

        return $this->compute($events);
    }

    /**
     * Batch analyze multiple attempts. Returns [attemptId => analysis].
     */
    public function analyzeMany(array $attemptIds): array
    {
        if (empty($attemptIds)) {
            return [];
        }

        $byAttempt = OgeAttemptEvent::whereIn('attempt_id', $attemptIds)
            ->whereIn('event_type', ['answer_pasted', 'tab_away'])
            ->orderBy('attempt_id')
            ->orderBy('seq')
            ->get()
            ->groupBy('attempt_id');

        $result = [];
        foreach ($attemptIds as $id) {
            $result[$id] = $this->compute($byAttempt->get($id) ?? collect());
        }
        return $result;
    }

    private function compute($events): array
    {
        $reasons = [];
        $score = 0;

        $pasteEvents = $events->where('event_type', 'answer_pasted');

        foreach ($pasteEvents as $event) {
            $payload  = $event->payload_json ?? [];
            $awayMs   = (int) ($payload['away_ms_before'] ?? 0);
            $taskNum  = $event->task_number;
            $taskLabel = $taskNum ? "задание {$taskNum}" : 'ответ';

            if ($awayMs >= 30_000) {
                $awayStr = round($awayMs / 1000) . 'с';
                $reasons[] = "Вставка ответа ({$taskLabel}): приложение было скрыто {$awayStr} перед вставкой";
                $score += 3;
            } elseif ($awayMs >= 10_000) {
                $awayStr = round($awayMs / 1000) . 'с';
                $reasons[] = "Вставка ответа ({$taskLabel}): приложение было скрыто {$awayStr}";
                $score += 1;
            }
        }

        $pasteCount = $pasteEvents->count();
        if ($pasteCount >= 3) {
            $reasons[] = "Ответ вставлен из буфера {$pasteCount} раз за вариант";
            $score += 1;
        }

        return [
            'is_suspicious' => $score >= 2,
            'score'         => $score,
            'reasons'       => $reasons,
        ];
    }
}
