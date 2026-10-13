<?php

namespace App\Console\Commands;

use App\Models\HomeworkTopicTask;
use App\Models\LessonSessionTask;
use App\Services\TaskBankResolver;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Пересобрать условие и подписи вариантов в уже розданных снимках задач ФИПИ.
 *
 * Урок и домашка хранят задачу СНИМКОМ, поэтому правка разбора разметки
 * ({@see TaskBankResolver::escapeBareLessThan()}) старые снимки не догоняет:
 * варианты «$a-5<0$» так и остаются обрезанными до «$a-5». Снимок хранит
 * исходную разметку банка в `raw` — из неё текст и собирается заново.
 * Идентификаторы вариантов не меняются, ответы учеников остаются верными.
 *
 *   php artisan tasks:refresh-fipi-text --dry-run
 *   php artisan tasks:refresh-fipi-text
 */
class RefreshFipiSnapshotText extends Command
{
    protected $signature = 'tasks:refresh-fipi-text
        {--dry-run : показать, что изменится, и не писать}';

    protected $description = 'Пересобрать текст и варианты снимков задач ФИПИ (уроки, домашки) из их разметки';

    public function handle(TaskBankResolver $resolver): int
    {
        $dry = (bool) $this->option('dry-run');

        $changed = $this->refresh(LessonSessionTask::query(), 'Урок', $resolver, $dry)
            + $this->refresh(HomeworkTopicTask::query(), 'Домашка', $resolver, $dry);

        $this->info("Снимков с изменениями: {$changed}");
        if ($dry) {
            $this->warn('--dry-run: в базу не писали');
        }

        return 0;
    }

    private function refresh($query, string $label, TaskBankResolver $resolver, bool $dry): int
    {
        $changed = 0;

        $query->whereRaw('JSON_EXTRACT(task_payload, "$.raw.fipi_guid") IS NOT NULL')
            ->chunkById(200, function ($tasks) use ($label, $resolver, $dry, &$changed) {
                /** @var Model $task */
                foreach ($tasks as $task) {
                    $payload = $task->task_payload ?? [];
                    $fresh = $resolver->fipiTextFromSnapshot($payload['raw']);

                    $next = $payload;
                    foreach ($fresh as $key => $value) {
                        // Поле, которого в снимке не было, не добавляем: снимок
                        // собран другим путём, и интерфейс его не ждёт.
                        if (array_key_exists($key, $payload)) {
                            $next[$key] = $value;
                        }
                    }
                    if ($next === $payload) {
                        continue;
                    }

                    $changed++;
                    $this->line(sprintf('%s · задача %d:', $label, $task->id));
                    foreach (['expression', 'options'] as $key) {
                        if (($next[$key] ?? null) !== ($payload[$key] ?? null)) {
                            $this->line('  ' . $key . ': ' . $this->show($payload[$key] ?? null) . ' → ' . $this->show($next[$key]));
                        }
                    }

                    if (!$dry) {
                        $task->task_payload = $next;
                        $task->save();
                    }
                }
            });

        return $changed;
    }

    private function show($value): string
    {
        if (is_array($value)) {
            return implode(' | ', array_map(static fn ($o) => (string) ($o['label'] ?? ''), $value));
        }

        return mb_strimwidth((string) $value, 0, 80, '…');
    }
}
