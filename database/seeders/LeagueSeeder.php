<?php

namespace Database\Seeders;

use App\Models\League;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            ['slug' => 'bronze', 'name' => 'Бронзовая', 'level' => 1, 'color' => '#CD7F32', 'icon' => '🥉', 'promote_top' => 10, 'demote_bottom' => 0],
            ['slug' => 'silver', 'name' => 'Серебряная', 'level' => 2, 'color' => '#C0C0C0', 'icon' => '🥈', 'promote_top' => 10, 'demote_bottom' => 5],
            ['slug' => 'gold', 'name' => 'Золотая', 'level' => 3, 'color' => '#FFD700', 'icon' => '🥇', 'promote_top' => 10, 'demote_bottom' => 5],
            ['slug' => 'platinum', 'name' => 'Платиновая', 'level' => 4, 'color' => '#E5E4E2', 'icon' => '💠', 'promote_top' => 5, 'demote_bottom' => 5],
            ['slug' => 'diamond', 'name' => 'Алмазная', 'level' => 5, 'color' => '#B9F2FF', 'icon' => '💎', 'promote_top' => 3, 'demote_bottom' => 5],
            ['slug' => 'master', 'name' => 'Мастер', 'level' => 6, 'color' => '#9B30FF', 'icon' => '👑', 'promote_top' => 0, 'demote_bottom' => 10],
        ];

        foreach ($leagues as $leagueData) {
            League::create($leagueData);
        }
    }
}
