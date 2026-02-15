<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestStudent extends Command
{
    protected $signature = 'user:create-test-student
                            {email : Student email}
                            {password : Plain password (min 8 chars)}
                            {--name=Тестовый Ученик : Student display name}';

    protected $description = 'Create or update a local student account with email/password for manual testing';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $password = (string) $this->argument('password');
        $name = trim((string) $this->option('name'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email format.');
            return self::FAILURE;
        }

        if (mb_strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        /** @var User $user */
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name !== '' ? $name : 'Тестовый Ученик';
        $user->password = Hash::make($password);
        $user->role = 'student';

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        $this->info('Student account is ready.');
        $this->line('ID: ' . $user->id);
        $this->line('Email: ' . $user->email);
        $this->line('Role: ' . $user->role);

        return self::SUCCESS;
    }
}
