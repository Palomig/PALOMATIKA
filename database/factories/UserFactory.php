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
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];

        // `email_verified_at` — наследство первых версий, когда вход был по
        // почте. Сейчас авторизация идёт через телеграм, и в части тестов
        // таблица users поднимается урезанной — тогда вставка с этой колонкой
        // валила прогон целиком («Unknown column email_verified_at»), причём
        // только в связке: по отдельности тот же тест проходил. Ставим поле
        // лишь там, где оно есть, — тем же приёмом, что и телеграм-колонки.
        if ($this->usersTableHasColumn('email_verified_at')) {
            $attributes['email_verified_at'] = now();
        }

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
        return $this->usersTableHasColumn('telegram_chat_id');
    }

    private function usersTableHasColumn(string $column): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasColumn('users', $column);
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
     * Аккаунт без подтверждённой почты — состояние из первых версий, когда
     * вход был по email. Оставлено для тестов, которые его ещё используют.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
