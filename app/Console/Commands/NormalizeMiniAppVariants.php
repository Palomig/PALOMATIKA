<?php

namespace App\Console\Commands;

use App\Models\OgeVariant;
use App\Services\MiniAppTaskCanonicalizer;
use Illuminate\Console\Command;

class NormalizeMiniAppVariants extends Command
{
    protected $signature = 'variants:normalize-miniapp';
    protected $description = 'Normalize config_json tasks for all miniapp variants (fix legacy statements, options, etc.)';

    public function handle(MiniAppTaskCanonicalizer $canonicalizer): int
    {
        $variants = OgeVariant::where('source', 'miniapp')->get();

        $normalized = 0;
        $deleted = 0;
        $skipped = 0;

        foreach ($variants as $variant) {
            // Delete variants with no attempts
            if ($variant->attempts()->count() === 0) {
                $variant->delete();
                $deleted++;
                continue;
            }

            $config = is_array($variant->config_json) ? $variant->config_json : [];
            $tasks = $config['tasks'] ?? null;

            if (!is_array($tasks) || empty($tasks)) {
                $skipped++;
                continue;
            }

            $changed = false;
            $normalizedTasks = [];

            foreach ($tasks as $task) {
                if (!is_array($task)) {
                    $normalizedTasks[] = $task;
                    continue;
                }

                $result = $canonicalizer->normalizeForUi($task);
                $normalizedTasks[] = $result;

                if ($result !== $task) {
                    $changed = true;
                }
            }

            if ($changed) {
                $config['tasks'] = $normalizedTasks;
                $variant->forceFill(['config_json' => $config])->save();
                $normalized++;
            } else {
                $skipped++;
            }
        }

        $this->info("Done. Deleted: {$deleted}, Normalized: {$normalized}, Unchanged: {$skipped}");

        return 0;
    }
}
