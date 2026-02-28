<?php

namespace App\Console\Commands;

use App\Services\TaskDataService;
use Illuminate\Console\Command;

class SetTaskStatus extends Command
{
    protected $signature = 'tasks:set-status
        {topicId : Topic ID (e.g. 06, 07, 15)}
        {taskIds : Comma-separated task IDs (e.g. 1,2,3,7,8)}
        {--status=production : Status to set (production or draft)}';

    protected $description = 'Set status for specific tasks in a topic JSON file';

    public function handle(TaskDataService $taskDataService): int
    {
        $topicId = str_pad($this->argument('topicId'), 2, '0', STR_PAD_LEFT);
        $taskIds = array_map('intval', explode(',', $this->argument('taskIds')));
        $status = $this->option('status');

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

        $updated = 0;
        $notFound = [];

        foreach ($data['blocks'] as $bi => $block) {
            foreach ($block['zadaniya'] ?? [] as $zi => $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $ti => $task) {
                    if (in_array((int) ($task['id'] ?? 0), $taskIds, true)) {
                        $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['status'] = $status;
                        $updated++;
                    }
                }
            }
        }

        // Find which IDs were not found
        $foundIds = [];
        foreach ($data['blocks'] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if (in_array((int) ($task['id'] ?? 0), $taskIds, true)) {
                        $foundIds[] = (int) $task['id'];
                    }
                }
            }
        }
        $notFound = array_diff($taskIds, $foundIds);

        if ($updated > 0) {
            $taskDataService->saveTopicData($topicId, $data);
            $this->info("Updated {$updated} tasks to '{$status}' in topic {$topicId}");
        }

        if (!empty($notFound)) {
            $this->warn('Task IDs not found: ' . implode(', ', $notFound));
        }

        if ($updated === 0) {
            $this->error('No tasks were updated');
            return 1;
        }

        return 0;
    }
}
