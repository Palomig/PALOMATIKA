<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Перевод отдельных учеников в другой класс. Класс ученик выбирает сам
 * на онбординге, а у учителя кнопки смены нет — переводить посреди года
 * (9 → 10 после вступительных) приходится отсюда, через deploy-вебхук.
 *
 * - php artisan user:set-grade 6 47 82 --grade=10
 * - php artisan user:set-grade 6 --grade=10 --letter=А --dry-run
 */
class SetUserGrade extends Command
{
    protected $signature = 'user:set-grade
                            {ids* : ID учеников}
                            {--grade= : Новый класс, 5–11}
                            {--letter= : Новая буква класса (по умолчанию не меняется)}
                            {--dry-run : Показать изменения без записи}';

    protected $description = 'Перевести выбранных учеников в другой класс';

    public function handle(): int
    {
        $grade = (int) $this->option('grade');
        if ($grade < 5 || $grade > 11) {
            $this->error('Укажите --grade от 5 до 11.');
            return self::FAILURE;
        }

        $letter = $this->option('letter');
        $ids = array_map('intval', (array) $this->argument('ids'));
        $users = User::query()->whereIn('id', $ids)->get()->keyBy('id');

        $missing = array_diff($ids, $users->keys()->all());
        if ($missing) {
            $this->error('Не найдены: ' . implode(', ', $missing));
            return self::FAILURE;
        }

        $notStudents = $users->filter(fn (User $u) => $u->role !== 'student');
        if ($notStudents->isNotEmpty()) {
            $this->error('Не ученики: ' . $notStudents->map(fn (User $u) => "#{$u->id} {$u->name} ({$u->role})")->implode(', '));
            return self::FAILURE;
        }

        foreach ($ids as $id) {
            $user = $users[$id];
            $newLetter = $letter ?? $user->grade_letter;
            $this->line("#{$user->id} {$user->name}: {$user->grade_num}{$user->grade_letter} → {$grade}{$newLetter}");

            if ($this->option('dry-run')) {
                continue;
            }

            $user->grade_num = $grade;
            if ($letter !== null) {
                $user->grade_letter = $letter;
            }
            $user->save();
            Log::info("user:set-grade: #{$user->id} → {$grade}{$newLetter}");
        }

        $this->info($this->option('dry-run') ? '--dry-run: в базу не писали' : 'Готово.');
        return self::SUCCESS;
    }
}
