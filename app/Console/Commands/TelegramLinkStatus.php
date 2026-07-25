<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TelegramLinkStatus extends Command
{
    protected $signature = 'users:telegram-status {--unlinked : показать поимённо тех, кому бот писать не может}';

    protected $description = 'Сводка по привязке телеграма: кому бот может писать, а кому нет.';

    public function handle(): int
    {
        $students = User::where('role', 'student')->whereNull('merged_into_id')->get();

        $linked = $students->whereNotNull('telegram_chat_id');
        $blocked = $linked->whereNotNull('telegram_blocked_at');

        $this->line('Учеников всего:        ' . $students->count());
        $this->line('С привязанным чатом:   ' . $linked->count() . ' (из них заблокировали бота: ' . $blocked->count() . ')');
        $this->line('Без привязки:          ' . $students->whereNull('telegram_chat_id')->count());
        $this->newLine();

        $this->line('По способу входа:');
        foreach ($students->groupBy(fn (User $u) => $u->oauth_provider ?? 'нет') as $provider => $group) {
            $withChat = $group->whereNotNull('telegram_chat_id')->count();
            $this->line(sprintf('  %-10s %2d всего, %2d с чатом', $provider, $group->count(), $withChat));
        }

        if ($this->option('unlinked')) {
            $this->newLine();
            $this->line('Без привязки:');
            foreach ($students->whereNull('telegram_chat_id')->sortBy('id') as $student) {
                $this->line(sprintf('  #%-4d %-30s %s', $student->id, $student->name, $student->oauth_provider ?? '—'));
            }
        }

        return self::SUCCESS;
    }
}
