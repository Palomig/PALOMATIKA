<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromoteGrades extends Command
{
    protected $signature   = 'grades:promote {--dry-run : Показать что будет изменено без реального обновления}';
    protected $description = 'Перевести учеников в следующий класс (запускать 1 июня ежегодно)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Считаем по текущим данным
        $counts = User::where('role', 'student')
            ->whereIn('grade_num', range(5, 11))
            ->selectRaw('grade_num, COUNT(*) as cnt')
            ->groupBy('grade_num')
            ->pluck('cnt', 'grade_num');

        $this->table(['Класс', 'Учеников', 'Станет'], collect($counts)->map(fn($cnt, $grade) => [
            $grade, $cnt, $grade < 11 ? $grade + 1 : '12 (выпускник)'
        ])->values()->all());

        if ($dryRun) {
            $this->warn('--dry-run: изменения НЕ применены.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Сначала переводим 11→12, потом 10→11 ... 5→6 (обратный порядок важен)
            foreach (range(11, 5) as $grade) {
                $count = User::where('role', 'student')
                    ->where('grade_num', $grade)
                    ->update(['grade_num' => $grade + 1]);

                $this->line("  {$grade} → " . ($grade + 1) . ": {$count} учеников");
                Log::info("grades:promote: {$count} students promoted from grade {$grade} to " . ($grade + 1));
            }
        });

        $this->info('Готово. Классы обновлены.');
        return self::SUCCESS;
    }
}
