<?php

namespace App\Console\Commands;

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlushMiniAppVariants extends Command
{
    protected $signature = 'variants:flush-miniapp';
    protected $description = 'Delete ALL miniapp-generated variants with their attempts and answers';

    public function handle(): int
    {
        $variantIds = OgeVariant::where('source', 'miniapp')->pluck('id');

        if ($variantIds->isEmpty()) {
            $this->info('No miniapp variants found.');
            return 0;
        }

        $attemptIds = OgeAttempt::whereIn('variant_id', $variantIds)->pluck('id');

        $answersDeleted = 0;
        $timingsDeleted = 0;

        if ($attemptIds->isNotEmpty()) {
            $answersDeleted = DB::table('oge_attempt_answers')
                ->whereIn('attempt_id', $attemptIds)
                ->delete();

            $timingsDeleted = DB::table('oge_attempt_task_timings')
                ->whereIn('attempt_id', $attemptIds)
                ->delete();
        }

        $attemptsDeleted = OgeAttempt::whereIn('variant_id', $variantIds)->delete();
        $variantsDeleted = OgeVariant::whereIn('id', $variantIds)->delete();

        $this->info("Flushed: {$variantsDeleted} variants, {$attemptsDeleted} attempts, {$answersDeleted} answers, {$timingsDeleted} timings");

        return 0;
    }
}
