<?php

namespace App\Console\Commands;

use App\Services\OgeVariantPoolService;
use Illuminate\Console\Command;

class SyncVariantPool extends Command
{
    protected $signature = 'pool:sync';
    protected $description = 'Sync variant pool statuses based on current task production/draft statuses';

    public function handle(OgeVariantPoolService $poolService): int
    {
        $this->info('Syncing variant pool statuses...');

        $result = $poolService->syncAllVariantStatuses();

        $this->info("Total pool entries: {$result['total']}");
        $this->info("Activated: {$result['activated']}");
        $this->info("Deactivated: {$result['deactivated']}");

        $stats = $poolService->getStats();
        $this->newLine();
        $this->info('Pool statistics:');
        $this->table(
            ['Type', 'Active'],
            [
                ['Full', $stats['by_type']['full']],
                ['Geometry', $stats['by_type']['geometry']],
                ['Algebra', $stats['by_type']['algebra']],
                ['Mixed', $stats['by_type']['mixed']],
                ['Total Active', $stats['active']],
                ['Deactivated', $stats['deactivated']],
            ]
        );

        return 0;
    }
}
