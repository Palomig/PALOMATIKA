<?php

namespace App\Console\Commands;

use App\Services\TaskDataService;
use Illuminate\Console\Command;

class ValidateProductionTasks extends Command
{
    protected $signature = 'tasks:validate
        {--production-only : Validate only production tasks}
        {--topic= : Validate only one topic id (e.g. 07)}
        {--fix-types : Convert int answers to string for choice/matching tasks in JSON}';

    protected $description = 'Validate task JSON integrity rules for miniapp/scoring pipeline';

    public function handle(TaskDataService $taskData): int
    {
        $topicOpt = $this->option('topic');
        $productionOnly = (bool) $this->option('production-only');
        $fixTypes = (bool) $this->option('fix-types');

        $topics = $topicOpt
            ? [str_pad((string) $topicOpt, 2, '0', STR_PAD_LEFT)]
            : array_keys($taskData->getAllTopicsMeta());

        $warns = 0;
        $errors = 0;

        foreach ($topics as $topicId) {
            if (!$taskData->topicDataExists($topicId)) {
                continue;
            }

            $data = $taskData->getTopicData($topicId);
            $topicWarns = 0;
            $topicErrors = 0;
            $fixed = 0;
            $checked = 0;

            foreach ($data['blocks'] ?? [] as $bi => $block) {
                foreach ($block['zadaniya'] ?? [] as $zi => $zadanie) {
                    $type = (string) ($zadanie['type'] ?? '');
                    $tasks = $zadanie['tasks'] ?? [];

                    foreach ($tasks as $ti => $task) {
                        $status = (string) ($task['status'] ?? 'draft');
                        if ($productionOnly && $status !== 'production') {
                            continue;
                        }
                        $checked++;

                        $options = $task['options'] ?? $zadanie['options'] ?? null;
                        $answer = $task['answer'] ?? $zadanie['answer'] ?? null;

                        $isChoiceLike = in_array($type, ['choice', 'simple_choice', 'fraction_choice', 'interval_choice', 'matching', 'matching_signs'], true)
                            || is_array($options);

                        if ($isChoiceLike) {
                            if (empty($options) || !is_array($options) || count($options) < 2) {
                                $topicErrors++;
                                $this->line("[ERR] topic {$topicId} task {$task['id']} options_present/options_count");
                            }

                            if ($answer === null || $answer === '') {
                                $topicErrors++;
                                $this->line("[ERR] topic {$topicId} task {$task['id']} answer_present");
                                continue;
                            }

                            if (is_int($answer)) {
                                $topicWarns++;
                                $this->line("[WARN] topic {$topicId} task {$task['id']} answer_type_consistent int");
                                if ($fixTypes) {
                                    $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['answer'] = (string) $answer;
                                    $fixed++;
                                    $answer = (string) $answer;
                                }
                            }

                            if ($type === 'matching') {
                                if (!preg_match('/^[1-9]+$/', (string) $answer)) {
                                    $topicErrors++;
                                    $this->line("[ERR] topic {$topicId} task {$task['id']} matching_answer format");
                                }
                            } else {
                                if (preg_match('/^[1-9][0-9]*$/', (string) $answer) && is_array($options)) {
                                    $idx = (int) $answer;
                                    if ($idx > count($options)) {
                                        $topicErrors++;
                                        $this->line("[ERR] topic {$topicId} task {$task['id']} answer_in_range idx={$idx}, options=" . count($options));
                                    }
                                }
                            }
                        }
                    }

                    if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements']) && is_array($zadanie['statements'])) {
                        foreach ($zadanie['statements'] as $s) {
                            if (!array_key_exists('is_true', $s)) {
                                $topicErrors++;
                                $this->line("[ERR] topic {$topicId} zadanie {$zadanie['number']} statements_have_is_true");
                            }
                        }
                    }
                }
            }

            if ($fixTypes && $fixed > 0) {
                $taskData->saveTopicData($topicId, $data);
            }

            $warns += $topicWarns;
            $errors += $topicErrors;
            $this->info("Topic {$topicId}: checked={$checked}, warnings={$topicWarns}, errors={$topicErrors}, fixed={$fixed}");
        }

        $this->newLine();
        $this->info("Summary: warnings={$warns}, errors={$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
