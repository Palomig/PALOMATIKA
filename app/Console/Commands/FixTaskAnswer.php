<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Точечная правка эталонного ответа задачи банка.
 *
 * Зачем отдельная команда: ответы банков ФИПИ считали решатель и агенты
 * (`answer_src`), и ошибки в них находятся поштучно — на живой домашке.
 * Переимпорт банка ради одного ответа слишком груб: вместе с ним уедут
 * ручные переносы заданий ({@see MoveTaskGroups}) и разметка подтипов.
 *
 * Задача адресуется `fipi_guid`: он переживает пересборку банка, а `id`
 * строки — нет.
 *
 * Ответ пишется и в колонку `answer` (по ней ищут), и в `payload.answer` —
 * интерфейс собирает задачу ИЗ payload, и правка одной колонки была бы
 * невидимой (та же грабля, что с `status` при импорте банка ФИПИ).
 *
 * Выгрузка банка живёт отдельно (`fipi-sync/out_ege/…`): чтобы правка
 * пережила следующий переимпорт, ответ надо поправить и там.
 *
 *   php artisan tasks:fix-answer --guid=efb16f1c… --answer=67
 *   php artisan tasks:fix-answer --guid=… --answer=2 --src=claude --dry-run
 */
class FixTaskAnswer extends Command
{
    protected $signature = 'tasks:fix-answer
        {--guid= : fipi_guid задачи}
        {--answer= : правильный ответ}
        {--src=fix : чем помечать происхождение ответа (answer_src)}
        {--dry-run : показать, что изменится, и не писать}';

    protected $description = 'Исправить эталонный ответ задачи банка по её fipi_guid';

    public function handle(): int
    {
        $guid   = trim((string) $this->option('guid'));
        $answer = (string) $this->option('answer');
        $src    = (string) $this->option('src');

        if ($guid === '' || $answer === '') {
            $this->error('Нужны --guid и --answer');
            return 1;
        }

        $task = Task::with('group')->where('fipi_guid', $guid)->first();
        if (!$task) {
            $this->error("Задача с guid {$guid} не найдена");
            return 1;
        }

        $group = $task->group;
        $this->line(sprintf(
            'Банк %s · тема %s · задание %s · задача #%d',
            $group->bank ?? '?', $group->topic ?? '?', $group->zadanie_number ?? '?', $task->id
        ));
        $this->line("Было:  {$task->answer} ({$task->answer_src})");
        $this->line("Стало: {$answer} ({$src})");

        if ($this->option('dry-run')) {
            $this->warn('--dry-run: в базу не писали');
            return 0;
        }

        $payload = $task->payload ?? [];
        $payload['answer'] = $answer;
        $payload['answer_src'] = $src;

        $task->payload = $payload;
        $task->answer = $answer;
        $task->answer_src = $src;
        $task->save();

        if ($group && $group->bank && $group->topic) {
            Cache::forget("{$group->bank}_topic_data_{$group->topic}");
            Cache::forget("topic_data_{$group->topic}");
        }
        Cache::forget('picker:classes:v2');

        $this->info('Ответ исправлен');
        return 0;
    }
}
