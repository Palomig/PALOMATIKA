<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlushUserSessions extends Command
{
    protected $signature = 'user:flush-sessions {user_id}';
    protected $description = 'Delete all sessions for a specific user';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $deleted = DB::table('sessions')->where('user_id', $userId)->delete();
        $this->info("Deleted {$deleted} session(s) for user {$userId}.");

        // Also clear remember token
        DB::table('users')->where('id', $userId)->update(['remember_token' => null]);
        $this->info("Cleared remember_token for user {$userId}.");

        return 0;
    }
}
