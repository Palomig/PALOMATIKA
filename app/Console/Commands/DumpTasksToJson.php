<?php

namespace App\Console\Commands;

use App\Models\TaskGroup;
use App\Services\TaskBankRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Обратная выгрузка банков из БД в `storage/app/tasks/**.json`.
 *
 * Уходя из JSON, мы теряем git-diff контента: сейчас правку задания видно
 * в pull request. Эта команда возвращает потерю — база остаётся источником
 * истины для рантайма, а файлы в репозитории становятся ревьюируемым слепком.
 *
 * Формат совпадает с тем, которым пишет `TaskDataService::saveTopicData()`
 * (`JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`) — иначе первый же прогон дал
 * бы diff на весь банк из-за отступов и экранирования.
 *
 *   php artisan tasks:dump-json                # все банки
 *   php artisan tasks:dump-json --bank=oge
 *   php artisan tasks:dump-json --dry-run      # показать, что изменилось бы
 */
class DumpTasksToJson extends Command
{
    protected $signature = 'tasks:dump-json
        {--bank= : oge|ege|vpr|alg, по умолчанию все}
        {--dry-run : не писать файлы, только показать расхождения}';

    protected $description = 'Выгрузить банки заданий из БД обратно в JSON';

    private const DIRS = ['oge' => '', 'ege' => 'ege', 'vpr' => 'vpr', 'alg' => 'alg'];

    public function handle(TaskBankRepository $repository): int
    {
        $only = $this->option('bank');
        $dry = (bool) $this->option('dry-run');

        $topics = TaskGroup::query()
            ->when($only !== null, fn ($q) => $q->where('bank', $only))
            ->select('bank', 'grade', 'topic')
            ->distinct()
            ->orderBy('bank')->orderBy('grade')->orderBy('topic')
            ->get();

        if ($topics->isEmpty()) {
            $this->warn('в базе нет заданий — сначала tasks:import-json');
            return self::FAILURE;
        }

        $written = $same = 0;
        foreach ($topics as $row) {
            $path = $this->path($row->bank, $row->grade, $row->topic);
            if ($path === null) {
                $this->warn("  неизвестный банк «{$row->bank}», пропускаю");
                continue;
            }

            $data = $repository->topicData($row->bank, $row->topic, $row->grade);
            $existing = File::exists($path) ? File::get($path) : null;
            if ($existing !== null) {
                $reference = json_decode($existing, true);
                if (is_array($reference)) {
                    $data = $this->alignKeyOrder($data, $reference);
                }
            }

            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
            // Часть банков набиралась без экранирования слэшей — повторяем
            // стиль файла, иначе каждый URL и каждый тег SVG попадёт в diff.
            if ($existing !== null && !str_contains($existing, '\\/')) {
                $flags |= JSON_UNESCAPED_SLASHES;
            }
            $json = json_encode($data, $flags);
            $json = $this->matchTrailingNewline($this->matchIndent($json, $existing), $existing);

            $unchanged = File::exists($path) && File::get($path) === $json;
            if ($unchanged) {
                $same++;
                continue;
            }

            $written++;
            $label = sprintf('%s%s тема %s', $row->bank, $row->grade ? " кл.{$row->grade}" : '', $row->topic);
            if ($dry) {
                $this->line("  изменился бы: {$label}");
                continue;
            }

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $json);
            $this->line("  записан: {$label}");
        }

        $this->newLine();
        $this->info($dry
            ? "ПОДСЧЁТ: совпадает {$same}, отличается {$written}"
            : "Выгружено: записано {$written}, без изменений {$same}");

        return self::SUCCESS;
    }

    /**
     * Подогнать отступ под существующий файл.
     *
     * `JSON_PRETTY_PRINT` всегда пишет по четыре пробела, а банки набирались
     * разными инструментами: 51 файл с отступом 4, 60 — с отступом 2. Без
     * подгонки дамп переформатировал бы 60 файлов целиком, и diff утонул бы
     * в пробелах.
     */
    private function matchIndent(string $json, ?string $existing): string
    {
        if ($existing === null) {
            return $json;
        }

        $newline = strpos($existing, "\n");
        if ($newline === false) {
            return $json;
        }
        $rest = substr($existing, $newline + 1);
        $indent = strlen($rest) - strlen(ltrim($rest, ' '));
        if ($indent !== 2) {
            return $json;
        }

        // Уровень вложенности всегда кратен четырём — делим отступ пополам.
        return preg_replace_callback(
            '/^( +)/m',
            static fn (array $m) => str_repeat(' ', (int) (strlen($m[1]) / 2)),
            $json
        );
    }

    /** Завершающий перевод строки — как в существующем файле. */
    private function matchTrailingNewline(string $json, ?string $existing): string
    {
        return $existing !== null && str_ends_with($existing, "\n") ? $json . "\n" : $json;
    }

    /**
     * Выстроить ключи объектов в том же порядке, что и в существующем файле.
     *
     * MySQL хранит JSON с собственным порядком ключей («icon» может встать
     * перед «title»), и без этого шага первый же дамп переписал бы весь банк
     * целиком — 73 файла с нулевым смыслом в diff. Ради этого diff'а мы файлы
     * и держим, так что шум недопустим.
     *
     * Ключи, которых в файле не было, дописываются в конец; порядок списков
     * не трогается — он значащий.
     */
    private function alignKeyOrder(mixed $data, mixed $reference): mixed
    {
        if (!is_array($data) || !is_array($reference)) {
            return $data;
        }

        if (array_is_list($data)) {
            foreach ($data as $i => $item) {
                if (array_key_exists($i, $reference)) {
                    $data[$i] = $this->alignKeyOrder($item, $reference[$i]);
                }
            }
            return $data;
        }

        $ordered = [];
        foreach ($reference as $key => $value) {
            if (array_key_exists($key, $data)) {
                $ordered[$key] = $this->alignKeyOrder($data[$key], $value);
            }
        }
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $ordered)) {
                $ordered[$key] = $value;
            }
        }

        return $ordered;
    }

    private function path(string $bank, ?int $grade, string $topic): ?string
    {
        if (!array_key_exists($bank, self::DIRS)) {
            return null;
        }

        $dir = storage_path('app/tasks' . (self::DIRS[$bank] ? '/' . self::DIRS[$bank] : ''));
        if ($grade !== null) {
            $dir .= "/grade_{$grade}";
        }

        return "{$dir}/topic_{$topic}.json";
    }
}
