<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\FipiTaskTaxonomy;
use App\Services\TaskBankRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Импорт банка открытого банка ФИПИ (`bank_katex.json`) в ОГЭ.
 *
 * Формат источника собирает `fipi-sync/export_katex_bank.py`: условие —
 * готовая разметка с формулами в KaTeX (`$…$`) и инлайновыми SVG, ответы
 * размечены происхождением, у каждого задания стабильный `guid`.
 *
 * Тип заданий — `fipi`: существующие шаблоны экранируют текст
 * (`word_problem` рендерит `e($task['text'])`), поэтому разметку условия они
 * показали бы тегами наружу. Отдельный шаблон рендерит её как есть.
 *
 * Варианты ответа кладутся БЕЗ буквенных `id`. В PWA `optionAnswerValue()`
 * отдаёт `opt.id`, а при его отсутствии — порядковый номер; ответы банка
 * ФИПИ как раз номера, и с буквенными id ученик отправлял бы «b» против
 * эталона «2».
 *
 *   php artisan tasks:import-fipi --file=storage/app/imports/bank_katex.json
 */
class ImportFipiBank extends Command
{
    protected $signature = 'tasks:import-fipi
        {--file= : путь к bank_katex.json, по умолчанию storage/app/imports/bank_katex.json}
        {--url= : скачать выгрузку по адресу вместо чтения файла}
        {--and-retire : сразу отключить прежний банк ОГЭ, в одной транзакции}
        {--dry-run : только посчитать, в базу не писать}';

    protected $description = 'Импортировать банк заданий ФИПИ в банк ОГЭ';

    /**
     * Ответ отсутствует. Именно так, а не `empty()`: десять уравнений темы 9
     * имеют корень «0», и `empty('0')` посчитал бы их нерешёнными — задачи
     * уехали бы в `draft` и пропали из выдачи.
     */
    private static function missingAnswer(array $task): bool
    {
        return !isset($task['answer']) || $task['answer'] === '' || $task['answer'] === null;
    }

    /** Задание с несколькими верными вариантами. */
    private static function isMultiSelect(array $task): bool
    {
        $answer = (string) ($task['answer'] ?? '');

        return !empty($task['options'])
            && preg_match('/^[1-9][0-9]+$/', $answer) === 1;
    }

    public function handle(): int
    {
        // На проде файла нет: storage/app целиком в .gitignore, и деплой его
        // не привозит. Выгрузка опубликована на нашем же VPS, поэтому проще
        // скачать её, чем городить отдельную заливку.
        if ($url = $this->option('url')) {
            $this->line("  скачиваю выгрузку: {$url}");
            $body = @file_get_contents($url);
            if ($body === false) {
                $this->error("не удалось скачать выгрузку: {$url}");
                return self::FAILURE;
            }
            $path = storage_path('app/imports/bank_katex.json');
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $body);
        } else {
            $path = $this->option('file') ?: storage_path('app/imports/bank_katex.json');
        }

        if (!File::exists($path)) {
            $this->error("не найден файл банка: {$path}");
            return self::FAILURE;
        }

        $bank = json_decode(File::get($path), true);
        if (!is_array($bank) || !isset($bank['tasks'])) {
            $this->error('файл не похож на выгрузку банка ФИПИ');
            return self::FAILURE;
        }

        // Задания идут в кураторском порядке: тема → задание → задача.
        $tasks = $bank['tasks'];
        usort($tasks, static fn (array $a, array $b) => [$a['topic'], ...$a['order']] <=> [$b['topic'], ...$b['order']]);

        $topics = [];
        foreach ($tasks as $task) {
            $topic = str_pad((string) $task['topic'], 2, '0', STR_PAD_LEFT);
            $topics[$topic][] = $task;
        }

        try {
            $groups = [];
            foreach ($topics as $topic => $items) {
                $taxonomy = FipiTaskTaxonomy::forTopic($topic);
                $groups[$topic] = $taxonomy
                    ? $taxonomy->group($items)
                    : $this->sourceGroups($items);
            }
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $counts = ['topics' => 0, 'groups' => 0, 'tasks' => 0, 'no_answer' => 0];
        foreach ($groups as $definitions) {
            $counts['topics']++;
            foreach ($definitions as $definition) {
                $items = $definition['items'];
                $counts['groups']++;
                $counts['tasks'] += count($items);
                $counts['no_answer'] += count(array_filter($items, static fn ($t) => self::missingAnswer($t)));
            }
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                'ПОДСЧЁТ: тем %d, заданий %d, задач %d (из них без ответа %d)',
                $counts['topics'], $counts['groups'], $counts['tasks'], $counts['no_answer'],
            ));
            return self::SUCCESS;
        }

        DB::transaction(function () use ($groups, $bank) {
            // Переимпорт заменяет ранее залитый ФИПИ целиком и не трогает
            // задания Паломатики: они различаются по `source`.
            TaskGroup::query()->where('bank', 'oge')->where('source', 'fipi')->delete();

            foreach ($groups as $topic => $definitions) {
                $this->upsertTopic($topic, $bank);
                $position = 0;
                foreach ($definitions as $definition) {
                    $this->createGroup($topic, $definition, $position++);
                }
            }

            // В одной транзакции с импортом: иначе между заливкой ФИПИ и
            // отключением старого банка тема показывала бы оба сразу.
            if ($this->option('and-retire')) {
                Task::query()
                    ->whereIn('task_group_id', TaskGroup::query()
                        ->where('bank', 'oge')->where('source', 'palomatika')->select('id'))
                    ->where('source', 'palomatika')
                    ->update(['source' => TaskBankRepository::RETIRED]);
                TaskGroup::query()->where('bank', 'oge')->where('source', 'palomatika')
                    ->update(['source' => TaskBankRepository::RETIRED]);
            }
        });

        $this->info(sprintf(
            'Импортировано: тем %d, заданий %d, задач %d (без ответа %d — доказательства темы 24)',
            $counts['topics'], $counts['groups'], $counts['tasks'], $counts['no_answer'],
        ));

        return self::SUCCESS;
    }

    private function upsertTopic(string $topic, array $bank): void
    {
        TaskTopic::query()->where('bank', 'oge')->where('grade', null)->where('topic', $topic)->delete();
        TaskTopic::create([
            'bank' => 'oge',
            'grade' => null,
            'topic' => $topic,
            'payload' => [
                'topic_id' => $topic,
                'source' => 'fipi',
                'math' => $bank['math'] ?? 'katex-0.16.9',
                'imported_at' => now()->toIso8601String(),
                'meta' => ['title' => $bank['topic_titles'][ltrim($topic, '0')] ?? "Тема {$topic}"],
            ],
        ]);
    }

    /**
     * Исходная группировка ФИПИ для тем без курируемой учебной карты.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sourceGroups(array $items): array
    {
        $subtypes = [];
        foreach ($items as $task) {
            $subtypes[$task['subtype_id'] ?? 1][] = $task;
        }

        $groups = [];
        foreach ($subtypes as $subtypeId => $tasks) {
            $groups[] = [
                'block_number' => 1,
                'block_title' => 'ФИПИ',
                'number' => (int) $subtypeId,
                'key' => null,
                'title' => $tasks[0]['subtype_title'] ?? null,
                'items' => $tasks,
            ];
        }

        return $groups;
    }

    /** @param array<string, mixed> $definition */
    private function createGroup(string $topic, array $definition, int $position): void
    {
        $items = $definition['items'];
        $payload = [
            'part2' => (bool) ($items[0]['part2'] ?? false),
            'instruction' => $definition['title'] ?? null,
            'type' => 'fipi',
            'status' => 'production',
        ];
        if (!empty($definition['key'])) {
            $payload['taxonomy_key'] = $definition['key'];
        }

        $group = TaskGroup::create([
            'bank' => 'oge',
            'grade' => null,
            'topic' => $topic,
            'block_number' => $definition['block_number'],
            'block_title' => $definition['block_title'],
            'zadanie_number' => $definition['number'],
            'position' => $position,
            'instruction' => $definition['title'] ?? null,
            'type' => 'fipi',
            // Всё, что нужно интерфейсу, обязано лежать в payload: структуру
            // репозиторий собирает именно из него, а колонки существуют ради
            // запросов и индексов. Без `instruction` падал сбор варианта,
            // без `type` задание уходило в шаблон по умолчанию, без `status`
            // фильтр «production» отсекал банк целиком.
            'payload' => $payload,
            'status' => 'production',
            'source' => 'fipi',
        ]);

        foreach (array_values($items) as $index => $task) {
            Task::create([
                'task_group_id' => $group->id,
                'position' => $index,
                'type' => 'fipi',
                'payload' => array_filter([
                    'id' => $index + 1,
                    'html' => $task['html'] ?? '',
                    // Варианты — только номер и разметка: буквенные id сломали
                    // бы сверку числового ответа (см. док-блок класса).
                    'options' => array_map(
                        static fn (array $o) => ['n' => $o['n'], 'html' => $o['html']],
                        $task['options'] ?? []
                    ) ?: null,
                    'images' => $task['images'] ?? null,
                    'svg_style' => $task['svg_style'] ?? null,
                    'answer' => $task['answer'] ?? null,
                    'status' => self::missingAnswer($task) ? 'draft' : 'production',
                    // Задание 19 бывает двух видов: «Какое из утверждений
                    // является истинным» — один ответ, «Какие … являются» —
                    // несколько. Признак берём по длине ответа: в банке это
                    // совпадает с формулировкой один в один (88 одиночных
                    // против 62 множественных).
                    'multi_select' => self::isMultiSelect($task) ?: null,
                ], static fn ($v) => $v !== null && $v !== []),
                'answer' => $task['answer'] ?? null,
                'answer_src' => $task['answer_src'] ?? null,
                'status' => self::missingAnswer($task) ? 'draft' : 'production',
                'source' => 'fipi',
                'fipi_guid' => $task['guid'],
            ]);
        }
    }
}
