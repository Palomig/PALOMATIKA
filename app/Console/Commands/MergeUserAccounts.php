<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountMergeService;
use Illuminate\Console\Command;

class MergeUserAccounts extends Command
{
    protected $signature = 'users:merge
        {from : id аккаунта-донора (перестанет быть входной точкой)}
        {into : id канонического аккаунта}
        {--dry-run : только показать, что переедет}';

    protected $description = 'Сливает два аккаунта одного человека: данные донора переезжают на канонический.';

    public function handle(AccountMergeService $merger): int
    {
        $from = User::find((int) $this->argument('from'));
        $into = User::find((int) $this->argument('into'));

        if (!$from || !$into) {
            $this->error('Не найден один из аккаунтов.');
            return self::FAILURE;
        }

        if ($from->id === $into->id) {
            $this->error('Это один и тот же аккаунт.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->line("Донор:        #{$from->id} {$from->name} ({$from->role}, {$from->oauth_provider})");
        $this->line("Канонический: #{$into->id} {$into->name} ({$into->role}, {$into->oauth_provider})");

        $suggested = $merger->pickCanonical($from, $into);
        if ($suggested->id !== $into->id) {
            $this->warn("По ролям/возрасту каноническим выглядит #{$suggested->id}. Проверь направление слияния.");
        }

        $moved = $merger->merge($from, $into, $dryRun);

        if ($moved === []) {
            $this->info('Переносить нечего.');
        } else {
            foreach ($moved as $where => $count) {
                $this->line("  {$where}: {$count}");
            }
        }

        $this->info($dryRun ? 'Dry-run: ничего не изменено.' : "Готово: #{$from->id} слит в #{$into->id}.");

        return self::SUCCESS;
    }
}
