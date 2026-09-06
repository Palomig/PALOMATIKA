<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Разбить кураторскую группу на подтипы.
 *
 * Группы банка ФИПИ собраны по фигуре и приёму, но внутри одной группы лежат
 * серии с разными условиями: в «Высоте к гипотенузе» и «даны катеты», и «дан
 * катет с гипотенузой», и «дана проекция». Решаются они по-разному, поэтому в
 * интерфейсе группа раскрывается ещё на один уровень.
 *
 * Сами серии команда находит сама: у ФИПИ задачи одного прототипа записаны
 * дословно одинаково и отличаются только числами и числительными. Руками
 * задаются только названия — в файле, по одному списку на группу:
 *
 *   storage/app/tasks/subtypes/{bank}/topic_{NN}.json
 *   { "1": ["Даны катеты", "Даны катет и гипотенуза", ...], ... }
 *
 * Если число найденных серий разошлось с числом названий, команда ничего не
 * пишет и показывает, что нашла: значит, банк изменился и список пора обновить.
 *
 * Там, где серий заведомо больше, чем осмысленных подтипов (полсотни сюжетов
 * про вероятность решаются одним и тем же способом), подтип задаётся правилом
 * по тексту условия — регулярным выражением; задача уходит в первый подошедший:
 *
 *   { "1": [ {"title": "Один предмет из партии", "match": "партии|наугад"},
 *            {"title": "Жребий и номера", "match": "жреб|номер"} ] }
 *
 * Задача, не подошедшая ни под одно правило, и пустой подтип — тоже причина
 * ничего не писать: молча потерять задачу хуже, чем не разметить группу.
 *
 *   php artisan tasks:seed-subtypes --topic=23 --dry-run
 *   php artisan tasks:seed-subtypes
 */
class SeedTaskSubtypes extends Command
{
    protected $signature = 'tasks:seed-subtypes
        {--bank=oge : банк заданий}
        {--topic= : только одна тема}
        {--dry-run : только показать, что найдено}
        {--clear : снять разметку подтипов вместо её наложения}';

    protected $description = 'Разбить группы заданий на подтипы по тексту условия';

    public function handle(): int
    {
        $bank = (string) $this->option('bank');
        $root = storage_path("app/tasks/subtypes/{$bank}");
        if (!File::isDirectory($root)) {
            $this->warn("нет каталога {$root} — размечать нечего");
            return self::SUCCESS;
        }

        if ($this->option('clear')) {
            return $this->clear($bank);
        }

        $groupUpdates = $taskUpdates = [];
        $ok = $mismatched = 0;

        foreach (File::files($root) as $file) {
            if ($file->getExtension() !== 'json' || !preg_match('/topic_(\d+)$/', $file->getFilenameWithoutExtension(), $m)) {
                continue;
            }
            $topic = $m[1];
            if ($this->option('topic') !== null && ltrim($topic, '0') !== ltrim((string) $this->option('topic'), '0')) {
                continue;
            }

            $plan = json_decode(File::get($file->getPathname()), true) ?? [];
            foreach ($plan as $number => $titles) {
                if (!is_array($titles)) {
                    continue;   // служебные ключи вроде _comment
                }

                $group = TaskGroup::query()->with('tasks')
                    ->where('bank', $bank)->where('topic', $topic)
                    ->where('source', 'fipi')->where('zadanie_number', (int) $number)
                    ->first();

                if ($group === null) {
                    $this->warn("  нет группы: тема {$topic}, задание {$number}");
                    $mismatched++;
                    continue;
                }

                $rules = $this->rules($titles);
                if ($rules !== null) {
                    [$series, $error] = $this->byRules($group, $rules);
                    if ($error !== null) {
                        $mismatched++;
                        $this->error("  тема {$topic}, задание {$number}: {$error}");
                        continue;
                    }
                    $titles = array_column($rules, 'title');
                } else {
                    $series = $this->series($group);
                    if (count($series) !== count($titles)) {
                        $mismatched++;
                        $this->error(sprintf('  тема %s, задание %s: найдено серий %d, названий %d',
                            $topic, $number, count($series), count($titles)));
                        foreach ($series as $i => $ids) {
                            $this->line(sprintf('      серия %d — %d задач: %s',
                                $i + 1, count($ids), mb_substr($this->preview($group, $ids[0]), 0, 80)));
                        }
                        continue;
                    }
                }

                $ok++;
                $this->line(sprintf('  тема %s, задание %-2s — %d подтипов: %s',
                    $topic, $number, count($titles), implode(' · ', array_map(
                        static fn ($t, $ids) => $t . ' (' . count($ids) . ')', $titles, $series))));

                $groupUpdates[$group->id] = array_merge($group->payload ?? [], ['subtypes' => array_values($titles)]);
                foreach ($series as $i => $ids) {
                    foreach ($ids as $id) {
                        $taskUpdates[$id] = $i;
                    }
                }
            }
        }

        $this->newLine();
        $this->info(sprintf('%s: групп размечено %d, разошлось %d',
            $this->option('dry-run') ? 'ПОДСЧЁТ' : 'Готово', $ok, $mismatched));

        if ($this->option('dry-run') || $groupUpdates === []) {
            return $mismatched > 0 ? self::FAILURE : self::SUCCESS;
        }

        DB::transaction(function () use ($groupUpdates, $taskUpdates) {
            foreach ($groupUpdates as $id => $payload) {
                TaskGroup::query()->whereKey($id)->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
            }
            foreach (Task::query()->whereKey(array_keys($taskUpdates))->get() as $task) {
                $payload = $task->payload ?? [];
                $payload['subtype'] = $taskUpdates[$task->id];
                $task->update(['payload' => $payload]);
            }
        });

        return $mismatched > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Задачи группы, разложенные по сериям в порядке появления.
     *
     * @return array<int, array<int, int>> id задач по сериям
     */
    private function series(TaskGroup $group): array
    {
        $series = [];
        foreach ($group->tasks as $task) {
            $series[$this->signature($task)][] = $task->id;
        }

        return array_values($series);
    }

    /**
     * Снять разметку подтипов — путь назад, если деление не прижилось.
     * Трогаются только группы банка, размеченные командой, и их задачи.
     */
    private function clear(string $bank): int
    {
        $query = TaskGroup::query()->with('tasks')->where('bank', $bank)->where('source', 'fipi');
        if ($this->option('topic') !== null) {
            $query->where('topic', str_pad((string) $this->option('topic'), 2, '0', STR_PAD_LEFT));
        }

        $groups = $tasks = 0;
        foreach ($query->get() as $group) {
            $payload = $group->payload ?? [];
            if (!array_key_exists('subtypes', $payload)) {
                continue;
            }
            unset($payload['subtypes']);
            $groups++;

            DB::transaction(function () use ($group, $payload, &$tasks) {
                TaskGroup::query()->whereKey($group->id)
                    ->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);

                foreach ($group->tasks as $task) {
                    $taskPayload = $task->payload ?? [];
                    if (!array_key_exists('subtype', $taskPayload)) {
                        continue;
                    }
                    unset($taskPayload['subtype']);
                    $task->update(['payload' => $taskPayload]);
                    $tasks++;
                }
            });
        }

        $this->info("Разметка снята: групп {$groups}, задач {$tasks}");

        return self::SUCCESS;
    }

    /**
     * Правила подтипов, если группа размечена ими, а не сериями.
     *
     * @param  array<int, mixed>  $titles
     * @return array<int, array{title: string, match: string}>|null
     */
    private function rules(array $titles): ?array
    {
        $rules = [];
        foreach ($titles as $entry) {
            if (!is_array($entry) || !isset($entry['title'], $entry['match'])) {
                return null;
            }
            $rules[] = ['title' => (string) $entry['title'], 'match' => (string) $entry['match']];
        }

        return $rules === [] ? null : $rules;
    }

    /**
     * Задачи группы, разложенные по правилам: задача уходит в первый подошедший
     * подтип. Ошибка — если задача не подошла никуда или подтип остался пустым.
     *
     * @param  array<int, array{title: string, match: string}>  $rules
     * @return array{0: array<int, array<int, int>>, 1: string|null}
     */
    private function byRules(TaskGroup $group, array $rules): array
    {
        $series = array_fill(0, count($rules), []);

        foreach ($group->tasks as $task) {
            $text = $this->plainText($task);
            $matched = null;
            foreach ($rules as $i => $rule) {
                if (preg_match('/' . str_replace('/', '\/', $rule['match']) . '/iu', $text) === 1) {
                    $matched = $i;
                    break;
                }
            }
            if ($matched === null) {
                return [[], sprintf('задача #%d не подошла ни под одно правило: %s',
                    $task->id, mb_substr($text, 0, 120))];
            }
            $series[$matched][] = $task->id;
        }

        foreach ($rules as $i => $rule) {
            if ($series[$i] === []) {
                return [[], "правило «{$rule['title']}» не поймало ни одной задачи"];
            }
        }

        return [$series, null];
    }

    /** Условие задачи текстом: без чертежа, разметки и html-сущностей. */
    private function plainText(Task $task): string
    {
        $payload = $task->payload ?? [];
        $text = (string) ($payload['html'] ?? $payload['text'] ?? $payload['expression'] ?? '');
        $text = preg_replace('/<svg\b.*?<\/svg>/is', '', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function preview(TaskGroup $group, int $taskId): string
    {
        $task = $group->tasks->firstWhere('id', $taskId);
        $payload = $task?->payload ?? [];

        return trim(html_entity_decode(strip_tags((string) ($payload['html'] ?? $payload['text'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Числительные словами — такие же данные серии, как цифры: «в десятом ряду»
     * и «в одиннадцатом ряду» это один прототип ФИПИ с разными числами. Без
     * этого тема 14 распадалась на серии по одной задаче.
     */
    private const NUMERAL_WORDS = 'ноль|нул|одиннадцат|один|одна|одно|одну|одн|перв|'
        . 'двадцат|двенадцат|два|две|двух|двум|двумя|втор|дважды|вдвое|'
        . 'тринадцат|тридцат|три|трёх|трех|трём|трем|тремя|трёмя|трет|трижды|втрое|'
        . 'четырнадцат|четыре|четырёх|четырех|четырём|четырем|четырьмя|четвёрт|четверт|вчетверо|'
        . 'пятнадцат|пятьдесят|пятидесят|пятьсот|пятисот|пять|пяти|пятью|пят|'
        . 'шестнадцат|шестьдесят|шестидесят|шестьсот|шестисот|шесть|шести|шестью|шест|'
        . 'семнадцат|семьдесят|семидесят|семьсот|семисот|семь|семи|семью|седьм|'
        . 'восемнадцат|восемьдесят|восьмидесят|восемьсот|восьмисот|восемь|восьм|'
        . 'девятнадцат|девяност|девятьсот|девятисот|девять|девяти|девятью|девят|'
        . 'десять|десяти|десятью|десят|сорок|сорока|сто|ста|сот|сотен|тысяч|'
        . 'половин|полтора';

    /**
     * Отпечаток серии: текст условия без чисел и разметки. Задачи одного
     * прототипа ФИПИ записаны дословно одинаково, различаются только данные.
     */
    private function signature(Task $task): string
    {
        $payload = $task->payload ?? [];
        $text = (string) ($payload['html'] ?? $payload['text'] ?? $payload['expression'] ?? '');
        $text = preg_replace('/<svg\b.*?<\/svg>/is', '', $text) ?? $text;
        $text = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\\\\[a-zA-Z]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/(?<!\p{L})(?:' . self::NUMERAL_WORDS . ')\p{L}*/u', '#', $text) ?? $text;
        $text = preg_replace('/\d+([.,]\d+)?/u', '#', $text) ?? $text;
        $text = preg_replace('/[^\p{L}#]+/u', '', $text) ?? $text;
        // Одинаковые по смыслу условия могут отличаться числом данных в
        // формуле (\dfrac{2\sqrt{2}}{3} против \dfrac{\sqrt{11}}{6}) —
        // подряд идущие числа считаем за одно.
        return preg_replace('/#+/u', '#', $text) ?? $text;
    }
}
