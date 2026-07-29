<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\TaskBankRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Отключить прежний банк ОГЭ, не удаляя его.
 *
 * Задания помечаются `source = palomatika_legacy`: репозиторий их больше не
 * отдаёт, но строки остаются — по ним читается история попыток
 * (`oge_attempt_task_details`, 3310 строк с `task_fingerprint`).
 *
 * Команда отказывается работать, если банк-замена пуст: иначе тема осталась
 * бы без единого задания, и ученику нечего было бы решать.
 *
 *   php artisan tasks:retire-legacy --dry-run
 *   php artisan tasks:retire-legacy
 *   php artisan tasks:retire-legacy --restore   # вернуть обратно
 */
class RetireLegacyBank extends Command
{
    protected $signature = 'tasks:retire-legacy
        {--dry-run : только посчитать}
        {--restore : вернуть отключённые задания в выдачу}';

    protected $description = 'Пометить прежний банк ОГЭ как отключённый (или вернуть)';

    public function handle(): int
    {
        [$from, $to] = $this->option('restore')
            ? [TaskBankRepository::RETIRED, 'palomatika']
            : ['palomatika', TaskBankRepository::RETIRED];

        $groups = TaskGroup::query()->where('bank', 'oge')->where('source', $from);
        $groupCount = (clone $groups)->count();
        $taskCount = Task::query()->whereIn('task_group_id', (clone $groups)->select('id'))->count();

        if ($groupCount === 0) {
            $this->warn("нечего переключать: заданий ОГЭ с source={$from} нет");
            return self::SUCCESS;
        }

        if (!$this->option('restore')) {
            $replacement = TaskGroup::query()
                ->where('bank', 'oge')
                ->whereNotIn('source', ['palomatika', TaskBankRepository::RETIRED])
                ->count();
            if ($replacement === 0) {
                $this->error('в банке ОГЭ нет заданий на замену — сначала tasks:import-fipi');
                return self::FAILURE;
            }
            $this->line("  замена на месте: заданий из других источников — {$replacement}");
        }

        $this->line("  затрагивается: заданий {$groupCount}, задач {$taskCount}");

        if ($this->option('dry-run')) {
            $this->info('ПОДСЧЁТ: ничего не изменено');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($groups, $from, $to) {
            Task::query()
                ->whereIn('task_group_id', (clone $groups)->select('id'))
                ->where('source', $from)
                ->update(['source' => $to]);
            (clone $groups)->update(['source' => $to]);
        });

        $this->info($this->option('restore')
            ? 'Прежний банк ОГЭ возвращён в выдачу'
            : 'Прежний банк ОГЭ отключён; строки сохранены для истории попыток');

        return self::SUCCESS;
    }
}
