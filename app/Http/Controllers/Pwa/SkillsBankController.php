<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Services\SkillsTaskDataService;
use Illuminate\Http\Request;

/**
 * Банк «Скиллы» в базе заданий — экран учителя.
 *
 * Скиллы сквозные: они не принадлежат ни ОГЭ, ни ЕГЭ, ни ВПР, поэтому
 * вход в них одинаковый из любого направления и любого класса. Ученику
 * раздел не показывается совсем: он готовится к своему экзамену, и лишний
 * банк в его базе только сбивает. Доступ закрыт не только кнопкой, но и
 * `role:teacher,admin` на маршруте.
 */
class SkillsBankController extends Controller
{
    public function index(Request $request)
    {
        $service = new SkillsTaskDataService();
        $topics = $service->getTopics();

        $selected = (string) $request->query('topic', '');
        $ids = array_column($topics, 'id');
        if (!in_array($selected, $ids, true)) {
            $selected = $ids[0] ?? '';
        }

        $groups = $selected === '' ? [] : $this->groups($service, $selected);

        return view('pwa.student.skills', [
            'topics' => $topics,
            'selectedTopic' => $selected,
            'meta' => $selected === '' ? ['title' => 'Скиллы', 'description' => ''] : $service->getTopicMeta($selected),
            'groups' => $groups,
            'taskCount' => array_sum(array_map(static fn (array $g) => count($g['tasks']), $groups)),
        ]);
    }

    /**
     * Задания темы: у скилла с делением по классам группа — класс, а внутри
     * неё уровни сложности разложены по подтипам.
     *
     * @return array<int, array{number:int,title:string,tasks:array,subtypes:array}>
     */
    private function groups(SkillsTaskDataService $service, string $topicId): array
    {
        $groups = [];
        foreach ($service->getBlocks($topicId) as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $tasks = [];
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if (($task['status'] ?? 'production') !== 'production') {
                        continue;
                    }
                    $tasks[] = [
                        'id' => $task['id'] ?? null,
                        'expression' => (string) ($task['expression'] ?? ''),
                        'answer' => (string) ($task['answer'] ?? ''),
                        'subtype' => isset($task['subtype']) ? (int) $task['subtype'] : null,
                    ];
                }
                if ($tasks === []) {
                    continue;
                }

                $subtypes = $this->subtypes($zadanie['subtypes'] ?? null, $tasks);
                foreach ($subtypes as $i => $subtype) {
                    $subtypes[$i]['chunks'] = $this->chunks($subtype['tasks']);
                }

                $groups[] = [
                    'number' => (int) ($zadanie['number'] ?? count($groups) + 1),
                    'title' => (string) ($zadanie['title'] ?? $zadanie['instruction'] ?? 'Задания'),
                    'instruction' => (string) ($zadanie['instruction'] ?? ''),
                    'tasks' => $tasks,
                    'subtypes' => $subtypes,
                    'chunks' => $subtypes === [] ? $this->chunks($tasks) : [],
                ];
            }
        }

        return $groups;
    }

    /**
     * Подуровень — двадцать задач подряд. Внутри уровня они отсортированы от
     * простых к сложным, поэтому «№21–40» — следующая ступень сложности.
     * Короткие списки не режем: спойлер ради пяти карточек только мешает.
     *
     * @return array<int, array{title:string,tasks:array}>
     */
    private function chunks(array $tasks, int $size = 20): array
    {
        if (count($tasks) <= $size * 2) {
            return [];
        }

        $chunks = [];
        foreach (array_chunk($tasks, $size) as $i => $part) {
            $from = $i * $size + 1;
            $chunks[] = [
                'title' => sprintf('Подуровень %d · №%d–%d', $i + 1, $from, $from + count($part) - 1),
                'tasks' => $part,
            ];
        }

        return $chunks;
    }

    /**
     * Уровни внутри класса. Если разметка неполная — показываем плоский
     * список: потерять задачу хуже, чем остаться без второго уровня.
     *
     * @return array<int, array{title:string,tasks:array}>
     */
    private function subtypes(?array $titles, array $tasks): array
    {
        if (empty($titles)) {
            return [];
        }

        $subtypes = [];
        foreach (array_values($titles) as $i => $title) {
            $subtypes[$i] = ['title' => (string) $title, 'tasks' => []];
        }

        foreach ($tasks as $task) {
            $i = $task['subtype'];
            if ($i === null || !isset($subtypes[$i])) {
                return [];
            }
            $subtypes[$i]['tasks'][] = $task;
        }

        foreach ($subtypes as $subtype) {
            if ($subtype['tasks'] === []) {
                return [];
            }
        }

        return array_values($subtypes);
    }
}
