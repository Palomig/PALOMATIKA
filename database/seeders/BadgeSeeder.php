<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // Streak badges
            ['slug' => 'streak-3', 'name' => 'Начинающий', 'description' => '3 дня подряд', 'condition_type' => 'streak', 'condition_value' => 3, 'rarity' => 'common', 'icon' => '🔥'],
            ['slug' => 'streak-7', 'name' => 'Неделя в деле', 'description' => '7 дней подряд', 'condition_type' => 'streak', 'condition_value' => 7, 'rarity' => 'common', 'icon' => '🔥'],
            ['slug' => 'streak-14', 'name' => 'Двухнедельный марафон', 'description' => '14 дней подряд', 'condition_type' => 'streak', 'condition_value' => 14, 'rarity' => 'rare', 'icon' => '💪'],
            ['slug' => 'streak-30', 'name' => 'Месяц без перерыва', 'description' => '30 дней подряд', 'condition_type' => 'streak', 'condition_value' => 30, 'rarity' => 'rare', 'icon' => '🏆'],
            ['slug' => 'streak-60', 'name' => 'Железная воля', 'description' => '60 дней подряд', 'condition_type' => 'streak', 'condition_value' => 60, 'rarity' => 'epic', 'icon' => '⚡'],
            ['slug' => 'streak-100', 'name' => 'Легенда стриков', 'description' => '100 дней подряд', 'condition_type' => 'streak', 'condition_value' => 100, 'rarity' => 'legendary', 'icon' => '👑'],

            // Tasks badges
            ['slug' => 'tasks-10', 'name' => 'Первые шаги', 'description' => '10 правильных задач', 'condition_type' => 'tasks', 'condition_value' => 10, 'rarity' => 'common', 'icon' => '📝'],
            ['slug' => 'tasks-50', 'name' => 'Полсотни', 'description' => '50 правильных задач', 'condition_type' => 'tasks', 'condition_value' => 50, 'rarity' => 'common', 'icon' => '📚'],
            ['slug' => 'tasks-100', 'name' => 'Сотня', 'description' => '100 правильных задач', 'condition_type' => 'tasks', 'condition_value' => 100, 'rarity' => 'rare', 'icon' => '🎯'],
            ['slug' => 'tasks-500', 'name' => 'Полтысячи', 'description' => '500 правильных задач', 'condition_type' => 'tasks', 'condition_value' => 500, 'rarity' => 'rare', 'icon' => '🌟'],
            ['slug' => 'tasks-1000', 'name' => 'Тысячник', 'description' => '1000 правильных задач', 'condition_type' => 'tasks', 'condition_value' => 1000, 'rarity' => 'epic', 'icon' => '💎'],
            ['slug' => 'tasks-5000', 'name' => 'Мастер математики', 'description' => '5000 правильных задач', 'condition_type' => 'tasks', 'condition_value' => 5000, 'rarity' => 'legendary', 'icon' => '🏅'],

            // Duel badges
            ['slug' => 'duel-first', 'name' => 'Первая дуэль', 'description' => 'Выиграй первую дуэль', 'condition_type' => 'duel', 'condition_value' => 1, 'rarity' => 'common', 'icon' => '⚔️'],
            ['slug' => 'duel-10', 'name' => 'Дуэлянт', 'description' => 'Выиграй 10 дуэлей', 'condition_type' => 'duel', 'condition_value' => 10, 'rarity' => 'rare', 'icon' => '🗡️'],
            ['slug' => 'duel-50', 'name' => 'Чемпион дуэлей', 'description' => 'Выиграй 50 дуэлей', 'condition_type' => 'duel', 'condition_value' => 50, 'rarity' => 'epic', 'icon' => '🏆'],

            // League badges
            ['slug' => 'league-silver', 'name' => 'Серебряная лига', 'description' => 'Достигни серебряной лиги', 'condition_type' => 'league', 'condition_value' => 2, 'rarity' => 'common', 'icon' => '🥈'],
            ['slug' => 'league-gold', 'name' => 'Золотая лига', 'description' => 'Достигни золотой лиги', 'condition_type' => 'league', 'condition_value' => 3, 'rarity' => 'rare', 'icon' => '🥇'],
            ['slug' => 'league-platinum', 'name' => 'Платиновая лига', 'description' => 'Достигни платиновой лиги', 'condition_type' => 'league', 'condition_value' => 4, 'rarity' => 'rare', 'icon' => '💠'],
            ['slug' => 'league-diamond', 'name' => 'Алмазная лига', 'description' => 'Достигни алмазной лиги', 'condition_type' => 'league', 'condition_value' => 5, 'rarity' => 'epic', 'icon' => '💎'],
            ['slug' => 'league-master', 'name' => 'Мастер', 'description' => 'Достигни лиги мастеров', 'condition_type' => 'league', 'condition_value' => 6, 'rarity' => 'legendary', 'icon' => '👑'],

            // Referral badges
            ['slug' => 'referral-1', 'name' => 'Приглашающий', 'description' => 'Пригласи 1 друга', 'condition_type' => 'referral', 'condition_value' => 1, 'rarity' => 'common', 'icon' => '🤝'],
            ['slug' => 'referral-5', 'name' => 'Командный игрок', 'description' => 'Пригласи 5 друзей', 'condition_type' => 'referral', 'condition_value' => 5, 'rarity' => 'rare', 'icon' => '👥'],
            ['slug' => 'referral-10', 'name' => 'Лидер', 'description' => 'Пригласи 10 друзей', 'condition_type' => 'referral', 'condition_value' => 10, 'rarity' => 'epic', 'icon' => '🌟'],

            // Special badges
            ['slug' => 'early-adopter', 'name' => 'Ранний пользователь', 'description' => 'Зарегистрировался в первый месяц', 'condition_type' => 'special', 'condition_value' => null, 'rarity' => 'epic', 'icon' => '🚀'],
            ['slug' => 'perfectionist', 'name' => 'Перфекционист', 'description' => '100% точность за неделю (мин. 50 задач)', 'condition_type' => 'special', 'condition_value' => null, 'rarity' => 'legendary', 'icon' => '✨'],
        ];

        foreach ($badges as $badgeData) {
            Badge::create($badgeData);
        }
    }
}
