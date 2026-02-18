<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use Illuminate\Console\Command;

class PruneAuditEventsCommand extends Command
{
    protected $signature = 'audit:prune {--days=90 : Keep events newer than N days}';
    protected $description = 'Delete audit events older than retention period';

    public function handle(): int
    {
        $days = max((int) $this->option('days'), 1);
        $threshold = now()->subDays($days);

        $deleted = AuditEvent::where('occurred_at', '<', $threshold)->delete();
        $this->info("Deleted {$deleted} audit events older than {$days} days.");

        return self::SUCCESS;
    }
}
