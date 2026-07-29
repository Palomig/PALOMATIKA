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
 *   php artisan tasks:attach-legacy-solutions --dry-run
 *   php artisan tasks:attach-legacy-solutions
 */
class AttachLegacySolutions extends Command
{
    protected $signature = 'tasks:attach-legacy-solutions
        {--dry-run : только показать, что нашлось}';

    protected $description = 'Перенести разборы и чертежи из отключённого банка на задания ФИПИ';

    private const CARRIED = ['solution', 'illustration', 'answer_hint'];

    public function handle(): int
    {
        $donors = $this->groupsWithExtras(TaskBankRepository::RETIRED);
        if ($donors === []) {
            $this->warn('в отключённом банке нет разборов — переносить нечего');
            return self::SUCCESS;
        }

        $targets = TaskGroup::query()
            ->with('tasks')
            ->where('bank', 'oge')
            ->where('source', 'fipi')
            ->get()
            ->groupBy('topic');

        $matched = $missed = 0;
        $updates = [];

        foreach ($donors as $donor) {
            $key = $this->signature($donor);
            $found = null;

            foreach ($targets[$donor->topic] ?? [] as $group) {
                if ($key !== '' && $this->signature($group) === $key) {
                    $found = $group;
                    break;
                }
            }

            // Запасной ключ — заголовок типа. Подтипы банка ФИПИ называли по
            // заголовкам прежнего банка, поэтому там, где текст задачи
            // переформулирован, названия всё равно совпадают.
            if ($found === null) {
                $title = $this->normalizeTitle($this->title($donor));
                foreach ($targets[$donor->topic] ?? [] as $group) {
                    if ($title !== '' && $this->normalizeTitle($this->title($group)) === $title) {
                        $found = $group;
                        break;
                    }
                }
            }

            if ($found === null) {
                $missed++;
                $this->line(sprintf('  не нашлось пары: тема %s, задание %d — %s',
                    $donor->topic, $donor->zadanie_number, mb_substr($this->title($donor), 0, 60)));
                continue;
            }

            $matched++;
            $updates[$found->id] = array_merge($found->payload ?? [], $this->extras($donor));
        }

        $this->newLine();
        $this->info(sprintf('%s: сопоставлено %d, без пары %d',
            $this->option('dry-run') ? 'ПОДСЧЁТ' : 'Перенесено', $matched, $missed));

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

    /** @return array<int, TaskGroup> */
    private function groupsWithExtras(string $source): array
    {
        return TaskGroup::query()
            ->with('tasks')
            ->where('bank', 'oge')
            ->where('source', $source)
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
