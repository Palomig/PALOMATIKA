<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetReferrerCommand extends Command
{
    protected $signature = 'user:set-referrer
        {user_id : User ID to update}
        {referrer_id : Referrer user ID}';

    protected $description = 'Set referred_by_user_id for a user';

    public function handle(): int
    {
        $user = User::find($this->argument('user_id'));
        $referrer = User::find($this->argument('referrer_id'));

        if (!$user) {
            $this->error('User not found.');
            return 1;
        }
        if (!$referrer) {
            $this->error('Referrer not found.');
            return 1;
        }

        $old = $user->referred_by_user_id;
        $user->update(['referred_by_user_id' => $referrer->id]);

        $this->info("User #{$user->id} ({$user->name}): referred_by {$old} → {$referrer->id} ({$referrer->name})");
        return 0;
    }
}
