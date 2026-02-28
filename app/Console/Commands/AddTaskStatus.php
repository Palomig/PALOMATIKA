<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddTaskStatus extends Command
{
    protected $signature = 'tasks:add-status {--default=draft : Default status to set}';
    protected $description = 'Add "status" field to all tasks in topic JSON files that don\'t already have one';

    public function handle(): int
    {
        $basePath = storage_path('app/tasks');
        $default = $this->option('default');

        if (!in_array($default, ['draft', 'production'])) {
            $this->error('Status must be "draft" or "production"');
            return 1;
        }

        $files = glob("{$basePath}/topic_*.json");
        // Exclude geometry files
        $files = array_filter($files, fn ($f) => !str_contains($f, '_geometry.json'));

        $totalUpdated = 0;

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $content = File::get($filePath);
            $data = json_decode($content, true);

            if (!$data || !isset($data['blocks'])) {
                $this->warn("Skipping {$filename}: no blocks found");
                continue;
            }

            $fileUpdated = 0;

            // Use index-based access to ensure mutations persist in the array
            foreach ($data['blocks'] as $bi => $block) {
                foreach ($block['zadaniya'] ?? [] as $zi => $zadanie) {
                    // For statements type, the statements themselves are tasks
                    if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'])) {
                        foreach ($zadanie['statements'] as $si => $statement) {
                            if (!isset($statement['status'])) {
                                $data['blocks'][$bi]['zadaniya'][$zi]['statements'][$si]['status'] = $default;
                                $fileUpdated++;
                            }
                        }
                    }

                    foreach ($zadanie['tasks'] ?? [] as $ti => $task) {
                        if (!isset($task['status'])) {
                            $data['blocks'][$bi]['zadaniya'][$zi]['tasks'][$ti]['status'] = $default;
                            $fileUpdated++;
                        }
                    }
                }
            }

            if ($fileUpdated > 0) {
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                File::put($filePath, $json);
                $this->info("{$filename}: added status to {$fileUpdated} tasks");
                $totalUpdated += $fileUpdated;
            } else {
                $this->line("{$filename}: all tasks already have status");
            }
        }

        $this->newLine();
        $this->info("Done. Updated {$totalUpdated} tasks total.");

        return 0;
    }
}
