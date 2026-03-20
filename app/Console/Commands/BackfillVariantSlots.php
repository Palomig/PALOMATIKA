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

                    // Check if ALL tasks already have canonical fields
                    $allBackfilled = true;
                    foreach ($tasks as $task) {
                        if (is_array($task) && (!isset($task['slot']) || !isset($task['exam_number']))) {
                            $allBackfilled = false;
                            break;
                        }
                    }
                    if ($allBackfilled) {
                        $skipped++;
                        continue;
                    }

                    // Resolve each task by its index — avoids identity-matching bugs with duplicate tasks
                    $newTasks = [];
                    foreach (array_values($tasks) as $index => $task) {
                        if (!is_array($task)) {
                            $newTasks[] = $task;
                            continue;
                        }

                        $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
                        $task['slot'] = $numbers['slot'];
                        $task['exam_number'] = $numbers['exam_number'];
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
