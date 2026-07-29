<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Перенос банков заданий из JSON в БД.
 *
 * Идемпотентна: содержимое темы полностью пересобирается, поэтому повторный
 * прогон приводит базу к состоянию файлов, а не плодит дубли.
 *
 *   php artisan tasks:import-json                 # все банки
 *   php artisan tasks:import-json --bank=oge      # только ОГЭ
 *   php artisan tasks:import-json --dry-run       # посчитать, ничего не писать
 */
class ImportTasksFromJson extends Command
{
    protected $signature = 'tasks:import-json
        {--bank= : oge|ege|vpr|alg, по умолчанию все}
        {--dry-run : только посчитать, в базу не писать}';

    protected $description = 'Перенести банки заданий из storage/app/tasks в БД';

    /**
     * Банки, у которых структура «blocks → zadaniya → tasks».
     *
     * `entrance10` сюда не входит: у вступительной работы своя форма
     * (`bank.json` с ключами-номерами заданий и `variants.json`), она живёт
     * отдельным банком и переносится отдельной задачей.
     */
    private const BANKS = [
        'oge' => ['dir' => '', 'graded' => false],
        'ege' => ['dir' => 'ege', 'graded' => false],
        'vpr' => ['dir' => 'vpr', 'graded' => true],
        'alg' => ['dir' => 'alg', 'graded' => true],
    ];

    public function handle(): int
    {
        $only = $this->option('bank');
        if ($only !== null && !isset(self::BANKS[$only])) {
            $this->error("Неизвестный банк «{$only}». Доступные: " . implode(', ', array_keys(self::BANKS)));
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $totals = ['groups' => 0, 'tasks' => 0, 'files' => 0];

        foreach (self::BANKS as $bank => $spec) {
            if ($only !== null && $only !== $bank) {
                continue;
            }
            foreach ($this->files($spec) as [$grade, $path]) {
                $result = $this->importFile($bank, $grade, $path, $dry);
                $totals['files']++;
                $totals['groups'] += $result['groups'];
                $totals['tasks'] += $result['tasks'];
                $this->line(sprintf(
                    '  %-4s %s%-14s заданий %3d, задач %4d',
                    $bank,
                    $grade ? "кл.{$grade} " : '',
                    basename($path),
                    $result['groups'],
                    $result['tasks'],
                ));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s файлов %d, заданий %d, задач %d',
            $dry ? 'ПОДСЧЁТ (в базу не писал):' : 'Импортировано:',
            $totals['files'], $totals['groups'], $totals['tasks'],
        ));

        return self::SUCCESS;
    }

    /** @return array<int, array{0: ?int, 1: string}> */
    private function files(array $spec): array
    {
        $base = storage_path('app/tasks' . ($spec['dir'] ? '/' . $spec['dir'] : ''));
        if (!File::isDirectory($base)) {
            return [];
        }

        $found = [];
        if ($spec['graded']) {
            foreach (glob("{$base}/grade_*", GLOB_ONLYDIR) as $gradeDir) {
                $grade = (int) str_replace('grade_', '', basename($gradeDir));
                foreach ($this->topicFiles($gradeDir) as $path) {
                    $found[] = [$grade, $path];
                }
            }
        } else {
            foreach ($this->topicFiles($base) as $path) {
                $found[] = [null, $path];
            }
        }

        sort($found);
        return $found;
    }

    /**
     * Файлы тем каталога. `topic_NN_geometry.json` отсеиваются: это ИСТОЧНИК
     * для `svg:bake`, а не банк заданий — блоков с задачами внутри нет.
     *
     * @return array<int, string>
     */
    private function topicFiles(string $dir): array
    {
        return array_values(array_filter(
            glob("{$dir}/topic_*.json") ?: [],
            static fn (string $path) => (bool) preg_match('/^topic_\\d+\\.json$/', basename($path)),
        ));
    }

    /** @return array{groups: int, tasks: int} */
    private function importFile(string $bank, ?int $grade, string $path, bool $dry): array
    {
        $data = json_decode(File::get($path), true);
        if (!is_array($data)) {
            $this->warn("  пропущен нечитаемый файл: {$path}");
            return ['groups' => 0, 'tasks' => 0];
        }

        // Тема берётся из имени файла, а не из поля внутри: имя — то, по чему
        // её адресуют сервисы, и ведущий ноль в нём значащий.
        $topic = str_replace(['topic_', '.json'], '', basename($path));
        $groups = $tasks = 0;
        $position = 0;

        $rows = [];
        foreach ($data['blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $rows[] = [$block, $zadanie, $position++];
                $groups++;
                $tasks += count($zadanie['tasks'] ?? []);
            }
        }

        if ($dry) {
            return ['groups' => $groups, 'tasks' => $tasks];
        }

        DB::transaction(function () use ($bank, $grade, $topic, $rows) {
            // Тема пересобирается целиком — иначе повторный прогон оставит
            // задачи, которых в файле уже нет.
            TaskGroup::query()
                ->where('bank', $bank)
                ->where('grade', $grade)
                ->where('topic', $topic)
                ->delete();

            foreach ($rows as [$block, $zadanie, $position]) {
                $group = TaskGroup::create([
                    'bank' => $bank,
                    'grade' => $grade,
                    'topic' => $topic,
                    'block_number' => (int) ($block['number'] ?? 1),
                    'block_title' => $block['title'] ?? null,
                    'zadanie_number' => (int) ($zadanie['number'] ?? 1),
                    'position' => $position,
                    'instruction' => $zadanie['instruction'] ?? null,
                    'type' => $zadanie['type'] ?? 'expression',
                    'svg_type' => $zadanie['svg_type'] ?? null,
                    'payload' => $this->rest($zadanie, [
                        'number', 'instruction', 'type', 'svg_type', 'tasks', 'status',
                    ]),
                    'status' => $zadanie['status'] ?? 'draft',
                    'source' => 'palomatika',
                ]);

                foreach (array_values($zadanie['tasks'] ?? []) as $index => $task) {
                    Task::create([
                        'task_group_id' => $group->id,
                        'position' => $index,
                        'type' => $task['type'] ?? null,
                        'payload' => $this->rest($task, ['type', 'answer', 'status']),
                        'answer' => $this->answer($task['answer'] ?? null),
                        'answer_src' => null,
                        'status' => $task['status'] ?? 'draft',
                        'source' => 'palomatika',
                        'legacy_task_key' => $this->legacyKey($bank, $topic, $block, $zadanie, $task),
                    ]);
                }
            }
        });

        return ['groups' => $groups, 'tasks' => $tasks];
    }

    /**
     * Ответ в текст. У десяти задач ВПР 5 класса он составной —
     * `["в среду", "6"]`. Такой ответ сериализуется в JSON, а не приводится
     * к строке: приведение молча превратило бы его в «Array».
     */
    private function answer(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    /** Всё, что не разложено по колонкам, — в payload как есть. */
    private function rest(array $source, array $columns): ?array
    {
        $rest = array_diff_key($source, array_flip($columns));
        return $rest === [] ? null : $rest;
    }

    /**
     * Прежний адрес задачи. Формат существовал только для ОГЭ и банк в себе
     * не кодирует, поэтому для остальных остаётся NULL: иначе ключи ЕГЭ и ВПР
     * столкнулись бы между собой на уникальном индексе.
     */
    private function legacyKey(string $bank, string $topic, array $block, array $zadanie, array $task): ?string
    {
        if ($bank !== 'oge' || !isset($task['id'])) {
            return null;
        }

        return sprintf(
            'topic_%s_block_%d_zadanie_%d_task_%d',
            $topic,
            (int) ($block['number'] ?? 1),
            (int) ($zadanie['number'] ?? 1),
            (int) $task['id'],
        );
    }
}
