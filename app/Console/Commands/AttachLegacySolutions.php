<?php

namespace App\Console\Commands;

use App\Models\TaskGroup;
use App\Services\TaskBankRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Перенести разборы и чертежи серий из отключённого банка на задания ФИПИ.
 *
 * В прежнем банке у заданий второй части лежал `solution` — подробный разбор,
 * который учитель открывал кнопкой «Подробнее · для учителя», и `illustration`
 * — чертёж серии. Банк ФИПИ этого не несёт: он собран из открытого банка, а
 * разборы писались руками.
 *
 * Сопоставление идёт по тексту задания с замаскированными числами: у одного
 * типа задачи текст совпадает дословно, а числа меняются от варианта к
 * варианту. Заголовки не годятся — у ФИПИ они длиннее и переформулированы.
 *
 * Автоматика ловит только пары «один к одному». Педагогическая перегруппировка
 * ФИПИ шире прежней: например, пять серий про движение по прямой слились в одну
 * группу «Скорость и разность времени в пути». Такие случаи перечислены руками
 * в MANUAL, а разборы подтипов склеиваются в одну страницу с подзаголовками.
 *
 *   php artisan tasks:attach-legacy-solutions --dry-run
 *   php artisan tasks:attach-legacy-solutions
 */
class AttachLegacySolutions extends Command
{
    protected $signature = 'tasks:attach-legacy-solutions
        {--dry-run : только показать, что нашлось}';

    protected $description = 'Перенести разборы и чертежи из отключённого банка на задания ФИПИ';

    private const CARRIED = ['solution', 'illustration', 'answer_hint'];

    /**
     * Ручная карта: тема → задание банка ФИПИ → задания прежнего банка,
     * которые в него вошли. Сверено по текстам задач; порядок доноров
     * задаёт порядок подтипов на странице разбора.
     */
    private const MANUAL = [
        '21' => [
            1 => [1, 2, 3, 4, 5],   // движение по прямой: туда-обратно, навстречу, вдогонку
            2 => [6],               // круговая трасса
            3 => [7],               // средняя скорость
            4 => [8],               // протяжённые тела (длина поезда)
            5 => [9, 10, 11, 12],   // движение по воде
            6 => [15],              // работа и производительность
            7 => [13, 14],          // проценты: концентрация и сухое вещество
        ],
        '23' => [
            5 => [6],               // биссектрисы углов при боковой стороне трапеции
            7 => [11],              // прямая, параллельная основаниям трапеции
            8 => [12],              // хорды и расстояния от центра
        ],
        '24' => [
            8 => [11, 12],          // две высоты: тупоугольный и остроугольный случаи
        ],
    ];

    public function handle(): int
    {
        $donors = $this->groupsWithExtras(TaskBankRepository::RETIRED);
        if ($donors === []) {
            $this->warn('в отключённом банке нет разборов — переносить нечего');
            return self::SUCCESS;
        }

        $byTopic = [];
        $byNumber = [];
        foreach ($donors as $donor) {
            $byTopic[$donor->topic][] = $donor;
            $byNumber[$donor->topic][$donor->zadanie_number] = $donor;
        }

        $targets = TaskGroup::query()
            ->with('tasks')
            ->where('bank', 'oge')
            ->where('source', 'fipi')
            ->orderBy('topic')->orderBy('zadanie_number')
            ->get();

        $used = [];
        $matched = $empty = 0;
        $updates = [];

        foreach ($targets as $target) {
            $found = $this->manualDonors($target, $byNumber) ?: $this->autoDonors($target, $byTopic);
            if ($found === []) {
                $empty++;
                continue;
            }

            $matched++;
            foreach ($found as $donor) {
                $used[$donor->id] = true;
            }
            $updates[$target->id] = array_merge($target->payload ?? [], $this->compose($found));
        }

        foreach ($donors as $donor) {
            if (!isset($used[$donor->id])) {
                $this->line(sprintf('  не нашлось пары: тема %s, задание %d — %s',
                    $donor->topic, $donor->zadanie_number, mb_substr($this->title($donor), 0, 60)));
            }
        }

        $this->newLine();
        $this->info(sprintf('%s: групп ФИПИ с разбором %d, без разбора %d',
            $this->option('dry-run') ? 'ПОДСЧЁТ' : 'Перенесено', $matched, $empty));

        if ($this->option('dry-run') || $updates === []) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $payload) {
                TaskGroup::query()->whereKey($id)->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
            }
        });

        return self::SUCCESS;
    }

    /**
     * Доноры из ручной карты.
     *
     * @param  array<string, array<int, TaskGroup>>  $byNumber
     * @return array<int, TaskGroup>
     */
    private function manualDonors(TaskGroup $target, array $byNumber): array
    {
        $numbers = self::MANUAL[$target->topic][$target->zadanie_number] ?? [];
        $found = [];
        foreach ($numbers as $number) {
            if (isset($byNumber[$target->topic][$number])) {
                $found[] = $byNumber[$target->topic][$number];
            }
        }

        return $found;
    }

    /**
     * Доноры, найденные автоматически: сперва по отпечатку текста задачи,
     * затем — запасным ключом по заголовку типа. Подтипы банка ФИПИ называли
     * по заголовкам прежнего банка, поэтому там, где текст задачи
     * переформулирован, названия всё равно совпадают.
     *
     * @param  array<string, array<int, TaskGroup>>  $byTopic
     * @return array<int, TaskGroup>
     */
    private function autoDonors(TaskGroup $target, array $byTopic): array
    {
        $signature = $this->signature($target);
        if ($signature !== '') {
            foreach ($byTopic[$target->topic] ?? [] as $donor) {
                if ($this->signature($donor) === $signature) {
                    return [$donor];
                }
            }
        }

        $title = $this->normalizeTitle($this->title($target));
        if ($title !== '') {
            foreach ($byTopic[$target->topic] ?? [] as $donor) {
                if ($this->normalizeTitle($this->title($donor)) === $title) {
                    return [$donor];
                }
            }
        }

        return [];
    }

    /**
     * Собрать переносимые поля. Несколько доноров — это подтипы одной группы
     * ФИПИ: их разборы идут подряд под своими подзаголовками, а чертёж и
     * подсказка берутся от первого донора, у которого они есть.
     *
     * @param  array<int, TaskGroup>  $donors
     * @return array<string, mixed>
     */
    private function compose(array $donors): array
    {
        if (count($donors) === 1) {
            return $this->extras($donors[0]);
        }

        $carried = [];
        $parts = [];
        foreach ($donors as $donor) {
            $extras = $this->extras($donor);
            $solution = trim((string) ($extras['solution'] ?? ''));
            if ($solution !== '') {
                $parts[] = '<h3 class="sol-part">' . e($this->subtypeTitle($donor)) . '</h3>' . $solution;
            }
            foreach (['illustration', 'answer_hint'] as $key) {
                if (!isset($carried[$key]) && !empty($extras[$key])) {
                    $carried[$key] = $extras[$key];
                }
            }
        }

        if ($parts !== []) {
            $carried['solution'] = implode('', $parts);
        }

        return $carried;
    }

    /** Заголовок подтипа без кураторской нумерации прежнего банка («III) …»). */
    private function subtypeTitle(TaskGroup $donor): string
    {
        $title = $this->title($donor);
        $title = preg_replace('/^\s*[IVX]+\)\s*/u', '', $title) ?? $title;
        $title = trim($title);

        return $title !== '' ? $title : "Задание {$donor->zadanie_number}";
    }

    /** @return array<int, TaskGroup> */
    private function groupsWithExtras(string $source): array
    {
        return TaskGroup::query()
            ->with('tasks')
            ->where('bank', 'oge')
            ->where('source', $source)
            ->orderBy('topic')->orderBy('zadanie_number')
            ->get()
            ->filter(fn (TaskGroup $g) => $this->extras($g) !== [])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function extras(TaskGroup $group): array
    {
        $payload = $group->payload ?? [];

        return array_filter(
            array_intersect_key($payload, array_flip(self::CARRIED)),
            static fn ($v) => is_string($v) ? trim($v) !== '' : !empty($v)
        );
    }

    private function title(TaskGroup $group): string
    {
        $payload = $group->payload ?? [];

        return (string) ($payload['section'] ?? $payload['instruction'] ?? $group->instruction ?? '');
    }

    private function normalizeTitle(string $title): string
    {
        $title = preg_replace('/\\\\[a-zA-Z]+/u', ' ', $title) ?? $title;
        $title = preg_replace('/\d+([.,]\d+)?/u', '#', mb_strtolower($title)) ?? $title;

        return preg_replace('/[^\p{L}#]+/u', '', $title) ?? $title;
    }

    /**
     * Отпечаток типа задания: текст первой задачи без чисел и разметки.
     * Числа маскируются — они и отличают варианты одного типа друг от друга.
     */
    private function signature(TaskGroup $group): string
    {
        $task = $group->tasks->first();
        if ($task === null) {
            return '';
        }

        $payload = $task->payload ?? [];
        $raw = (string) ($payload['text'] ?? $payload['html'] ?? $payload['expression'] ?? '');
        if ($raw === '') {
            return '';
        }

        $text = preg_replace('/<svg\b.*?<\/svg>/is', '', $raw) ?? $raw;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower($text);
        // Формульная разметка у банков разная: у прежнего «77°», у ФИПИ
        // «$77^\circ$». Имена команд LaTeX состоят из букв и иначе попали бы
        // в отпечаток, сделав одинаковые по смыслу тексты разными.
        $text = preg_replace('/\\\\[a-zA-Z]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\d+([.,]\d+)?/u', '#', $text) ?? $text;
        $text = preg_replace('/[^\p{L}#]+/u', '', $text) ?? $text;

        return $text;
    }
}
