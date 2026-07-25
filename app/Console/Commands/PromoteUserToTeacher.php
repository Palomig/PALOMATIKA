<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToTeacher extends Command
{
    /**
     * Examples:
     * - php artisan user:promote-teacher user@example.com
     * - php artisan user:promote-teacher 15
     * - php artisan user:promote-teacher --telegram-id=245710727
     */
    protected $signature = 'user:promote-teacher
                            {identifier? : User email or internal user ID}
                            {--telegram-id= : Telegram user ID (oauth_id for provider telegram)}
                            {--dry-run : Show target user and exit without writing}';

    protected $description = 'Promote a user to teacher role by email, user ID, or Telegram ID';

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

        if ($user->role === 'teacher') {
            $this->info('User already has teacher role. No changes needed.');
            return self::SUCCESS;
        }

        $user->role = 'teacher';
        if ($user->evrium_teacher_id === null) {
            $user->evrium_teacher_id = User::nextEvriumTeacherId();
        }
        $user->save();

        $this->info("Role updated successfully: user #{$user->id} is now teacher (Evrium teacher_id={$user->evrium_teacher_id}).");
        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $telegramId = $this->option('telegram-id');
        if (!empty($telegramId)) {
            // После разделения ключей настоящий id живёт в telegram_chat_id,
            // но у части записей он ещё и в oauth_id — ищем по обоим.
            return User::query()->where('telegram_chat_id', (int) $telegramId)->first()
                ?? User::query()
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

        return User::query()->where('telegram_chat_id', (int) $identifier)->first()
            ?? User::query()
                ->where('oauth_provider', 'telegram')
                ->where('oauth_id', (string) $identifier)
                ->first();
    }
}

