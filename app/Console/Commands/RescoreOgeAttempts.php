<?php

namespace App\Console\Commands;

use App\Models\OgeAttempt;
use App\Services\OgeAttemptService;
use Illuminate\Console\Command;

class RescoreOgeAttempts extends Command
{
    protected $signature = 'oge:rescore-attempts {--variant= : Variant hash filter} {--student= : Student ID filter}';

    protected $description = 'Rebuild OGE scoring rows for existing attempts';

    public function __construct(private readonly OgeAttemptService $attemptService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = OgeAttempt::query()->with('variant:id,hash');

        if ($variantHash = $this->option('variant')) {
            $query->whereHas('variant', fn ($q) => $q->where('hash', $variantHash));
        }

        if ($studentId = $this->option('student')) {
            $query->where('student_id', (int) $studentId);
        }

        $attempts = $query->orderBy('id')->get();
        if ($attempts->isEmpty()) {
            $this->warn('No attempts found.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($attempts->count());
        $bar->start();

        foreach ($attempts as $attempt) {
            $this->attemptService->rebuildScoring($attempt);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Rescored attempts: ' . $attempts->count());

        return self::SUCCESS;
    }
}
