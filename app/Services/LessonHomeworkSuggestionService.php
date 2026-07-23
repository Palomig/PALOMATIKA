<?php

namespace App\Services;

use App\Models\LessonSession;
use App\Models\LessonSessionAttempt;
use App\Models\LessonSessionTask;
use DomainException;
use InvalidArgumentException;

/**
 * Предложения «домашки по итогам урока»: задачи урока группируются по заданию
 * (тема+номер / навык+уровень) банка, к каждой группе подбираются аналоги —
 * другие задачи того же задания, не использованные на уроке.
 *
 * Список задач задания читается из JSON-банков, превью каждого аналога —
 * через TaskBankResolver (единый источник нормализации), недоступные типы
 * (geometry/matching и т.п.) пропускаются.
 */
class LessonHomeworkSuggestionService
{
    public function __construct(private readonly TaskBankResolver $resolver)
    {
    }

    /**
     * @return array<int, array{key:string,label:string,lesson_stats:array{task_count:int,solved:int},no_analogs:bool,suggestions:array}>
     */
    public function suggestionsFor(LessonSession $session): array
    {
        $groups = [];
        foreach ($session->tasks()->orderBy('position')->get() as $task) {
            $refs = $this->parseRefs($task->task_ref);
            if ($refs === null) {
                continue;
            }
            $key = $this->groupKey($task->bank, $refs);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'bank'          => $task->bank,
                    'refs'          => $refs,
                    'label'         => $this->groupLabel($task),
                    'used_task_ids' => [],
                    'lesson_row_ids' => [],
                ];
            }
            $groups[$key]['used_task_ids'][] = (string) ($refs['task_id'] ?? '');
            $groups[$key]['lesson_row_ids'][] = $task->id;
        }

        $out = [];
        foreach ($groups as $key => $g) {
            $allIds = $this->zadanieTaskIds($g['bank'], $g['refs']);
            $analogIds = array_values(array_filter(
                $allIds,
                fn ($id) => $id !== null && !in_array((string) $id, $g['used_task_ids'], true)
            ));

            $suggestions = [];
            foreach ($analogIds as $id) {
                $refs = $this->analogRefs($g['bank'], $g['refs'], $id);
                try {
                    $r = $this->resolver->resolve($g['bank'], $refs);
                } catch (DomainException | InvalidArgumentException $e) {
                    continue; // тип не поддержан в ДЗ — пропускаем
                }
                $suggestions[] = [
                    'bank'         => $g['bank'],
                    'refs'         => $refs,
                    'preview_text' => (string) ($r['expression'] ?? ''),
                    'preview_svg'  => $r['image_svg'] ?? null,
                ];
            }

            $out[] = [
                'key'          => $key,
                'label'        => $g['label'],
                'lesson_stats' => [
                    'task_count' => count($g['lesson_row_ids']),
                    'solved'     => $this->solvedCount($g['lesson_row_ids']),
                ],
                'no_analogs'   => $suggestions === [],
                'suggestions'  => $suggestions,
            ];
        }

        return $out;
    }

    private function parseRefs(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }
        $refs = json_decode($json, true);
        return is_array($refs) ? $refs : null;
    }

    private function groupKey(string $bank, array $refs): string
    {
        if ($bank === 'alg-skill') {
            return "alg-skill:{$refs['grade']}:{$refs['skill_slug']}:{$refs['level_id']}";
        }
        $grade = $refs['grade'] ?? '';
        return "{$bank}:{$grade}:{$refs['topic_id']}:{$refs['zadanie_number']}";
    }

    /** Заголовок задания = source_label задачи урока без хвоста «.N» / «· №N». */
    private function groupLabel(LessonSessionTask $task): string
    {
        $label = (string) ($task->task_payload['source_label'] ?? '');
        $label = preg_replace('/(\.\d+|\s*·\s*№\d+)$/u', '', $label);
        return trim((string) $label) ?: 'Задание';
    }

    /**
     * Все id задач того же задания (для oge/ege/vpr/alg-topic) или того же
     * уровня навыка (для alg-skill), в порядке банка.
     *
     * @return array<int, int|string|null>
     */
    private function zadanieTaskIds(string $bank, array $refs): array
    {
        if ($bank === 'alg-skill') {
            $skill = (new AlgTaskDataService((int) $refs['grade']))->getSkillBySlug($refs['skill_slug']);
            foreach ($skill['levels'] ?? [] as $level) {
                if (($level['id'] ?? null) === $refs['level_id']) {
                    return array_map(fn ($t) => $t['id'] ?? null, $level['tasks'] ?? []);
                }
            }
            return [];
        }

        $topic = $this->topicService($bank, $refs)->getTopicData((string) $refs['topic_id']);
        foreach ($topic['blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $z) {
                if ((string) ($z['number'] ?? '') !== (string) $refs['zadanie_number']) {
                    continue;
                }
                // statements: одна синтетическая задача с id=1 (см. TaskBankResolver).
                if (($z['type'] ?? '') === 'statements' && empty($z['tasks'])) {
                    return [1];
                }
                return array_map(fn ($t) => $t['id'] ?? null, $z['tasks'] ?? []);
            }
        }
        return [];
    }

    private function topicService(string $bank, array $refs)
    {
        return match ($bank) {
            'oge'       => new TaskDataService(),
            'ege'       => new EgeTaskDataService(),
            'vpr'       => new VprTaskDataService((int) $refs['grade']),
            'alg-topic' => new AlgTaskDataService((int) $refs['grade']),
            default     => throw new InvalidArgumentException("Unknown bank: {$bank}"),
        };
    }

    private function analogRefs(string $bank, array $base, $taskId): array
    {
        if ($bank === 'alg-skill') {
            return [
                'grade'      => $base['grade'],
                'skill_slug' => $base['skill_slug'],
                'level_id'   => $base['level_id'],
                'task_id'    => $taskId,
            ];
        }
        $refs = [
            'topic_id'       => $base['topic_id'],
            'zadanie_number' => $base['zadanie_number'],
            'task_id'        => $taskId,
        ];
        if (isset($base['grade'])) {
            $refs['grade'] = $base['grade'];
        }
        return $refs;
    }

    /** Сколько задач урока этой группы решено верно хотя бы одним учеником. */
    private function solvedCount(array $lessonRowIds): int
    {
        if ($lessonRowIds === []) {
            return 0;
        }
        return LessonSessionAttempt::whereIn('lesson_session_task_id', $lessonRowIds)
            ->where('is_correct', true)
            ->distinct()
            ->count('lesson_session_task_id');
    }
}
