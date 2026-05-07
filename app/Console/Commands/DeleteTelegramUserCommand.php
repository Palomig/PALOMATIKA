<?php

namespace App\Console\Commands;

use App\Models\OgeAttempt;
use App\Models\User;
use Illuminate\Console\Command;

class DeleteTelegramUserCommand extends Command
{
    protected $signature = 'user:delete-telegram {telegram_id : Telegram oauth_id to delete}';
    protected $description = 'Delete a Telegram user and all related data by their Telegram ID';

    public function handle(): int
    {
        $telegramId = $this->argument('telegram_id');

        $users = User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $telegramId)
            ->get();

        if ($users->isEmpty()) {
            $this->error("No users found with telegram_id={$telegramId}");
            return 1;
        }

        $userIds = $users->pluck('id')->toArray();

        $this->info("Found {$users->count()} user(s): " . implode(', ', $userIds));

        // Delete related data
        $attempts = OgeAttempt::whereIn('student_id', $userIds)->count();
        OgeAttempt::whereIn('student_id', $userIds)->delete();
        $this->info("Deleted {$attempts} OGE attempts");

        // Delete users
        User::whereIn('id', $userIds)->delete();
        $this->info("Deleted {$users->count()} user(s)");

        return 0;
    }
}
