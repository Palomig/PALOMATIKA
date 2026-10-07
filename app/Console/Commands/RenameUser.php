<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Смена имени ученика. Имя приходит из телеграма один раз, при создании
 * аккаунта (повторный вход его не трогает), и нередко это ник вроде «Flux✌️».
 * Псевдоним учителя виден только ему, а в уроке, рейтингах и у других
 * учителей остаётся ник — поэтому правим само имя, через deploy-вебхук.
 *
 * - php artisan user:rename 113 --name=Савва
 * - php artisan user:rename 113 --name="Савва Иванов" --dry-run
 */
class RenameUser extends Command
{
    protected $signature = 'user:rename
                            {id : ID пользователя}
                            {--name= : Новое имя}
                            {--dry-run : Показать изменение без записи}';

    protected $description = 'Переименовать пользователя';

    public function handle(): int
    {
        $name = trim((string) $this->option('name'));
        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Укажите --name (до 255 символов).');
            return self::FAILURE;
        }

        $user = User::find((int) $this->argument('id'));
        if (!$user) {
            $this->error('Не найден: #' . $this->argument('id'));
            return self::FAILURE;
        }

        $this->line("#{$user->id} {$user->name} → {$name}");

        if ($this->option('dry-run')) {
            $this->info('--dry-run: в базу не писали');
            return self::SUCCESS;
        }

        $old = $user->name;
        $user->name = $name;
        $user->save();
        Log::info("user:rename: #{$user->id} {$old} → {$name}");

        $this->info('Готово.');
        return self::SUCCESS;
    }
}
