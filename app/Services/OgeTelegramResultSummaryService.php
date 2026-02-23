<?php

namespace App\Services;

use App\Models\OgeAttempt;
use App\Support\OgeResultLinkBuilder;

class OgeTelegramResultSummaryService
{
    public function __construct(
        private readonly OgeAttemptService $attemptService,
        private readonly OgeResultLinkBuilder $linkBuilder,
    ) {
    }

    public function buildAttemptSummaryPayload(OgeAttempt $attempt): array
    {
        $result = $this->attemptService->buildAttemptResultPayload($attempt);
        $attemptData = $result['attempt'] ?? [];
        $summary = $result['summary'] ?? [];
        $taskRows = $result['tasks'] ?? [];

        $taskStatuses = array_map(function (array $taskRow): array {
            $status = (string) ($taskRow['status'] ?? 'unanswered');

            return [
                'task_number' => (int) ($taskRow['task_number'] ?? 0),
                'status' => $status,
                'code' => $this->statusCode($status),
            ];
        }, $taskRows);

        $telegramLinks = $this->linkBuilder->buildTelegramLinks($attempt);
        $variantResultsUrl = (string) ($telegramLinks['variant']['web_url'] ?? $this->linkBuilder->buildVariantResultsUrl($attempt));
        $attemptResultsUrl = (string) ($telegramLinks['attempt']['web_url'] ?? $this->linkBuilder->buildAttemptResultsUrl($attempt));
        $variantPreferredUrl = (string) ($telegramLinks['variant']['preferred_url'] ?? $variantResultsUrl);
        $attemptPreferredUrl = (string) ($telegramLinks['attempt']['preferred_url'] ?? $attemptResultsUrl);

        return [
            'contract' => [
                'name' => 'oge_attempt_telegram_result_summary',
                'version' => 1,
            ],
            'attempt' => [
                'id' => (int) ($attemptData['id'] ?? $attempt->id),
                'status' => (string) ($attemptData['status'] ?? $attempt->status),
                'variant_id' => (int) ($attemptData['variant_id'] ?? $attempt->variant_id),
                'variant_hash' => (string) ($attemptData['variant_hash'] ?? ''),
                'is_custom' => (bool) ($attemptData['is_custom'] ?? false),
                'student_id' => (int) ($attemptData['student_id'] ?? $attempt->student_id),
                'student' => $attemptData['student'] ?? null,
            ],
            'summary' => [
                'tasks_total' => (int) ($summary['tasks_total'] ?? 0),
                'answered_count' => (int) ($summary['answered_count'] ?? 0),
                'unanswered_count' => (int) ($summary['unanswered_count'] ?? 0),
                'correct_count' => (int) ($summary['correct_count'] ?? 0),
                'incorrect_count' => (int) ($summary['incorrect_count'] ?? 0),
                'unchecked_count' => (int) ($summary['unchecked_count'] ?? 0),
                'total_active_ms' => (int) ($summary['total_active_ms'] ?? 0),
                'away_ms_total' => (int) ($summary['away_ms_total'] ?? 0),
                'duration_ms' => isset($summary['duration_ms']) ? (is_null($summary['duration_ms']) ? null : (int) $summary['duration_ms']) : null,
            ],
            'telegram' => [
                'message_text' => $this->buildMessageText($attemptData, $summary, $taskStatuses, $variantPreferredUrl, $attemptPreferredUrl),
                'task_statuses' => $taskStatuses,
                'links' => [
                    'variant_results_url' => $variantResultsUrl,
                    'attempt_results_url' => $attemptResultsUrl,
                    'variant_results_mini_app_url' => $telegramLinks['variant']['mini_app_url'] ?? null,
                    'attempt_results_mini_app_url' => $telegramLinks['attempt']['mini_app_url'] ?? null,
                    'variant_results_button_url' => $telegramLinks['variant']['button_url'] ?? $variantResultsUrl,
                    'attempt_results_button_url' => $telegramLinks['attempt']['button_url'] ?? $attemptResultsUrl,
                    'variant_results_preferred_url' => $variantPreferredUrl,
                    'attempt_results_preferred_url' => $attemptPreferredUrl,
                    'variant_results_startapp_payload' => $telegramLinks['variant']['startapp_payload'] ?? null,
                    'attempt_results_startapp_payload' => $telegramLinks['attempt']['startapp_payload'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $attemptData
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $taskStatuses
     */
    private function buildMessageText(array $attemptData, array $summary, array $taskStatuses, string $variantResultsUrl, string $attemptResultsUrl): string
    {
        $variantHash = (string) ($attemptData['variant_hash'] ?? '');
        $attemptId = (int) ($attemptData['id'] ?? 0);
        $studentName = (string) (($attemptData['student']['name'] ?? '') ?: 'Unknown');
        $isCustom = (bool) ($attemptData['is_custom'] ?? false);
        $status = (string) ($attemptData['status'] ?? '');

        $taskLine = collect($taskStatuses)
            ->map(fn (array $row): string => ((int) $row['task_number']) . ':' . ((string) $row['code']))
            ->implode(' ');

        $lines = [
            'OGE Results',
            sprintf(
                'Variant %s | Attempt #%d | %s%s',
                $variantHash !== '' ? $variantHash : 'unknown',
                $attemptId,
                $studentName,
                $isCustom ? ' | custom' : ''
            ),
            sprintf(
                'Status: %s | Correct: %d | Incorrect: %d | Unchecked: %d | Unanswered: %d',
                $status !== '' ? $status : 'unknown',
                (int) ($summary['correct_count'] ?? 0),
                (int) ($summary['incorrect_count'] ?? 0),
                (int) ($summary['unchecked_count'] ?? 0),
                (int) ($summary['unanswered_count'] ?? 0),
            ),
            'Tasks: ' . $taskLine,
            'Open attempt: ' . $attemptResultsUrl,
            'Open variant: ' . $variantResultsUrl,
        ];

        return implode("\n", $lines);
    }

    private function statusCode(string $status): string
    {
        return match ($status) {
            'correct' => '+',
            'incorrect' => '-',
            'unchecked' => '?',
            default => '.',
        };
    }
}
