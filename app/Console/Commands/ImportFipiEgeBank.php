<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\EgeTaskDataService;
use App\Services\TaskBankRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Импорт профиля ЕГЭ из открытого банка ФИПИ (`ege_prof_katex.json`).
 *
 * Выгрузку собирает `fipi-sync/export_ege_katex.py`: условие — готовая
 * разметка с формулами в KaTeX, ответы размечены происхождением, у каждого
 * задания стабильный `guid`.
 *
 * Отличий от ОГЭ ({@see ImportFipiBank}) три, и все из-за самого материала.
 *
 * Тема здесь — НОМЕР ЗАДАНИЯ ЕГЭ (01…19), а не раздел математики: именно так
 * устроен и нынешний ЕГЭ-банк Паломатики, и сам экзамен. Кураторской учебной
 * карты, как у ОГЭ, для ЕГЭ нет, поэтому задания внутри номера группируются
 * по подтипам самого банка.
 *
 * Чертежи остаются картинками — своих SVG для ЕГЭ не рисовали. Файлы
 * приезжают архивом и раскладываются в `public/ege-bank/img`, а ссылки в
 * разметке переписываются на этот публичный путь: относительный `img/…` в
 * PWA разрешался бы от адреса страницы и давал 404.
 *
 * Задачи, где ответ не число («Да» на вопрос «может ли»), помечены в
 * выгрузке `answer_kind` и уходят в `draft`: автопроверка их принять не
 * может, а показать ученику без проверки — значит соврать.
 *
 *   php artisan tasks:import-fipi-ege --url=https://palomig.ru/ege-bank/export/ege_prof_katex.json
 */
class ImportFipiEgeBank extends Command
{
    protected $signature = 'tasks:import-fipi-ege
        {--file= : путь к ege_prof_katex.json, по умолчанию storage/app/imports/ege_prof_katex.json}
        {--url= : скачать выгрузку по адресу вместо чтения файла}
        {--images= : архив рисунков (tar.gz) — путь или URL}
        {--and-retire : сразу отключить прежний ЕГЭ-банк, в одной транзакции}
        {--dry-run : только посчитать, в базу не писать}';

    protected $description = 'Импортировать профиль ЕГЭ из банка ФИПИ';

    private const BANK = 'ege';
    private const PUBLIC_IMAGES = 'ege-bank/img';

    /** Ответ отсутствует. Именно так, а не `empty()`: ответ «0» — настоящий. */
    private static function missingAnswer(array $task): bool
    {
        return !isset($task['answer']) || $task['answer'] === '' || $task['answer'] === null;
    }

    /** Ответ, который нельзя проверить автоматически («Да»/«Нет»). */
    private static function unverifiable(array $task): bool
    {
        return ($task['answer_kind'] ?? null) === 'yes_no';
    }

    public function handle(): int
    {
        $bank = $this->loadBank();
        if ($bank === null) {
            return self::FAILURE;
        }

        $skipped = [];
        $topics = [];
        foreach ($bank['tasks'] as $task) {
            $number = (int) ($task['task_no'] ?? 0);
            // Номер задания не определён — в ученическом разделе такой теме
            // неоткуда взяться, поэтому задачи не импортируются, но и молча
            // не пропадают: их перечисляет итог.
            if ($number < 1 || $number > 19) {
                $skipped[] = $task['guid'];
                continue;
            }
            $topics[str_pad((string) $number, 2, '0', STR_PAD_LEFT)][] = $task;
        }
        ksort($topics);

        $counts = ['topics' => 0, 'groups' => 0, 'tasks' => 0, 'draft' => 0];
        $plan = [];
        foreach ($topics as $topic => $items) {
            $groups = $this->groupBySubtype($items);
            $plan[$topic] = $groups;
            $counts['topics']++;
            $counts['groups'] += count($groups);
            foreach ($groups as $group) {
                $counts['tasks'] += count($group['items']);
                $counts['draft'] += count(array_filter(
                    $group['items'],
                    static fn (array $t) => self::missingAnswer($t) || self::unverifiable($t),
                ));
            }
        }

        $this->line(sprintf(
            'Разобрано: тем %d, заданий %d, задач %d (из них в черновиках %d)',
            $counts['topics'], $counts['groups'], $counts['tasks'], $counts['draft'],
        ));
        if ($skipped) {
            $this->warn(sprintf('без номера задания и потому не импортированы: %d', count($skipped)));
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $images = $this->installImages();
        if ($images === null) {
            return self::FAILURE;
        }
        $this->line("рисунков разложено: {$images}");

        DB::transaction(function () use ($plan, $bank) {
            // Переимпорт заменяет ранее залитый ФИПИ целиком и не трогает
            // задания Паломатики: они различаются по `source`.
            TaskGroup::query()->where('bank', self::BANK)->where('source', 'fipi')->delete();

            foreach ($plan as $topic => $groups) {
                $this->upsertTopic($topic, $bank, $groups[0]['items'] ?? []);
                $position = 0;
                foreach ($groups as $group) {
                    $this->createGroup($topic, $group, $position++);
                }
            }

            // В одной транзакции с импортом: иначе между заливкой ФИПИ и
            // отключением старого банка тема показывала бы оба сразу.
            if ($this->option('and-retire')) {
                Task::query()
                    ->whereIn('task_group_id', TaskGroup::query()
                        ->where('bank', self::BANK)->where('source', 'palomatika')->select('id'))
                    ->where('source', 'palomatika')
                    ->update(['source' => TaskBankRepository::RETIRED]);
                TaskGroup::query()->where('bank', self::BANK)->where('source', 'palomatika')
                    ->update(['source' => TaskBankRepository::RETIRED]);
            }
        });

        $this->info(sprintf(
            'Импортировано: тем %d, заданий %d, задач %d (в черновиках %d)',
            $counts['topics'], $counts['groups'], $counts['tasks'], $counts['draft'],
        ));

        return self::SUCCESS;
    }

    /** @return array<string, mixed>|null */
    private function loadBank(): ?array
    {
        // На проде файла нет: storage/app целиком в .gitignore, и деплой его
        // не привозит. Выгрузка опубликована на нашем же VPS.
        if ($url = $this->option('url')) {
            $this->line("  скачиваю выгрузку: {$url}");
            $body = @file_get_contents($url);
            if ($body === false) {
                $this->error("не удалось скачать выгрузку: {$url}");
                return null;
            }
            $path = storage_path('app/imports/ege_prof_katex.json');
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $body);
        } else {
            $path = $this->option('file') ?: storage_path('app/imports/ege_prof_katex.json');
        }

        if (!File::exists($path)) {
            $this->error("не найден файл банка: {$path}");
            return null;
        }

        $bank = json_decode(File::get($path), true);
        if (!is_array($bank) || !isset($bank['tasks'])) {
            $this->error('файл не похож на выгрузку банка ФИПИ');
            return null;
        }
        if (($bank['level'] ?? null) !== 'prof') {
            $this->error('ожидается выгрузка профиля ЕГЭ (level=prof)');
            return null;
        }

        return $bank;
    }

    /**
     * Задания внутри номера — по подтипам банка.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function groupBySubtype(array $items): array
    {
        $subtypes = [];
        foreach ($items as $task) {
            $subtypes[(string) ($task['subtype_id'] ?? '1')][] = $task;
        }
        ksort($subtypes, SORT_NATURAL);

        $groups = [];
        $number = 1;
        foreach ($subtypes as $tasks) {
            $groups[] = [
                'number' => $number++,
                'title' => $tasks[0]['subtype_title'] ?? null,
                'part2' => (bool) ($tasks[0]['part2'] ?? false),
                'items' => $tasks,
            ];
        }

        return $groups;
    }

    /** Рисунки из архива — в public. Возвращает их число или null при ошибке. */
    private function installImages(): ?int
    {
        $source = $this->option('images');
        if (!$source) {
            return 0;
        }

        $archive = $source;
        if (preg_match('~^https?://~', $source)) {
            $this->line("  скачиваю рисунки: {$source}");
            $body = @file_get_contents($source);
            if ($body === false) {
                $this->error("не удалось скачать архив рисунков: {$source}");
                return null;
            }
            $archive = storage_path('app/imports/ege_prof_images.tar.gz');
            File::ensureDirectoryExists(dirname($archive));
            File::put($archive, $body);
        }
        if (!File::exists($archive)) {
            $this->error("не найден архив рисунков: {$archive}");
            return null;
        }

        $target = public_path(self::PUBLIC_IMAGES);
        File::ensureDirectoryExists($target);
        $command = sprintf('tar -xzf %s --strip-components=1 -C %s',
            escapeshellarg($archive), escapeshellarg($target));
        exec($command, $output, $code);
        if ($code !== 0) {
            $this->error("архив рисунков не распаковался: {$archive}");
            return null;
        }

        return count(File::allFiles($target));
    }

    /**
     * @param array<string, mixed> $bank
     * @param array<int, array<string, mixed>> $items
     */
    private function upsertTopic(string $topic, array $bank, array $items = []): void
    {
        $meta = (new EgeTaskDataService())->getAllTopicsMeta()[$topic] ?? null;

        // Название темы берётся из банка, а не из прежней карты: нумерация
        // заданий ЕГЭ с тех пор сместилась, и тема 13 называлась
        // «Неравенства», хотя у ФИПИ 13 — уравнение, а неравенство это 15.
        // Цвет и иконку оставляем прежние: они к номеру не привязаны.
        $title = $items[0]['task_title'] ?? null;
        if ($title) {
            $meta = array_merge($meta ?? [], ['title' => $title]);
        }

        TaskTopic::query()->where('bank', self::BANK)->whereNull('grade')
            ->where('topic', $topic)->delete();
        TaskTopic::create([
            'bank' => self::BANK,
            'grade' => null,
            'topic' => $topic,
            'payload' => [
                'topic_id' => $topic,
                'exam_type' => 'ege',
                'source' => 'fipi',
                'math' => 'katex-0.16.9',
                'imported_at' => now()->toIso8601String(),
                'meta' => $meta ?? ['title' => "Задание {$topic}"],
            ],
        ]);
    }

    /** @param array<string, mixed> $group */
    private function createGroup(string $topic, array $group, int $position): void
    {
        $payload = [
            'part2' => $group['part2'],
            'instruction' => $group['title'],
            'type' => 'fipi',
            'status' => 'production',
        ];

        $model = TaskGroup::create([
            'bank' => self::BANK,
            'grade' => null,
            'topic' => $topic,
            'block_number' => 1,
            'block_title' => 'ФИПИ',
            'zadanie_number' => $group['number'],
            'position' => $position,
            'instruction' => $group['title'],
            'type' => 'fipi',
            // Всё, что нужно интерфейсу, обязано лежать в payload: структуру
            // репозиторий собирает именно из него (см. ImportFipiBank).
            'payload' => $payload,
            'status' => 'production',
            'source' => 'fipi',
        ]);

        foreach (array_values($group['items']) as $index => $task) {
            $draft = self::missingAnswer($task) || self::unverifiable($task);
            $status = $draft ? 'draft' : 'production';

            Task::create([
                'task_group_id' => $model->id,
                'position' => $index,
                'type' => 'fipi',
                'payload' => array_filter([
                    'id' => $index + 1,
                    'html' => $this->publicHtml($task['html'] ?? ''),
                    'options' => array_map(
                        static fn (array $o) => ['n' => $o['n'], 'html' => $o['html']],
                        $task['options'] ?? []
                    ) ?: null,
                    'images' => $task['images'] ?? null,
                    'answer' => $task['answer'] ?? null,
                    // Полный ответ задачи с пунктами а/б/в: в поле ученика
                    // идёт только числовой пункт, но потерять остальное
                    // нельзя — по нему проверяют решение.
                    'answer_parts' => $task['answer_parts'] ?? null,
                    'answer_kind' => $task['answer_kind'] ?? null,
                    'status' => $status,
                ], static fn ($v) => $v !== null && $v !== []),
                'answer' => $task['answer'] ?? null,
                'answer_src' => $task['answer_src'] ?? null,
                'status' => $status,
                'source' => 'fipi',
                'fipi_guid' => $task['guid'],
            ]);
        }
    }

    /**
     * Ссылки на рисунки — от корня сайта.
     *
     * В выгрузке путь относительный (`img/<guid>/<файл>`), и в PWA он
     * разрешался бы от адреса страницы вроде `/pwa/student/tasks/03`, давая
     * 404 на каждом чертеже.
     */
    private function publicHtml(string $html): string
    {
        return preg_replace('~(<img[^>]*\bsrc=")img/~i', '$1/' . self::PUBLIC_IMAGES . '/', $html);
    }
}
