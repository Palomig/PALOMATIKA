<?php

namespace App\Console\Commands;

use App\Models\TaskGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Перенос заданий (`task_groups`) вместе с их задачами из одной темы банка
 * в другую.
 *
 * Зачем: темы банка ФИПИ проставлены классификатором по тексту, и часть
 * серий попала не в свой номер. Переимпортировать банк ради нескольких
 * заданий нельзя — вместе с ними переедут и ручные правки, поэтому серия
 * переносится точечно.
 *
 * Что делает команда:
 *   • меняет `topic` у выбранных заданий;
 *   • даёт им новые `zadanie_number` и `position` — следующие свободные в
 *     теме-приёмнике, то есть серия встаёт в конец, а нумерация уже
 *     лежащих там заданий не сдвигается (иначе поехали бы отметки
 *     «решено» у учеников, они позиционные);
 *   • подставляет блок последнего задания приёмника — блоки собираются в
 *     {@see \App\Services\TaskBankRepository::topicData()} по смене номера
 *     подряд идущих заданий, и чужой номер блока разорвал бы блок надвое;
 *   • уплотняет `position` оставшихся заданий темы-источника;
 *   • сбрасывает кеш обеих тем.
 *
 * Задачи (`tasks`) ссылаются на задание по `task_group_id` и не трогаются.
 *
 *   php artisan tasks:move-groups --from=06 --to=09 --zadanie=5,6,7,8,12
 *   php artisan tasks:move-groups --from=06 --to=10 --zadanie=9,10,11,13 --dry-run
 */
class MoveTaskGroups extends Command
{
    protected $signature = 'tasks:move-groups
        {--bank=ege : банк (oge / ege / vpr / alg)}
        {--grade= : класс; у ОГЭ и ЕГЭ пусто}
        {--source=fipi : источник заданий (fipi / palomatika / …)}
        {--from= : тема-источник, как в базе: 06}
        {--to= : тема-приёмник: 09}
        {--zadanie= : номера заданий в теме-источнике через запятую}
        {--dry-run : только показать, в базу не писать}';

    protected $description = 'Перенести задания банка из одной темы в другую';

    public function handle(): int
    {
        $bank   = (string) $this->option('bank');
        $source = (string) $this->option('source');
        $grade  = $this->option('grade') === null || $this->option('grade') === ''
            ? null
            : (int) $this->option('grade');
        $from = $this->normalizeTopic((string) $this->option('from'));
        $to   = $this->normalizeTopic((string) $this->option('to'));

        $numbers = array_values(array_unique(array_filter(array_map(
            static fn ($n) => (int) trim($n),
            explode(',', (string) $this->option('zadanie'))
        ), static fn ($n) => $n > 0)));

        if ($from === '' || $to === '' || $numbers === []) {
            $this->error('Нужны --from, --to и --zadanie (например: --from=06 --to=09 --zadanie=5,6).');
            return self::FAILURE;
        }
        if ($from === $to) {
            $this->error('Тема-источник и тема-приёмник совпадают.');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        $groups = $this->scope($bank, $grade, $source, $from)
            ->whereIn('zadanie_number', $numbers)
            ->orderBy('position')
            ->get();

        // Переносим либо всё названное, либо ничего: молча пропущенный номер
        // означает, что команду зовут с неверными данными.
        $missing = array_diff($numbers, $groups->pluck('zadanie_number')->all());
        if ($missing !== []) {
            $this->error(sprintf(
                'В теме %s (банк %s, источник %s) нет заданий: %s',
                $from, $bank, $source, implode(', ', $missing)
            ));
            return self::FAILURE;
        }

        $dest = $this->scope($bank, $grade, $source, $to)->orderBy('position')->get();
        $nextZadanie = ((int) $dest->max('zadanie_number')) + 1;
        $nextPosition = $dest->isEmpty() ? 0 : ((int) $dest->max('position')) + 1;
        $lastBlock = $dest->last();

        $plan = [];
        foreach ($groups as $i => $group) {
            $plan[] = [
                'group'          => $group,
                'zadanie_number' => $nextZadanie + $i,
                'position'       => $nextPosition + $i,
                'block_number'   => $lastBlock->block_number ?? $group->block_number,
                'block_title'    => $lastBlock->block_title ?? $group->block_title,
            ];
        }

        $this->table(
            ['id', 'было', 'станет', 'блок', 'задач', 'инструкция'],
            array_map(fn (array $p) => [
                $p['group']->id,
                "{$from} · №{$p['group']->zadanie_number}",
                "{$to} · №{$p['zadanie_number']}",
                $p['block_number'],
                $p['group']->tasks()->count(),
                mb_substr((string) $p['group']->instruction, 0, 50),
            ], $plan)
        );

        if ($dry) {
            $this->info('dry-run: ничего не записано.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan, $to, $bank, $grade, $source, $from) {
            foreach ($plan as $p) {
                $p['group']->update([
                    'topic'          => $to,
                    'block_number'   => $p['block_number'],
                    'block_title'    => $p['block_title'],
                    'zadanie_number' => $p['zadanie_number'],
                    'position'       => $p['position'],
                ]);
            }
            $this->resequence($bank, $grade, $source, $from);
        });

        $this->forgetTopicCache($bank, $from);
        $this->forgetTopicCache($bank, $to);
        Cache::forget('picker:classes:v2');

        $this->info(sprintf('Перенесено заданий: %d (%s → %s).', count($plan), $from, $to));

        return self::SUCCESS;
    }

    /** Задания одной темы одного источника. */
    private function scope(string $bank, ?int $grade, string $source, string $topic)
    {
        return TaskGroup::query()
            ->where('bank', $bank)
            ->where('grade', $grade)
            ->where('source', $source)
            ->where('topic', $topic);
    }

    /**
     * Уплотнить `position` после переноса. `zadanie_number` не трогаем: он
     * попадает в ссылки на задачу (урок, домашка, отметки «решено»), и
     * сдвиг переименовал бы оставшиеся задания.
     */
    private function resequence(string $bank, ?int $grade, string $source, string $topic): void
    {
        $rest = $this->scope($bank, $grade, $source, $topic)->orderBy('position')->get();
        foreach ($rest->values() as $i => $group) {
            if ($group->position !== $i) {
                $group->update(['position' => $i]);
            }
        }
    }

    /** Ключи кеша тем — свои у каждого банка. */
    private function forgetTopicCache(string $bank, string $topic): void
    {
        Cache::forget($bank === 'ege' ? "ege_topic_data_{$topic}" : "topic_data_{$topic}");
    }

    /** В базе тема — строка с ведущим нулём: «6» и «06» это одно и то же. */
    private function normalizeTopic(string $topic): string
    {
        $topic = trim($topic);
        if ($topic === '') return '';
        return ctype_digit($topic) && strlen($topic) < 2 ? str_pad($topic, 2, '0', STR_PAD_LEFT) : $topic;
    }
}
