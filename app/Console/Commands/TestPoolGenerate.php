<?php

namespace App\Console\Commands;

use App\Services\OgeVariantPoolService;
use Illuminate\Console\Command;

class TestPoolGenerate extends Command
{
    protected $signature = 'pool:test-generate {type} {--full : Test full flow with DB writes}';
    protected $description = 'Test variant generation for a pool type';

    public function handle(OgeVariantPoolService $poolService): int
    {
        $type = $this->argument('type');
        $full = $this->option('full');

        if ($full) {
            return $this->testFullFlow($poolService, $type);
        }

        return $this->testTaskGeneration($poolService, $type);
    }

    private function testTaskGeneration(OgeVariantPoolService $poolService, string $type): int
    {
        $this->info("Testing generateVariantTasks('{$type}')...");

        try {
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

            // Also test extractTaskRefs
            $ref2 = new \ReflectionMethod($poolService, 'extractTaskRefs');
            $ref2->setAccessible(true);
            $refs = $ref2->invoke($poolService, $tasks);
            $this->info("extractTaskRefs returned " . count($refs) . " refs");
            foreach ($refs as $r) {
                $this->line(sprintf("  topic=%s block=%d zadanie=%d task_id=%d",
                    $r['topic_id'], $r['block_number'], $r['zadanie_number'], $r['task_id']));
            }

            $fp = $poolService->computeFingerprint($refs);
            $this->info("Fingerprint: " . $fp);
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            $this->error('at ' . $e->getFile() . ':' . $e->getLine());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function testFullFlow(OgeVariantPoolService $poolService, string $type): int
    {
        $this->info("Testing FULL generateNewPoolVariant('{$type}') with DB writes...");

        try {
            $ref = new \ReflectionMethod($poolService, 'generateNewPoolVariant');
            $ref->setAccessible(true);
            $variant = $ref->invoke($poolService, $type);

            $this->info("SUCCESS! Created variant:");
            $this->line("  hash: {$variant->hash}");
            $this->line("  id: {$variant->id}");
            $this->line("  mode: {$variant->mode}");
            $this->line("  title: {$variant->title}");
            $tasks = $variant->config_json['tasks'] ?? [];
            $this->line("  tasks count: " . count($tasks));
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            $this->error('at ' . $e->getFile() . ':' . $e->getLine());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
