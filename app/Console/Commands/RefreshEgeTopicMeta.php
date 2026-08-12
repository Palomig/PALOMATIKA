<?php

namespace App\Console\Commands;

use App\Models\TaskTopic;
use App\Services\EgeTaskDataService;
use App\Services\TaskBankRepository;
use Illuminate\Console\Command;

/**
 * Пересобрать подписи тем ЕГЭ, не трогая сами задачи.
 *
 * Импорт ({@see ImportFipiEgeBank}) складывает в `task_topics` смесь: название
 * приходит из банка ФИПИ, а описание, цвет и иконка — из карты в
 * {@see EgeTaskDataService}. Карта отставала от нумерации ФИПИ, и в базу
 * попали пары вида «Текстовая задача» с описанием «Чтение графиков и
 * диаграмм». Переимпортировать банк ради подписи незачем — она правится
 * отдельно.
 *
 * Название из базы сохраняется: оно пришло из банка и точнее карты. Команда
 * идемпотентна.
 *
 *   php artisan tasks:refresh-ege-meta
 */
class RefreshEgeTopicMeta extends Command
{
    protected $signature = 'tasks:refresh-ege-meta {--dry-run : только показать, в базу не писать}';

    protected $description = 'Обновить описания тем ЕГЭ из карты сервиса, сохранив названия банка';

    private const BANK = 'ege';

    public function handle(): int
    {
        $map = (new EgeTaskDataService())->getAllTopicsMeta();
        $dry = (bool) $this->option('dry-run');
        $changed = 0;

        $topics = TaskTopic::query()
            ->where('bank', self::BANK)
            ->whereNull('grade')
            ->orderBy('topic')
            ->get();

        if ($topics->isEmpty()) {
            $this->warn('Тем ЕГЭ в базе нет — обновлять нечего.');
            return self::SUCCESS;
        }

        foreach ($topics as $topic) {
            $payload = $topic->payload ?? [];
            $current = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
            $fallback = $map[$topic->topic] ?? [];

            $meta = array_merge($fallback, $current);
            $meta['description'] = $fallback['description'] ?? ($current['description'] ?? '');

            if ($meta === $current) {
                continue;
            }

            $this->line(sprintf(
                '%s %s: «%s» → «%s»',
                $topic->topic,
                $meta['title'] ?? '',
                $current['description'] ?? '',
                $meta['description']
            ));
            $changed++;

            if ($dry) {
                continue;
            }

            $payload['meta'] = $meta;
            $topic->forceFill(['payload' => $payload])->save();
        }

        $this->info($dry
            ? "Изменилось бы тем: {$changed} (--dry-run, база не тронута)"
            : "Обновлено тем: {$changed}");

        if (!$dry && $changed > 0) {
            (new EgeTaskDataService())->clearCache();
            TaskBankRepository::forgetTableCheck();
        }

        return self::SUCCESS;
    }
}
