<?php

namespace App\Console\Commands;

use App\Models\OgeAttempt;
use Illuminate\Console\Command;

class AbandonStaleAttempts extends Command
{
    protected $signature = 'oge:abandon-stale {--days=7 : Mark active attempts older than this many days as error}';
    protected $description = 'Mark stale active OGE attempts (no activity for N days) as error/abandoned';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = OgeAttempt::where('status', 'active')
            ->where('last_seen_at', '<', now()->subDays($days))
            ->update(['status' => 'error']);

        $this->info("Abandoned {$count} stale attempts (inactive > {$days} days).");

        return self::SUCCESS;
    }
}
