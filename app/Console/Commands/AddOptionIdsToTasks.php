<?php

namespace App\Console\Commands;

use App\Services\TaskDataService;
use Illuminate\Console\Command;

class AddOptionIdsToTasks extends Command
{
    protected $signature = 'tasks:add-option-ids
        {--topic= : Process only one topic id (e.g. 07)}
        {--production-only : Process only production tasks}
        {--dry-run : Show changes without writing files}';

    protected $description = 'Convert legacy options arrays to stable id-based option objects and remap index answers';

    private const CHOICE_TYPES = [
        'choice', 'simple_choice', 'fraction_choice', 'interval_choice',
        'between_fractions', 'segment_choice', 'fraction_options', 'decimal_choice',
        'sqrt_choice', 'sqrt_interval', 'sqrt_segment', 'sqrt_options',
        'comparison', 'power_choice', 'compare_fractions', 'false_statements',
        'ordering', 'point_value', 'fraction_point', 'count_integers',
        'negative_segment', 'negative_interval',
    ];

    public function handle(TaskDataService $taskData): int
    {
        $topicOpt = $this->option('topic');
        $productionOnly = (bool) $this->option('production-only');
        $dryRun = (bool) $this->option('dry-run');

        $topics = $topicOpt
            ? [str_pad((string) $topicOpt, 2, '0', STR_PAD_LEFT)]
            : array_keys($taskData->getAllTopicsMeta());

        $totalTasksChanged = 0;
        $totalOptionsChanged = 0;
        $totalAnswersRemapped = 0;

        foreach ($topics as $topicId) {
            if (!$taskData->topicDataExists($topicId)) {
                continue;
            }

            $data = $taskData->getTopicData($topicId);
            $topicChanged = false;
            $topicTasksChanged = 0;
            $topicOptionsChanged = 0;
            $topicAnswersRemapped = 0;

            foreach (($data['blocks'] ?? []) as $bi => $block) {
                foreach (($block['zadaniya'] ?? []) as $zi => $zadanie) {
                    $type = (string) ($zadanie['type'] ?? '');

                    foreach (($zadanie['tasks'] ?? []) as $ti => $task) {
                        $status = (string) ($task['status'] ?? 'draft');
                        if ($productionOnly && $status !== 'production') {
                            continue;
                        }

                        $options = $task['options'] ?? null;
                        if (!is_array($options) || empty($options)) {
                            continue;
                        }

                        $normalizeResult = $this->normalizeOptions($options);
                        $normalizedOptions = $normalizeResult['options'];
                        $optionsChanged = (int) $normalizeResult['changed'];

                        $answerChanged = false;
                        $answer = $task['answer'] ?? null;
                        if ($this->isChoiceType($type) && $this->isNumericIndexAnswer($answer)) {
                            $idx = (int) $answer;
                            if ($idx >= 1 && $idx <= count($normalizedOptions)) {
                                $mapped = (string) ($normalizedOptions[$idx - 1]['id'] ?? '');
                                if ($mapped !== '' && (string) $answer !== $mapped) {
                                    $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['answer'] = $mapped;
                                    $answerChanged = true;
                                }
                            }
                        }

                        if ($optionsChanged > 0) {
                            $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['options'] = $normalizedOptions;
                            $topicOptionsChanged += $optionsChanged;
                        }

                        if ($optionsChanged > 0 || $answerChanged) {
                            $topicChanged = true;
                            $topicTasksChanged++;
                            if ($answerChanged) {
                                $topicAnswersRemapped++;
                            }
                        }
                    }
                }
            }

            if ($topicChanged && !$dryRun) {
                $taskData->saveTopicData($topicId, $data);
            }

            $totalTasksChanged += $topicTasksChanged;
            $totalOptionsChanged += $topicOptionsChanged;
            $totalAnswersRemapped += $topicAnswersRemapped;

            $mode = $dryRun ? 'DRY-RUN' : 'APPLIED';
            $this->info("Topic {$topicId} [{$mode}]: tasks_changed={$topicTasksChanged}, options_changed={$topicOptionsChanged}, answers_remapped={$topicAnswersRemapped}");
        }

        $this->newLine();
        $this->info('Summary: '
            . "tasks_changed={$totalTasksChanged}, "
            . "options_changed={$totalOptionsChanged}, "
            . "answers_remapped={$totalAnswersRemapped}");

        return 0;
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        $changed = 0;

        foreach (array_values($options) as $index => $option) {
            $defaultId = $this->optionIdByIndex($index);

            if (is_array($option)) {
                $existingId = isset($option['id']) && $option['id'] !== '' ? (string) $option['id'] : $defaultId;
                $label = (string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? '');

                $newOption = array_merge($option, [
                    'id' => $existingId,
                    'label' => $label,
                    'text' => (string) ($option['text'] ?? $label),
                    'value' => (string) ($option['value'] ?? $label),
                ]);

                if ($newOption !== $option) {
                    $changed++;
                }

                $normalized[] = $newOption;
                continue;
            }

            $label = (string) $option;
            $normalized[] = [
                'id' => $defaultId,
                'label' => $label,
                'text' => $label,
                'value' => $label,
            ];
            $changed++;
        }

        return ['options' => $normalized, 'changed' => $changed];
    }

    private function optionIdByIndex(int $index): string
    {
        if ($index < 26) {
            return chr(ord('a') + $index);
        }

        return 'o' . ($index + 1);
    }

    private function isChoiceType(string $type): bool
    {
        return in_array($type, self::CHOICE_TYPES, true);
    }

    private function isNumericIndexAnswer(mixed $answer): bool
    {
        if (is_int($answer)) {
            return $answer > 0;
        }

        if (!is_string($answer)) {
            return false;
        }

        return (bool) preg_match('/^[1-9][0-9]*$/', $answer);
    }
}
