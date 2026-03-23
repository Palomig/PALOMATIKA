<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QaSetupUsersCommand extends Command
{
    protected $signature = 'qa:setup-users';
    protected $description = 'Create or verify QA test users (student, teacher, admin, parent)';

    private const USERS = [
        ['name' => 'QA Student', 'email' => 'qa-student@palomatika.ru', 'role' => 'student', 'grade' => 9],
        ['name' => 'QA Teacher', 'email' => 'qa-teacher@palomatika.ru', 'role' => 'teacher', 'grade' => null],
        ['name' => 'QA Admin',   'email' => 'qa-admin@palomatika.ru',   'role' => 'admin',   'grade' => null],
        ['name' => 'QA Parent',  'email' => 'qa-parent@palomatika.ru',  'role' => 'parent',  'grade' => null],
    ];

    private const PASSWORD = 'QaTest2026!';

    /** QA Student is always linked to this parent as a child */
    private const QA_STUDENT_EMAIL = 'qa-student@palomatika.ru';

    public function handle(): int
    {
        foreach (self::USERS as $data) {
            $existing = User::where('email', $data['email'])->first();

            if ($existing) {
                // Ensure role, password and onboarding are correct
                $existing->role = $data['role'];
                $existing->password = Hash::make(self::PASSWORD);
                $existing->onboarding_completed_at = $existing->onboarding_completed_at ?? now();
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
                'onboarding_completed_at' => now(),
            ]);

            $this->line("CREATED: {$data['email']} (id={$user->id}, role={$data['role']})");
        }

        // Link QA Parent → QA Student in parent_student table
        $parent  = User::where('email', 'qa-parent@palomatika.ru')->first();
        $student = User::where('email', self::QA_STUDENT_EMAIL)->first();

        if ($parent && $student) {
            $exists = DB::table('parent_student')
                ->where('parent_id', $parent->id)
                ->where('student_id', $student->id)
                ->exists();

            if (!$exists) {
                DB::table('parent_student')->insert([
                    'parent_id'  => $parent->id,
                    'student_id' => $student->id,
                    'relation'   => 'parent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->line("LINKED: qa-parent (id={$parent->id}) → qa-student (id={$student->id})");
            } else {
                $this->line("LINK EXISTS: qa-parent (id={$parent->id}) → qa-student (id={$student->id})");
            }
        }

        $this->info('QA users ready.');
        return self::SUCCESS;
    }
}
