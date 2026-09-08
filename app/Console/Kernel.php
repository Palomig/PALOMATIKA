<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Audit prune is manual-only by request.

        // Ежегодный перевод классов — 1 июня в 03:00
        $schedule->command('grades:promote', ['--no-interaction'])
                 ->yearlyOn(6, 1, '03:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Каждые 15 минут — закрывать забытые live lesson-сессии (live > 3ч)
        $schedule->command('lesson-sessions:auto-close')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
