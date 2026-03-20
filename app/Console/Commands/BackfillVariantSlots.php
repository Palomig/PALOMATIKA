<?php

namespace App\Console\Commands;

use App\Models\OgeVariant;
use App\Services\VariantTaskNumberResolver;
use Illuminate\Console\Command;

class BackfillVariantSlots extends Command
{
    protected $signature = 'variants:backfill-slots {--dry-run : Show what would change without saving}';
    protected $description = 'Backfill slot/exam_number canonical fields into config_json tasks for all variants';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $updated = 0;
        $skipped = 0;
        $total = 0;

        OgeVariant::query()
            ->whereNotNull('config_json')
            ->chunkById(100, function ($variants) use ($dryRun, &$updated, &$skipped, &$total) {
                foreach ($variants as $variant) {
                    $total++;
                    $config = is_array($variant->config_json) ? $variant->config_json : [];
                    $tasks = $config['tasks'] ?? null;

                    if (!is_array($tasks) || empty($tasks)) {
                        $skipped++;
                        continue;
                    }

                    // Check if already backfilled (first task has both fields)
                    $firstTask = collect($tasks)->first(fn ($t) => is_array($t));
                    if ($firstTask && isset($firstTask['slot']) && isset($firstTask['exam_number'])) {
                        $skipped++;
                        continue;
                    }

                    $resolved = VariantTaskNumberResolver::resolveAll($tasks, $variant);
                    $newTasks = [];

                    foreach (array_values($tasks) as $index => $task) {
                        if (!is_array($task)) {
                            $newTasks[] = $task;
                            continue;
                        }

                        // Find the resolved entry for this task by index match
                        $entry = null;
                        foreach ($resolved as $r) {
                            if ($r['task'] === $task) {
                                $entry = $r;
                                break;
                            }
                        }

                        if ($entry) {
                            $task['slot'] = $entry['slot'];
                            $task['exam_number'] = $entry['exam_number'];
                        }

                        $newTasks[] = $task;
                    }

                    if (!$dryRun) {
                        $config['tasks'] = $newTasks;
                        $variant->forceFill(['config_json' => $config])->save();
                    }

                    $updated++;
                }
            });

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Done. Total: {$total}, Updated: {$updated}, Skipped: {$skipped}");

        return 0;
    }
}
