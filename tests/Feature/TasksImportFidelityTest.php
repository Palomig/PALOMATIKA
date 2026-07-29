<?php

namespace Tests\Feature;

use App\Models\TaskGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Перенос банка в БД не должен ничего терять.
 *
 * Проверка идёт «туда и обратно»: импортируем ОГЭ из JSON, собираем структуру
 * обратно из базы и сверяем с исходным файлом. ОГЭ выбран потому, что он несёт
 * всё разнообразие — 22 типа только в теме 7, геометрию с `geometry`/`params`
 * и `svg`, соответствия, утверждения и картиночные варианты темы 13.
 */
class TasksImportFidelityTest extends TestCase
{
    use RefreshDatabase;

    private static bool $imported = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$imported) {
            Artisan::call('tasks:import-json', ['--bank' => 'oge']);
        }
    }

    public function test_every_oge_topic_survives_the_round_trip(): void
    {
        $files = $this->topicFiles();
        $this->assertNotEmpty($files, 'не найдено ни одного topic_NN.json');

        foreach ($files as $path) {
            $topic = str_replace(['topic_', '.json'], '', basename($path));
            $original = json_decode(File::get($path), true);
            $restored = $this->restore($topic);

            $this->assertSame(
                $this->canonical($this->flatten($original['blocks'] ?? [])),
                $this->canonical($restored),
                "тема {$topic}: структура после переноса не совпала с файлом"
            );
        }
    }

    public function test_legacy_keys_match_the_old_address_format(): void
    {
        $group = TaskGroup::query()->where('bank', 'oge')->where('topic', '16')->firstOrFail();
        $task = $group->tasks()->firstOrFail();

        $expected = sprintf(
            'topic_16_block_%d_zadanie_%d_task_%d',
            $group->block_number,
            $group->zadanie_number,
            $task->payload['id'],
        );

        $this->assertSame($expected, $task->legacy_task_key);
    }

    public function test_composite_answers_are_not_stringified(): void
    {
        // У десяти задач ВПР 5 класса ответ составной: ["в среду", "6"].
        // Приведение к строке молча превратило бы его в «Array».
        Artisan::call('tasks:import-json', ['--bank' => 'vpr']);

        $answers = \App\Models\Task::query()
            ->whereHas('group', fn ($q) => $q->where('bank', 'vpr')->where('topic', '04'))
            ->pluck('answer')
            ->filter(fn (?string $a) => $a !== null && str_starts_with($a, '['));

        $this->assertNotEmpty($answers, 'составные ответы ВПР не найдены');
        foreach ($answers as $answer) {
            $this->assertIsArray(json_decode($answer, true), "ответ не разбирается обратно: {$answer}");
            $this->assertStringNotContainsString('Array', $answer);
        }
    }

    /**
     * Рекурсивная сортировка ключей объектов. MySQL нормализует порядок ключей
     * внутри JSON-колонки («text» может встать перед «label»), и это
     * единственное, чем структура из базы отличается от файла. Порядок ключей
     * объекта смысла не несёт — код обращается к ним по имени; порядок
     * элементов СПИСКА при этом сохраняется, и он как раз значащий
     * (варианты ответа, задачи в задании).
     */
    private function canonical(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $isList = array_is_list($value);
        $value = array_map(fn ($v) => $this->canonical($v), $value);
        if (!$isList) {
            ksort($value);
        }
        return $value;
    }

    /** @return array<int, string> */
    private function topicFiles(): array
    {
        return array_values(array_filter(
            glob(storage_path('app/tasks/topic_*.json')) ?: [],
            static fn (string $p) => (bool) preg_match('/^topic_\d+\.json$/', basename($p)),
        ));
    }

    /**
     * Структура из базы в том же виде, в каком она лежит в файле:
     * плоский список «блок/задание» с задачами внутри.
     */
    private function restore(string $topic): array
    {
        $groups = TaskGroup::query()
            ->with('tasks')
            ->where('bank', 'oge')
            ->where('topic', $topic)
            ->orderBy('position')
            ->get();

        return $groups->map(function (TaskGroup $group) {
            $zadanie = array_merge($group->payload ?? [], array_filter([
                'number' => $group->zadanie_number,
                'instruction' => $group->instruction,
                'type' => $group->type,
                'svg_type' => $group->svg_type,
                'status' => $group->status,
            ], static fn ($v) => $v !== null));

            $zadanie['tasks'] = $group->tasks->map(function ($task) {
                $row = array_merge($task->payload ?? [], array_filter([
                    'type' => $task->type,
                    'answer' => $task->answer,
                    'status' => $task->status,
                ], static fn ($v) => $v !== null));
                ksort($row);
                return $row;
            })->all();

            ksort($zadanie);
            return [
                'block' => $group->block_number,
                'zadanie' => $zadanie,
            ];
        })->all();
    }

    /** Тот же плоский вид, но собранный из исходного JSON. */
    private function flatten(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $tasks = [];
                foreach (array_values($zadanie['tasks'] ?? []) as $task) {
                    $row = $task;
                    if (array_key_exists('answer', $row)) {
                        $row['answer'] = is_array($row['answer'])
                            ? json_encode($row['answer'], JSON_UNESCAPED_UNICODE)
                            : ($row['answer'] === null || $row['answer'] === '' ? null : (string) $row['answer']);
                        if ($row['answer'] === null) {
                            unset($row['answer']);
                        }
                    }
                    $row['status'] ??= 'draft';
                    ksort($row);
                    $tasks[] = $row;
                }

                $copy = $zadanie;
                unset($copy['tasks']);
                $copy['number'] = (int) ($copy['number'] ?? 1);
                $copy['type'] ??= 'expression';
                $copy['status'] ??= 'draft';
                $copy = array_filter($copy, static fn ($v) => $v !== null);
                $copy['tasks'] = $tasks;
                ksort($copy);

                $out[] = [
                    'block' => (int) ($block['number'] ?? 1),
                    'zadanie' => $copy,
                ];
            }
        }
        return $out;
    }
}
