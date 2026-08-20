<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskIntro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Импорт вводных текстов банка ФИПИ и привязка к ним заданий 1–5.
 *
 * Отдельная команда, а не часть tasks:import-fipi: тот импорт уже работает на
 * проде и переносит 3884 задачи в одной транзакции, ломать его ради добавки
 * незачем. Команда идемпотентна — повторный запуск переписывает тексты и
 * ссылки, не плодя дублей.
 */
class ImportFipiIntros extends Command
{
    protected $signature = 'tasks:import-intros
        {--file= : путь к bank_katex.json}
        {--url= : скачать выгрузку по адресу вместо чтения файла}
        {--bank=oge : банк, в который импортировать}
        {--dry-run : только посчитать, в базу не писать}';

    protected $description = 'Импортировать вводные тексты блока 1–5 из выгрузки ФИПИ';

    public function handle(): int
    {
        $raw = $this->readExport();
        if ($raw === null) {
            return self::FAILURE;
        }

        $intros = $raw['intros'] ?? [];
        $tasks = $raw['tasks'] ?? [];

        if (!is_array($intros) || $intros === []) {
            $this->error('В выгрузке нет раздела intros — нечего импортировать.');

            return self::FAILURE;
        }

        $bank = (string) $this->option('bank');

        // GUID задания → GUID вводного текста.
        $links = [];
        foreach ($tasks as $task) {
            if (!empty($task['intro']) && !empty($task['guid'])) {
                $links[$task['guid']] = $task['intro'];
            }
        }

        $this->line('вводных текстов: ' . count($intros) . ', заданий с привязкой: ' . count($links));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($intros, $links, $bank): void {
            foreach ($intros as $guid => $intro) {
                $html = (string) ($intro['html'] ?? '');

                TaskIntro::updateOrCreate(
                    ['bank' => $bank, 'guid' => $guid],
                    ['html' => $html, 'images' => $this->imagesOf($html)]
                );
            }

            // Привязка идёт пачками: 359 отдельных UPDATE по одному заданию
            // держали бы транзакцию заметно дольше без всякой пользы.
            foreach (array_chunk($links, 200, true) as $chunk) {
                foreach ($chunk as $taskGuid => $introGuid) {
                    Task::where('fipi_guid', $taskGuid)->update(['intro_guid' => $introGuid]);
                }
            }
        });

        $linked = Task::whereNotNull('intro_guid')->count();
        $this->info("импортировано вводных текстов: " . count($intros) . ", привязано заданий: {$linked}");

        return self::SUCCESS;
    }

    /** @return array<int, string> Пути иллюстраций, упомянутых во вводном тексте. */
    private function imagesOf(string $html): array
    {
        preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $m);

        return array_values(array_unique($m[1] ?? []));
    }

    /** @return array<string, mixed>|null */
    private function readExport(): ?array
    {
        $url = (string) $this->option('url');
        $file = (string) ($this->option('file') ?: storage_path('app/imports/bank_katex.json'));

        if ($url !== '') {
            $this->line('скачиваю выгрузку: ' . $url);
            $json = @file_get_contents($url);
            if ($json === false) {
                $this->error('не удалось скачать выгрузку');

                return null;
            }
        } else {
            if (!is_file($file)) {
                $this->error("файл не найден: {$file}");

                return null;
            }
            $json = (string) file_get_contents($file);
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->error('выгрузка не разобралась как JSON');

            return null;
        }

        return $data;
    }
}
