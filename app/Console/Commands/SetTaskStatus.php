<?php

namespace App\Console\Commands;

use App\Services\TaskDataService;
use Illuminate\Console\Command;

class SetTaskStatus extends Command
{
    protected $signature = 'tasks:set-status
        {topicId : Topic ID (e.g. 06, 07, 15)}
        {taskIds : Comma-separated task IDs (e.g. 1,2,3,7,8) or "all" for all tasks}
        {--status=production : Status to set (production or draft)}
        {--block= : Only affect tasks in this block number}';

    protected $description = 'Set status for specific tasks in a topic JSON file';

    public function handle(TaskDataService $taskDataService): int
    {
        $topicId = str_pad($this->argument('topicId'), 2, '0', STR_PAD_LEFT);
        $taskIdsArg = $this->argument('taskIds');
        $allTasks = strtolower($taskIdsArg) === 'all';
        $taskIds = $allTasks ? [] : array_map('intval', explode(',', $taskIdsArg));
        $status = $this->option('status');
        $blockFilter = $this->option('block') !== null ? (int) $this->option('block') : null;

        if (!in_array($status, ['draft', 'production'])) {
            $this->error('Status must be "draft" or "production"');
            return 1;
        }

        if (!$taskDataService->topicDataExists($topicId)) {
            $this->error("Topic {$topicId} JSON file not found");
            return 1;
        }

        $data = $taskDataService->getTopicData($topicId);
        if (empty($data['blocks'])) {
            $this->error("Topic {$topicId} has no blocks");
            return 1;
        }

        $keys = [];
        $foundIds = [];

        foreach ($data['blocks'] as $block) {
            $blockNumber = (int) ($block['number'] ?? 0);
            if ($blockFilter !== null && $blockNumber !== $blockFilter) {
                continue;
            }
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $zadanieNumber = (int) ($zadanie['number'] ?? 0);
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $id = (int) ($task['id'] ?? 0);
                    if ($allTasks || in_array($id, $taskIds, true)) {
                        $keys[] = sprintf('topic_%s_block_%d_zadanie_%d_task_%d', $topicId, $blockNumber, $zadanieNumber, $id);
                        $foundIds[] = $id;
                    }
                }
            }
        }

        if (empty($keys)) {
            $this->error('No matching tasks found');
            return 1;
        }

        $updated = $taskDataService->bulkUpsertStatusByTaskKeys($topicId, $keys, $status);
        $blockInfo = $blockFilter !== null ? " block {$blockFilter}" : '';
        $this->info("Updated {$updated} task records to '{$status}' in topic {$topicId}{$blockInfo} (DB storage)");

        if (!$allTasks) {
            $notFound = array_values(array_diff($taskIds, array_values(array_unique($foundIds))));
            if (!empty($notFound)) {
                $this->warn('Task IDs not found: ' . implode(', ', $notFound));
            }
        }

        return 0;
    }
}
