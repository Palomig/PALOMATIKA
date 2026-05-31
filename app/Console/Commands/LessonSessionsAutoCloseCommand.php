<?php

namespace App\Console\Commands;

use App\Models\LessonSession;
use App\Services\LessonSessionService;
use Illuminate\Console\Command;

class LessonSessionsAutoCloseCommand extends Command
{
    protected $signature = 'lesson-sessions:auto-close
                            {--hours=3 : Auto-close live sessions older than this many hours since start}';

    protected $description = 'Завершает забытые live-сессии: если starts_at > N часов назад, переводит в ended.';

    public function handle(LessonSessionService $sessions): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $stale = LessonSession::where('status', LessonSession::STATUS_LIVE)
            ->where('starts_at', '<', $cutoff)
            ->get();

        if ($stale->isEmpty()) {
            $this->info("No stale live sessions (older than {$hours}h).");
            return self::SUCCESS;
        }

        $closed = 0;
        foreach ($stale as $session) {
            try {
                $sessions->end($session);
                $closed++;
            } catch (\Throwable $e) {
                $this->warn("Failed to close session #{$session->id}: {$e->getMessage()}");
            }
        }

        $this->info("Auto-closed {$closed}/{$stale->count()} stale sessions (live > {$hours}h).");
        return self::SUCCESS;
    }
}
