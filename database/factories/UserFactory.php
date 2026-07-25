<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attributes = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];

        // Привязанный телеграм — норма для живого аккаунта: ученик без него
        // упирается в обязательный экран /link-telegram.
        //
        // Часть тестов поднимает свою урезанную таблицу users в sqlite, поэтому
        // колонку добавляем только если она реально есть.
        if ($this->usersTableHasTelegramColumns()) {
            $attributes['telegram_chat_id'] = fake()->unique()->numberBetween(100_000_000, 999_999_999);
            $attributes['telegram_linked_at'] = now();
        }

        return $attributes;
    }

    private function usersTableHasTelegramColumns(): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasColumn('users', 'telegram_chat_id');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Ученик, который ещё не привязал телеграм (упрётся в гейт). */
    public function withoutTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
