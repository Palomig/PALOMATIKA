<?php

namespace App\Services\Print;

use App\Models\Task;
use App\Models\TaskIntro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Отбор заданий для печатного варианта из банка ОГЭ.
 *
 * Отбор детерминирован по seed: один и тот же seed даёт тот же вариант, что
 * позволяет перепечатать выданную работу и сверить её с ключом, не храня
 * состав в базе.
 */
class PrintVariantSelector
{
    /** Темы первой части и второй части в порядке нумерации заданий. */
    private const PART1 = ['01','02','03','04','05','06','07','08','09','10',
                           '11','12','13','14','15','16','17','18','19'];
    private const PART2 = ['20','21','22','23','24','25'];

    /** Практико-ориентированный блок 1–5 всегда идёт одним сюжетом. */
    private const SCENARIO_TOPICS = ['01','02','03','04','05'];

    /** Темы, где задание — доказательство и ответа по смыслу нет. */
    private const PROOF_TOPICS = ['24'];

    /** @var array<string, Collection<int, Task>> Кэш пулов по ключу темы. */
    private array $pools = [];

    /** @var array<int, true> Уже разданные задачи — не повторяются внутри пачки. */
    private array $used = [];

    public function __construct(private readonly string $bank = 'oge')
    {
    }

    /** Вводный текст выбранного сюжета — печатается один раз перед заданием 1. */
    private ?TaskIntro $intro = null;

    public function intro(): ?TaskIntro
    {
        return $this->intro;
    }

    /**
     * @param list<string>|null $topics Ограничить темы (по умолчанию все 25).
     * @return list<array{number: int, topic: string, task: Task, part2: bool}>
     */
    public function select(int $seed, ?array $topics = null): array
    {
        mt_srand($seed);

        $wanted = $topics ?? array_merge(self::PART1, self::PART2);
        $result = [];

        $this->intro = null;
        $scenarioTopics = array_values(array_intersect(self::SCENARIO_TOPICS, $wanted));
        $scenario = $scenarioTopics === [] ? null : $this->pickScenario();
        if ($scenario !== null) {
            $this->intro = TaskIntro::where('bank', $this->bank)->where('guid', $scenario)->first();
        }

        foreach ($wanted as $topic) {
            $number = (int) ltrim($topic, '0');
            $isScenario = $scenario !== null && in_array($topic, self::SCENARIO_TOPICS, true);

            $task = $isScenario
                ? $this->pickFromScenario($topic, $scenario)
                : $this->pick($topic);

            if ($task === null) {
                continue;
            }

            $this->used[$task->id] = true;

            $result[] = [
                'number' => $number,
                'topic' => $topic,
                'task' => $task,
                'part2' => in_array($topic, self::PART2, true),
            ];
        }

        mt_srand();

        return $result;
    }

    /**
     * Сюжет, представленный во всех пяти темах блока 1–5.
     *
     * Ключ сюжета — GUID вводного текста, а не название задания. Названия
     * задают лишь род сюжета: «План местности» — это два десятка разных планов,
     * и собранный по названию блок 1–5 состоял бы из вопросов к пяти разным
     * картам. Из 38 сюжетов банка полных 37; неполный отбрасывается, иначе
     * в работе окажется задание без своего условия.
     */
    private function pickScenario(): ?string
    {
        $guids = DB::table('tasks as t')
            ->join('task_groups as g', 'g.id', '=', 't.task_group_id')
            ->where('g.bank', $this->bank)
            ->whereIn('g.topic', self::SCENARIO_TOPICS)
            ->where('g.status', 'production')
            ->where('t.status', 'production')
            ->whereNotNull('t.intro_guid')
            ->groupBy('t.intro_guid')
            ->havingRaw('COUNT(DISTINCT g.topic) = ?', [count(self::SCENARIO_TOPICS)])
            ->pluck('t.intro_guid')
            ->all();

        if ($guids === []) {
            return null;
        }

        sort($guids);

        return $guids[mt_rand(0, count($guids) - 1)];
    }

    private function pickFromScenario(string $topic, ?string $scenario): ?Task
    {
        if ($scenario === null) {
            return $this->pick($topic);
        }

        return $this->takeFrom($this->pool($topic, $scenario)) ?? $this->pick($topic);
    }

    private function pick(string $topic): ?Task
    {
        return $this->takeFrom($this->pool($topic, null));
    }

    /**
     * Свежая задача из пула.
     *
     * Сначала пытаемся выдать ещё не использованную в этой пачке, и только
     * если весь пул исчерпан — разрешаем повтор. Иначе на теме с двумя
     * задачами генерация третьего варианта молча возвращала бы пустоту.
     */
    private function takeFrom(Collection $pool): ?Task
    {
        if ($pool->isEmpty()) {
            return null;
        }

        $fresh = $pool->reject(fn (Task $t): bool => isset($this->used[$t->id]))->values();
        $from = $fresh->isNotEmpty() ? $fresh : $pool;

        return $from[mt_rand(0, $from->count() - 1)];
    }

    /** @return Collection<int, Task> */
    private function pool(string $topic, ?string $introGuid): Collection
    {
        $key = $topic . '|' . ($introGuid ?? '*');

        if (!isset($this->pools[$key])) {
            $query = Task::query()
                ->whereHas('group', function ($q) use ($topic): void {
                    $q->where('bank', $this->bank)
                        ->where('topic', $topic)
                        ->where('status', 'production');
                });

            // Тема 24 — доказательства, и все её 60 заданий лежат в draft
            // ровно потому, что у них нет ответа. На экране это правильно:
            // проверять нечего. На бумаге доказательство — нормальное задание
            // второй части, ученик пишет его в бланк. Поэтому здесь draft
            // допускается, но только там, где отсутствие ответа и есть причина:
            // задание с ответом, залежавшееся в draft, — это недоделка,
            // и в работу оно не попадёт.
            if (in_array($topic, self::PROOF_TOPICS, true)) {
                $query->where(function ($q): void {
                    $q->where('status', 'production')
                        ->orWhere(fn ($q2) => $q2->where('status', 'draft')->whereNull('answer'));
                });
            } else {
                $query->where('status', 'production');
            }

            if ($introGuid !== null) {
                $query->where('intro_guid', $introGuid);
            }

            $this->pools[$key] = $query->orderBy('id')->get();
        }

        return $this->pools[$key];
    }
}
