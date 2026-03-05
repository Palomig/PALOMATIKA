<?php

namespace App\Console\Commands;

use App\Models\TaskStatus;
use App\Services\TaskDataService;
use Illuminate\Console\Command;

class ImportTaskStatuses extends Command
{
    protected $signature = 'task-statuses:import {--topic=}';

    protected $description = 'Import task statuses from topic JSON files into DB task_statuses table';

    public function handle(TaskDataService $taskDataService): int
    {
        $topics = $this->option('topic')
            ? [str_pad((string) $this->option('topic'), 2, '0', STR_PAD_LEFT)]
            : array_keys($taskDataService->getAllTopicsMeta());

        $total = 0;
        foreach ($topics as $topicId) {
            if (!$taskDataService->topicDataExists($topicId)) {
                continue;
            }

            $data = $taskDataService->getTopicData($topicId);
            $rows = [];
            $now = now();

            foreach ($data['blocks'] ?? [] as $block) {
                $blockNumber = (int) ($block['number'] ?? 0);
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    $zadanieNumber = (int) ($zadanie['number'] ?? 0);

                    foreach ($zadanie['tasks'] ?? [] as $task) {
                        $id = (int) ($task['id'] ?? 0);
                        if ($id < 1) {
                            continue;
                        }
                        $rows[] = [
                            'topic_id' => $topicId,
                            'task_key' => sprintf('topic_%s_block_%d_zadanie_%d_task_%d', $topicId, $blockNumber, $zadanieNumber, $id),
                            'status' => (string) ($task['status'] ?? 'draft'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    foreach ($zadanie['statements'] ?? [] as $statement) {
                        $id = (int) ($statement['id'] ?? 0);
                        if ($id < 1) {
                            continue;
                        }
                        $rows[] = [
                            'topic_id' => $topicId,
                            'task_key' => sprintf('topic_%s_block_%d_zadanie_%d_statement_%d', $topicId, $blockNumber, $zadanieNumber, $id),
                            'status' => (string) ($statement['status'] ?? 'draft'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if (!empty($rows)) {
                TaskStatus::query()->upsert($rows, ['topic_id', 'task_key'], ['status', 'updated_at']);
            }

            $count = count($rows);
            $total += $count;
            $this->info("{$topicId}: imported {$count}");
        }

        $this->newLine();
        $this->info("Done. Imported/updated {$total} task status records.");

        return 0;
    }
}
