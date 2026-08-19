<?php

namespace App\Console\Commands;

use App\Models\TaskGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Залить написанные разборы в банк заданий.
 *
 * Разборы второй части ОГЭ живут в `task_groups.payload.solution`, а сам банк
 * переехал в базу — значит, у текста разбора нет места в репозитории, где его
 * можно было бы отревьюить и откатить. Команда закрывает этот разрыв: исходник
 * лежит файлом, а команда переносит его в базу.
 *
 *   storage/app/tasks/solutions/{bank}/topic_{NN}/{номер задания}.html
 *
 * Файл — фрагмент HTML (без обёрток), тот же формат, что у прежних разборов:
 * <p>, <div class="formula">, <h3 class="sol-part">, <div class="answer">.
 * Формулы — KaTeX в \( \) и \[ \].
 *
 *   php artisan tasks:seed-solutions --topic=23 --dry-run
 *   php artisan tasks:seed-solutions
 */
class SeedTaskSolutions extends Command
{
    protected $signature = 'tasks:seed-solutions
        {--bank=oge : банк заданий}
        {--topic= : только одна тема}
        {--dry-run : только показать, что изменится}';

    protected $description = 'Залить написанные разборы из storage/app/tasks/solutions в банк';

    public function handle(): int
    {
        $bank = (string) $this->option('bank');
        $root = storage_path("app/tasks/solutions/{$bank}");
        if (!File::isDirectory($root)) {
            $this->warn("нет каталога {$root} — заливать нечего");
            return self::SUCCESS;
        }

        $written = $unchanged = $orphans = 0;
        $updates = [];

        foreach (File::directories($root) as $dir) {
            if (!preg_match('/topic_(\d+)$/', $dir, $m)) {
                continue;
            }
            $topic = $m[1];
            if ($this->option('topic') !== null && ltrim($topic, '0') !== ltrim((string) $this->option('topic'), '0')) {
                continue;
            }

            foreach (File::files($dir) as $file) {
                if ($file->getExtension() !== 'html') {
                    continue;
                }
                $number = (int) $file->getFilenameWithoutExtension();
                $solution = trim(File::get($file->getPathname()));
                if ($number <= 0 || $solution === '') {
                    continue;
                }

                // Разбор пишется под кураторскую группировку банка ФИПИ:
                // отключённый банк трогать нельзя, там свои разборы.
                $group = TaskGroup::query()
                    ->where('bank', $bank)->where('topic', $topic)
                    ->where('source', 'fipi')->where('zadanie_number', $number)
                    ->first();

                if ($group === null) {
                    $orphans++;
                    $this->warn("  нет группы: тема {$topic}, задание {$number}");
                    continue;
                }

                $payload = $group->payload ?? [];
                if (trim((string) ($payload['solution'] ?? '')) === $solution) {
                    $unchanged++;
                    continue;
                }

                $written++;
                $this->line(sprintf('  тема %s, задание %-2d — %s (%d символов)',
                    $topic, $number, $payload['solution'] ?? null ? 'обновлён' : 'добавлен', mb_strlen($solution)));
                $payload['solution'] = $solution;
                $updates[$group->id] = $payload;
            }
        }

        $this->newLine();
        $this->info(sprintf('%s: записей %d, без изменений %d, без группы %d',
            $this->option('dry-run') ? 'ПОДСЧЁТ' : 'Залито', $written, $unchanged, $orphans));

        if ($this->option('dry-run') || $updates === []) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $payload) {
                TaskGroup::query()->whereKey($id)->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
            }
        });

        return self::SUCCESS;
    }
}
