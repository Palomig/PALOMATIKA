<?php

namespace App\Console\Commands;

use App\Models\HomeworkTopicTask;
use App\Models\LessonSessionTask;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * `\dfrac` внутри показателя степени или индекса → `\frac`.
 *
 * Экспорт банка ФИПИ переводил каждую дробь MathML в `\dfrac`, и в
 * показателе это давало дробь в полный рост: `4^{\dfrac{1}{5}}` KaTeX
 * рисует так, что «1/5» стоит рядом с четвёркой, а не над ней, — на уроке
 * ученик и учитель не видели, что это степень. `\frac` в показателе KaTeX
 * сам уменьшает до размера индекса, как в печатном КИМ.
 *
 * Правится только содержимое групп `^{…}` и `_{…}` (со вложенными скобками),
 * дроби в основной строке остаются `\dfrac`. Проходит по банку задач и по
 * снимкам, уже розданным ученикам: задачи урока и домашки хранят копию
 * условия, и без этого исправленное в банке задание на уроке осталось бы
 * прежним. Идемпотентна — повторный запуск ничего не меняет.
 */
class FixScriptDfrac extends Command
{
    protected $signature = 'tasks:fix-script-dfrac {--dry-run : показать, что изменится, ничего не менять}';

    protected $description = '\\dfrac в показателях степени и индексах → \\frac (банк и снимки задач)';

    /**
     * Группа `^{…}` или `_{…}` с учётом вложенных скобок (рекурсия PCRE).
     * Пробел между знаком и скобкой допустим — так пишет часть экспорта.
     */
    private const SCRIPT_GROUP = '/[\^_]\s*\{((?:[^{}]++|\{(?1)\})*)\}/';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $touched = [];

        $tasks = 0;
        foreach (Task::with('group:id,bank,topic')->whereRaw("payload LIKE '%dfrac%'")->cursor() as $task) {
            $fixed = self::fixValue($task->payload);
            if ($fixed === $task->payload) {
                continue;
            }
            $tasks++;
            $label = ($task->group->bank ?? '?') . '/' . ($task->group->topic ?? '?');
            $this->line("  task #{$task->id} ({$label}): " . self::describe($task->payload, $fixed));
            if (!$dry) {
                $task->payload = $fixed;
                $task->save();
                $touched[$label] = [$task->group->bank ?? '', (string) ($task->group->topic ?? '')];
            }
        }

        $lessons = $this->fixSnapshots(LessonSessionTask::class, 'task_payload', 'lesson task', $dry);
        $homework = $this->fixSnapshots(HomeworkTopicTask::class, 'task_payload', 'homework task', $dry);

        if (!$dry) {
            foreach ($touched as [$bank, $topic]) {
                Cache::forget($bank === 'oge' ? "topic_data_{$topic}" : "{$bank}_topic_data_{$topic}");
            }
        }

        $this->info(($dry ? '[dry-run] ' : '') . "банк: {$tasks}, задачи уроков: {$lessons}, задачи домашки: {$homework}");

        return self::SUCCESS;
    }

    private function fixSnapshots(string $model, string $column, string $label, bool $dry): int
    {
        $count = 0;
        foreach ($model::whereRaw("{$column} LIKE '%dfrac%'")->cursor() as $row) {
            $fixed = self::fixValue($row->{$column});
            if ($fixed === $row->{$column}) {
                continue;
            }
            $count++;
            $this->line("  {$label} #{$row->id}: " . self::describe($row->{$column}, $fixed));
            if (!$dry) {
                $row->{$column} = $fixed;
                $row->save();
            }
        }

        return $count;
    }

    /** Обходит payload целиком: условие лежит в `html`, `expression`, `raw`, вариантах. */
    public static function fixValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'fixValue'], $value);
        }
        if (!is_string($value) || !str_contains($value, '\\dfrac')) {
            return $value;
        }

        return preg_replace_callback(
            self::SCRIPT_GROUP,
            static fn (array $m): string => str_replace('\\dfrac', '\\frac', $m[0]),
            $value
        ) ?? $value;
    }

    private static function describe(mixed $before, mixed $after): string
    {
        $a = json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $b = json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        preg_match_all(self::SCRIPT_GROUP, (string) $a, $groups);
        $changed = array_values(array_filter($groups[0] ?? [], static fn (string $g) => str_contains($g, '\\dfrac')));

        return implode(' ', array_slice($changed, 0, 3)) . (count($changed) > 3 ? ' …' : '')
            . ' (' . (strlen((string) $a) - strlen((string) $b)) . ' симв.)';
    }
}
