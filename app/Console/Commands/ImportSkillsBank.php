<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TaskTopic;
use App\Services\SkillsTaskDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Банк «Скиллы» из файлов репозитория в базу.
 *
 * Источник — `database/data/skills/*.json`, по файлу на тему (навык):
 *
 *   { "bank": "skills", "topic": "01", "meta": {"title": …},
 *     "zadaniya": [ {"number": 1, "title": …, "instruction": …, "type": "expression",
 *                    "tasks": [ {"id": 1, "expression": "$…$", "answer": "53,4"} ] } ] }
 *
 * Файлы лежат в репозитории, а не в storage: их порождают генераторы из
 * `scripts/`, и деплой доставляет их на прод как обычный код, после чего
 * команда запускается вебхуком. Идемпотентна: тема пересобирается целиком.
 *
 *   php artisan tasks:import-skills
 *   php artisan tasks:import-skills --dry-run
 */
class ImportSkillsBank extends Command
{
    protected $signature = 'tasks:import-skills
        {--dry-run : только посчитать, в базу не писать}';

    protected $description = 'Загрузить банк «Скиллы» из database/data/skills в БД';

    public function handle(): int
    {
        $dir = database_path('data/skills');
        $files = File::isDirectory($dir) ? File::glob("{$dir}/*.json") : [];
        if ($files === []) {
            $this->error("В {$dir} нет ни одного JSON.");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        sort($files);

        foreach ($files as $path) {
            $data = json_decode(File::get($path), true);
            if (!is_array($data) || ($data['bank'] ?? null) !== SkillsTaskDataService::BANK) {
                $this->error(basename($path) . ': не файл банка «skills», пропускаю.');
                continue;
            }

            $topic = (string) ($data['topic'] ?? '');
            $zadaniya = $data['zadaniya'] ?? [];
            if ($topic === '' || !is_array($zadaniya) || $zadaniya === []) {
                $this->error(basename($path) . ': нет topic или zadaniya, пропускаю.');
                continue;
            }

            $tasks = array_sum(array_map(static fn ($z) => count($z['tasks'] ?? []), $zadaniya));
            $title = (string) ($data['meta']['title'] ?? $topic);
            $this->line(sprintf('%s → тема %s «%s»: %d заданий, %d задач%s',
                basename($path), $topic, $title, count($zadaniya), $tasks, $dryRun ? ' (dry-run)' : ''));

            if ($dryRun) {
                continue;
            }

            $this->importTopic($topic, $data, $zadaniya);
        }

        if (!$dryRun) {
            SkillsTaskDataService::forgetCache();
        }

        return self::SUCCESS;
    }

    private function importTopic(string $topic, array $data, array $zadaniya): void
    {
        $bank = SkillsTaskDataService::BANK;
        // Всё, что не структура заданий, остаётся метаданными темы (meta,
        // generator) — как у прочих банков.
        $topicPayload = array_diff_key($data, array_flip(['bank', 'topic', 'zadaniya']));
        $topicPayload['topic_id'] = $topic;

        DB::transaction(function () use ($bank, $topic, $topicPayload, $zadaniya) {
            TaskTopic::query()->where('bank', $bank)->whereNull('grade')->where('topic', $topic)->delete();
            TaskTopic::create(['bank' => $bank, 'grade' => null, 'topic' => $topic, 'payload' => $topicPayload]);

            TaskGroup::query()->where('bank', $bank)->whereNull('grade')->where('topic', $topic)->delete();

            foreach (array_values($zadaniya) as $position => $zadanie) {
                // Интерфейс собирает задание из payload, поэтому статус и тип
                // обязаны лежать и там, а не только в колонках.
                $payload = array_diff_key($zadanie, array_flip(['number', 'tasks']));
                $payload['type'] = $payload['type'] ?? 'expression';
                $payload['status'] = $payload['status'] ?? 'production';

                $group = TaskGroup::create([
                    'bank' => $bank,
                    'grade' => null,
                    'topic' => $topic,
                    'block_number' => 1,
                    'block_title' => 'Скиллы',
                    'zadanie_number' => (int) ($zadanie['number'] ?? $position + 1),
                    'position' => $position,
                    'instruction' => $zadanie['instruction'] ?? null,
                    'type' => $payload['type'],
                    'payload' => $payload,
                    'status' => $payload['status'],
                    'source' => 'palomatika',
                ]);

                foreach (array_values($zadanie['tasks'] ?? []) as $index => $task) {
                    $task['id'] = $task['id'] ?? $index + 1;
                    $task['status'] = $task['status'] ?? 'production';
                    Task::create([
                        'task_group_id' => $group->id,
                        'position' => $index,
                        'type' => $task['type'] ?? null,
                        'payload' => $task,
                        'answer' => isset($task['answer']) ? (string) $task['answer'] : null,
                        'answer_src' => 'calc',
                        'status' => $task['status'],
                        'source' => 'palomatika',
                    ]);
                }
            }
        });
    }
}
