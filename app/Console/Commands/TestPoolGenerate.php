<?php

namespace App\Console\Commands;

use App\Services\OgeVariantPoolService;
use Illuminate\Console\Command;

class TestPoolGenerate extends Command
{
    protected $signature = 'pool:test-generate {type}';
    protected $description = 'Test variant generation for a pool type (dry run, no DB writes)';

    public function handle(OgeVariantPoolService $poolService): int
    {
        $type = $this->argument('type');

        $this->info("Testing generateVariantTasks('{$type}')...");

        try {
            // Use reflection to call protected method
            $ref = new \ReflectionMethod($poolService, 'generateVariantTasks');
            $ref->setAccessible(true);
            $tasks = $ref->invoke($poolService, $type);

            $this->info("Generated " . count($tasks) . " tasks:");
            foreach ($tasks as $i => $task) {
                $this->line(sprintf(
                    "  [%d] topic=%s block=%s zadanie=%s task_id=%s type=%s answer=%s",
                    $i + 1,
                    $task['topic_id'] ?? '?',
                    $task['block_number'] ?? '?',
                    $task['zadanie_number'] ?? '?',
                    $task['task_id'] ?? ($task['task']['id'] ?? '?'),
                    $task['type'] ?? '?',
                    mb_substr((string) ($task['correct_answer'] ?? $task['task']['answer'] ?? '?'), 0, 30)
                ));
            }
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            $this->error('at ' . $e->getFile() . ':' . $e->getLine());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
