<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlushVariantPool extends Command
{
    protected $signature = 'pool:flush {--type= : Only flush specific type (full, mixed, algebra, geometry)} {--exam-type= : Only flush a specific exam track (oge, vpr_5, vpr_6, vpr_7, vpr_8, ege)}';
    protected $description = 'Expire all active pool entries so fresh variants are generated';

    public function handle(): int
    {
        $query = DB::table('oge_variant_pool')->where('status', 'active');

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        if ($examType = $this->option('exam-type')) {
            $query->where('exam_type', $examType);
        }

        $count = $query->update(['status' => 'deactivated']);

        $this->info("Expired {$count} pool entries. New variants will be generated on next request.");

        return 0;
    }
}
