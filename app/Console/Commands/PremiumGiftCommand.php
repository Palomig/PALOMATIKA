<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserGift;
use Illuminate\Console\Command;

class PremiumGiftCommand extends Command
{
    protected $signature = 'premium:gift
        {months : Number of months to gift}
        {--user= : Specific user ID}
        {--tg= : Specific Telegram oauth_id}
        {--all : All telegram users}
        {--exclude= : Comma-separated Telegram oauth_ids to exclude}
        {--message= : Custom gift message}';

    protected $description = 'Gift premium subscription to users';

    public function handle(): int
    {
        $months = (int) $this->argument('months');
        if ($months < 1 || $months > 24) {
            $this->error('Months must be between 1 and 24.');
            return 1;
        }

        $query = User::query();

        if ($this->option('user')) {
            $query->where('id', $this->option('user'));
        } elseif ($this->option('tg')) {
            $query->where('oauth_provider', 'telegram')->where('oauth_id', $this->option('tg'));
        } elseif ($this->option('all')) {
            $query->where('oauth_provider', 'telegram');
        } else {
            $this->error('Specify --user=ID, --tg=TELEGRAM_ID, or --all');
            return 1;
        }

        if ($this->option('exclude')) {
            $excludeIds = array_map('trim', explode(',', $this->option('exclude')));
            $query->whereNotIn('oauth_id', $excludeIds);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');
            return 0;
        }

        $message = $this->option('message') ?: "Вы получили Premium на {$months} мес!";

        $count = 0;
        foreach ($users as $user) {
            // Extend premium
            $from = ($user->tg_premium_until && $user->tg_premium_until->isFuture())
                ? $user->tg_premium_until
                : now();
            $user->update(['tg_premium_until' => $from->copy()->addMonths($months)]);

            // Create gift notification
            UserGift::create([
                'user_id' => $user->id,
                'gift_type' => 'premium_months',
                'payload' => [
                    'months' => $months,
                    'message' => $message,
                ],
            ]);

            $count++;
            $this->line("  #{$user->id} {$user->name} — premium until {$user->tg_premium_until->format('d.m.Y')}");
        }

        $this->info("Done! Gifted {$months} months premium to {$count} users.");
        return 0;
    }
}
