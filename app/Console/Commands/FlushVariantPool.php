<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlushVariantPool extends Command
{
    protected $signature = 'pool:flush {--type= : Only flush specific type (full, mixed, algebra, geometry)}';
    protected $description = 'Expire all active pool entries so fresh variants are generated';

    public function handle(): int
    {
        $query = DB::table('oge_variant_pool')->where('status', 'active');

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        $count = $query->update(['status' => 'expired']);

        $this->info("Expired {$count} pool entries. New variants will be generated on next request.");

        return 0;
    }
}
