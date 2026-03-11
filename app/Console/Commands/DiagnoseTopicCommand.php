<?php

namespace App\Console\Commands;

use App\Services\TaskDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiagnoseTopicCommand extends Command
{
    protected $signature = 'topics:diagnose {topic}';
    protected $description = 'Diagnose topic data availability (file, blocks, production tasks)';

    public function handle(TaskDataService $taskData): int
    {
        $topicId = str_pad($this->argument('topic'), 2, '0', STR_PAD_LEFT);
        $filePath = storage_path("app/tasks/topic_{$topicId}.json");

        $this->info("=== Diagnosing topic {$topicId} ===");
        $this->info("File path: {$filePath}");
        $this->info("File exists: " . (File::exists($filePath) ? 'YES' : 'NO'));

        if (File::exists($filePath)) {
            $size = File::size($filePath);
            $this->info("File size: {$size} bytes");

            $content = File::get($filePath);
            $data = json_decode($content, true);
            $this->info("JSON valid: " . ($data !== null ? 'YES' : 'NO (error: ' . json_last_error_msg() . ')'));

            if ($data) {
                $blocks = $data['blocks'] ?? [];
                $this->info("Blocks count: " . count($blocks));

                $totalTasks = 0;
                foreach ($blocks as $block) {
                    foreach ($block['zadaniya'] ?? [] as $z) {
                        $totalTasks += count($z['tasks'] ?? []);
                    }
                }
                $this->info("Total tasks in JSON: {$totalTasks}");
            }
        }

        // Check via TaskDataService (uses cache)
        $allBlocks = $taskData->getBlocks($topicId);
        $prodBlocks = $taskData->getBlocks($topicId, 'production');

        $allCount = 0;
        $prodCount = 0;
        foreach ($allBlocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $z) {
                $allCount += count($z['tasks'] ?? []);
            }
        }
        foreach ($prodBlocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $z) {
                $prodCount += count($z['tasks'] ?? []);
            }
        }

        $this->info("TaskDataService all blocks: " . count($allBlocks) . " ({$allCount} tasks)");
        $this->info("TaskDataService production blocks: " . count($prodBlocks) . " ({$prodCount} tasks)");

        $meta = $taskData->getTopicMeta($topicId);
        $this->info("Meta title: " . ($meta['title'] ?? 'NONE'));

        return 0;
    }
}
