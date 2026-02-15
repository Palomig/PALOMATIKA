<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    /**
     * Examples:
     * - php artisan user:promote-admin user@example.com
     * - php artisan user:promote-admin 15
     * - php artisan user:promote-admin --telegram-id=245710727
     */
    protected $signature = 'user:promote-admin
                            {identifier? : User email or internal user ID}
                            {--telegram-id= : Telegram user ID (oauth_id for provider telegram)}
                            {--dry-run : Show target user and exit without writing}';

    protected $description = 'Promote a user to admin role by email, user ID, or Telegram ID';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (!$user) {
            $this->error('User not found. Provide email, user ID, or --telegram-id.');
            return self::FAILURE;
        }

        $this->line("User: #{$user->id} {$user->name}");
        $this->line("Email: " . ($user->email ?? '—'));
        $this->line("OAuth: " . ($user->oauth_provider ?? '—') . ' / ' . ($user->oauth_id ?? '—'));
        $this->line("Current role: {$user->role}");

        if ($this->option('dry-run')) {
            $this->info('Dry-run mode: no changes applied.');
            return self::SUCCESS;
        }

        if ($user->role === 'admin') {
            $this->info('User already has admin role. No changes needed.');
            return self::SUCCESS;
        }

        $user->role = 'admin';
        $user->save();

        $this->info("Role updated successfully: user #{$user->id} is now admin.");
        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $telegramId = $this->option('telegram-id');
        if (!empty($telegramId)) {
            return User::query()
                ->where('oauth_provider', 'telegram')
                ->where('oauth_id', (string) $telegramId)
                ->first();
        }

        $identifier = $this->argument('identifier');
        if (empty($identifier)) {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::query()->where('email', $identifier)->first();
        }

        if (ctype_digit((string) $identifier)) {
            $byId = User::query()->find((int) $identifier);
            if ($byId) {
                return $byId;
            }
        }

        return User::query()
            ->where('oauth_provider', 'telegram')
            ->where('oauth_id', (string) $identifier)
            ->first();
    }
}
