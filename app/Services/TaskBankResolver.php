<?php

namespace App\Services;

use DomainException;
use InvalidArgumentException;

/**
 * Унифицированный доступ к 5 банкам заданий.
 *
 * resolve(bank, refs) → нормализованный массив:
 *   [expression, type, answer, options?, source_label, raw]
 *
 * В v1 принимает только type ∈ {'expression', 'choice'}.
 * Прочие типы (geometry, matching, statements, …) → DomainException.
 */
class TaskBankResolver
{
    public const BANKS = ['oge', 'ege', 'vpr', 'alg-topic', 'alg-skill'];

    public const SUPPORTED_TYPES = ['expression', 'choice'];

    /**
     * @param  array<string, mixed>  $refs
     * @return array{expression:string,type:string,answer:string,options?:array,source_label:string,raw:array}
     */
    public function resolve(string $bank, array $refs): array
    {
        return match ($bank) {
            'oge'       => $this->fromOge($refs),
            'ege'       => $this->fromEge($refs),
            'vpr'       => $this->fromVpr($refs),
            'alg-topic' => $this->fromAlgTopic($refs),
            'alg-skill' => $this->fromAlgSkill($refs),
            default     => throw new InvalidArgumentException("Unknown bank: {$bank}"),
        };
    }

    private function fromOge(array $refs): array
    {
        $this->requireRefs($refs, ['topic_id', 'zadanie_number', 'task_id']);
        $topic = (new TaskDataService())->getTopicData($refs['topic_id']);
        [$z, $task] = $this->findTaskInBlocks($topic['blocks'] ?? [], $refs['zadanie_number'], $refs['task_id']);
        $label = "ОГЭ · Тема {$refs['topic_id']} · Задание {$refs['zadanie_number']}.{$refs['task_id']}";
        return $this->normalize($task, $z, $label);
    }

    private function fromEge(array $refs): array
    {
        $this->requireRefs($refs, ['topic_id', 'zadanie_number', 'task_id']);
        $topic = (new EgeTaskDataService())->getTopicData($refs['topic_id']);
        [$z, $task] = $this->findTaskInBlocks($topic['blocks'] ?? [], $refs['zadanie_number'], $refs['task_id']);
        $label = "ЕГЭ · Тема {$refs['topic_id']} · Задание {$refs['zadanie_number']}.{$refs['task_id']}";
        return $this->normalize($task, $z, $label);
    }

    private function fromVpr(array $refs): array
    {
        $this->requireRefs($refs, ['grade', 'topic_id', 'zadanie_number', 'task_id']);
        $topic = (new VprTaskDataService((int) $refs['grade']))->getTopicData($refs['topic_id']);
        [$z, $task] = $this->findTaskInBlocks($topic['blocks'] ?? [], $refs['zadanie_number'], $refs['task_id']);
        $label = "ВПР · {$refs['grade']} класс · Тема {$refs['topic_id']} · Задание {$refs['zadanie_number']}.{$refs['task_id']}";
        return $this->normalize($task, $z, $label);
    }

    private function fromAlgTopic(array $refs): array
    {
        $this->requireRefs($refs, ['grade', 'topic_id', 'zadanie_number', 'task_id']);
        $topic = (new AlgTaskDataService((int) $refs['grade']))->getTopicData($refs['topic_id']);
        [$z, $task] = $this->findTaskInBlocks($topic['blocks'] ?? [], $refs['zadanie_number'], $refs['task_id']);
        $label = "Алгебра · {$refs['grade']} класс · Тема {$refs['topic_id']} · Задание {$refs['zadanie_number']}.{$refs['task_id']}";
        return $this->normalize($task, $z, $label);
    }

    private function fromAlgSkill(array $refs): array
    {
        $this->requireRefs($refs, ['grade', 'skill_slug', 'level_id', 'task_id']);
        $svc = new AlgTaskDataService((int) $refs['grade']);
        $skill = $svc->getSkillBySlug($refs['skill_slug']);
        if (!$skill) {
            throw new DomainException("Skill not found: {$refs['skill_slug']} (grade {$refs['grade']})");
        }
        $level = null;
        foreach ($skill['levels'] ?? [] as $lvl) {
            if (($lvl['id'] ?? null) === $refs['level_id']) {
                $level = $lvl;
                break;
            }
        }
        if (!$level) {
            throw new DomainException("Level {$refs['level_id']} not found in skill {$refs['skill_slug']}");
        }
        $task = null;
        foreach ($level['tasks'] ?? [] as $t) {
            if ((string) ($t['id'] ?? '') === (string) $refs['task_id']) {
                $task = $t;
                break;
            }
        }
        if (!$task) {
            throw new DomainException("Task #{$refs['task_id']} not found in {$refs['skill_slug']}/{$refs['level_id']}");
        }
        $label = "Алгебра · Навык {$skill['id']}. {$skill['title']} · {$level['title']} · №{$refs['task_id']}";
        // alg-skill: task_type ('signed_add'/'decimal_add'/...) — это доменная метка,
        // I/O всегда expression. Принудительно нормализуем.
        return $this->normalize($task, ['type' => 'expression'], $label, forceType: 'expression');
    }

    /**
     * Находит [zadanie, task] по номеру задания + id задачи. Бросает DomainException, если не найдено.
     */
    private function findTaskInBlocks(array $blocks, $zadanieNumber, $taskId): array
    {
        $z = null;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $candidate) {
                if ((string) ($candidate['number'] ?? '') === (string) $zadanieNumber) {
                    $z = $candidate;
                    break 2;
                }
            }
        }
        if (!$z) {
            throw new DomainException("Zadanie #{$zadanieNumber} not found");
        }
        foreach ($z['tasks'] ?? [] as $task) {
            if ((string) ($task['id'] ?? '') === (string) $taskId) {
                return [$z, $task];
            }
        }
        throw new DomainException("Task #{$taskId} not found in zadanie #{$zadanieNumber}");
    }

    /**
     * Нормализует задачу к {expression, type, answer, options?, source_label, raw}.
     * Throws DomainException, если тип не поддержан в v1.
     */
    private function normalize(array $task, array $zadanieContext, string $sourceLabel, ?string $forceType = null): array
    {
        $type = $forceType ?? $this->normalizeType($task['task_type'] ?? $zadanieContext['type'] ?? null, $task);

        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new DomainException("Task type '{$type}' not supported in v1 (only expression/choice)");
        }

        $expression = (string) ($task['expression'] ?? $task['prompt'] ?? $task['question'] ?? '');
        if ($expression === '' && isset($zadanieContext['instruction'])) {
            $expression = (string) $zadanieContext['instruction'];
        }

        $answer = (string) ($task['answer'] ?? '');

        $result = [
            'expression'   => $expression,
            'type'         => $type,
            'answer'       => $answer,
            'source_label' => $sourceLabel,
            'raw'          => $task,
        ];

        if ($type === 'choice') {
            $result['options'] = array_values(array_map(
                fn ($o) => [
                    'id'    => (string) ($o['id'] ?? ''),
                    'label' => (string) ($o['label'] ?? $o['text'] ?? $o['value'] ?? ''),
                ],
                $task['options'] ?? []
            ));
        }

        return $result;
    }

    /**
     * Приводит «сырой» тип к 'expression' | 'choice' | оригинальное имя.
     * Если у задачи есть options, относим к choice независимо от метки.
     */
    private function normalizeType(?string $rawType, array $task): string
    {
        if (!empty($task['options'])) {
            return 'choice';
        }
        $rawType = strtolower(trim((string) $rawType));
        return match (true) {
            $rawType === '' || $rawType === 'expression'        => 'expression',
            str_contains($rawType, 'choice')                    => 'choice',
            default                                              => $rawType,
        };
    }

    private function requireRefs(array $refs, array $required): void
    {
        $missing = array_diff($required, array_keys($refs));
        if ($missing) {
            throw new InvalidArgumentException('Missing refs: ' . implode(',', $missing));
        }
    }
}
