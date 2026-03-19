<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class QaSetupUsersCommand extends Command
{
    protected $signature = 'qa:setup-users';
    protected $description = 'Create or verify QA test users (student, teacher, admin)';

    private const USERS = [
        ['name' => 'QA Student', 'email' => 'qa-student@palomatika.ru', 'role' => 'student', 'grade' => '9К'],
        ['name' => 'QA Teacher', 'email' => 'qa-teacher@palomatika.ru', 'role' => 'teacher', 'grade' => null],
        ['name' => 'QA Admin',   'email' => 'qa-admin@palomatika.ru',   'role' => 'admin',   'grade' => null],
    ];

    private const PASSWORD = 'QaTest2026!';

    public function handle(): int
    {
        foreach (self::USERS as $data) {
            $existing = User::where('email', $data['email'])->first();

            if ($existing) {
                // Ensure role and password are correct
                $existing->role = $data['role'];
                $existing->password = Hash::make(self::PASSWORD);
                $existing->save();
                $this->line("EXISTS: {$data['email']} (id={$existing->id}, role={$data['role']}) — password reset");
                continue;
            }

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make(self::PASSWORD),
                'role'     => $data['role'],
                'grade'    => $data['grade'],
            ]);

            $this->line("CREATED: {$data['email']} (id={$user->id}, role={$data['role']})");
        }

        $this->info('QA users ready.');
        return self::SUCCESS;
    }
}
