<?php

namespace App\Support;

use App\Models\OgeAttempt;
use App\Models\User;

/**
 * Карта задач, которые ученик уже видел в своих вариантах (по попыткам).
 * Используется генерацией вариантов как анти-повтор: пул вариантов выпилен,
 * каждый старт — свежий рандом, но решённые примеры не повторяем,
 * пока банк темы не исчерпан.
 */
final class StudentSolvedTasks
{
    /**
     * @param string|array<int,string> $examTypes exam_type варианта ('oge', 'ege', 'vpr_7', …)
     * @param string|null $level уровень ЕГЭ ('prof'/'base'). У профиля и базы
     *   номера заданий совпадают, а банки разные: без этого фильтра решённая
     *   задача профиля исключала бы чужую задачу базы с тем же номером.
     * @return array<string, array<int,int>> topic_id ('06') => список task_id
     */
    public static function mapByTopic(User $user, string|array $examTypes, ?string $level = null): array
    {
        $examTypes = (array) $examTypes;

        $attempts = OgeAttempt::where('student_id', $user->id)
            ->whereHas('variant', fn ($q) => $q->whereIn('exam_type', $examTypes))
            ->with('variant:id,config_json')
            ->get();

        $map = [];
        foreach ($attempts as $attempt) {
            $config = $attempt->variant?->config_json ?? [];
            if ($level !== null && ($config['level'] ?? 'prof') !== $level) {
                continue;
            }
            foreach ($config['tasks'] ?? [] as $task) {
                $topicId = str_pad((string) ($task['topic_id'] ?? ''), 2, '0', STR_PAD_LEFT);
                $taskId = (int) ($task['task']['id'] ?? $task['task_id'] ?? $task['id'] ?? 0);
                if ($topicId === '00' || $taskId <= 0) {
                    continue;
                }
                $map[$topicId][$taskId] = $taskId;
            }
        }

        foreach ($map as $topic => $set) {
            $map[$topic] = array_values($set);
        }

        return $map;
    }
}
