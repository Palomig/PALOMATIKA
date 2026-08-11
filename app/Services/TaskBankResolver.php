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
        // statements: задание само по себе одна задача (нет массива tasks).
        if (($z['type'] ?? '') === 'statements' && empty($z['tasks'])) {
            $synth = self::synthesizeStatementsTask($z);
            if ((string) $synth['id'] === (string) $taskId) {
                return [$z, $synth];
            }
            throw new DomainException("Task #{$taskId} not found in statements zadanie #{$zadanieNumber}");
        }
        foreach ($z['tasks'] ?? [] as $task) {
            if ((string) ($task['id'] ?? '') === (string) $taskId) {
                return [$z, $task];
            }
        }
        throw new DomainException("Task #{$taskId} not found in zadanie #{$zadanieNumber}");
    }

    private const IMAGE_LIKE_TYPES = [
        'matching', 'matching_signs', 'matching_4', 'matching_full',
        'graph_statements', 'statements',
        'geometry', 'grid_image', 'grid_image_with_question',
    ];

    /** Текстовые типы без картинки: условие в text/instruction + числовой ответ. */
    private const TEXT_EXPRESSION_TYPES = ['word_problem'];

    /**
     * Для задания типа statements (нет массива tasks) синтезирует одну псевдо-задачу:
     * условие = инструкция + перечень утверждений, ответ = номера верных по возрастанию.
     * Используется и picker'ом, и резолвером, чтобы их представление совпадало.
     *
     * @return array{id:int,task_type:string,text:string,answer:string,statements:array}
     */
    public static function synthesizeStatementsTask(array $zadanie): array
    {
        $lines = [];
        $trueIds = [];
        foreach ($zadanie['statements'] ?? [] as $s) {
            $id = $s['id'] ?? null;
            $lines[] = ($id !== null ? "{$id}) " : '') . (string) ($s['text'] ?? '');
            if (!empty($s['is_true']) && $id !== null) {
                $trueIds[] = (int) $id;
            }
        }
        sort($trueIds);
        $text = trim((string) ($zadanie['instruction'] ?? '') . "\n" . implode("\n", $lines));

        return [
            'id'         => 1,
            'task_type'  => 'statements',
            'text'       => $text,
            'answer'     => implode('', array_map('strval', $trueIds)),
            'statements' => $zadanie['statements'] ?? [],
        ];
    }

    /**
     * Нормализует задачу к {expression, type, answer, options?, image_svg?, source_label, raw}.
     * Throws DomainException, если тип не поддержан в v1.
     */
    private function normalize(array $task, array $zadanieContext, string $sourceLabel, ?string $forceType = null): array
    {
        $rawType = strtolower(trim((string) ($task['task_type'] ?? $zadanieContext['type'] ?? '')));
        $type = $forceType ?? $this->normalizeType($rawType, $task);

        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new DomainException("Task type '{$type}' not supported in v1 (only expression/choice)");
        }

        $expression = (string) ($task['expression'] ?? $task['prompt'] ?? $task['question'] ?? $task['text'] ?? '');
        if ($expression === '' && $rawType === 'fipi') {
            $expression = self::conditionFromFipiHtml((string) ($task['html'] ?? ''));
        }
        if ($expression === '' && isset($zadanieContext['instruction'])) {
            $expression = (string) $zadanieContext['instruction'];
        }
        if ($rawType === 'fipi' && !empty($task['multi_select'])) {
            $statements = array_map(
                static fn (array $option): string => sprintf(
                    '%s) %s',
                    (string) ($option['n'] ?? ''),
                    self::textFromHtml((string) ($option['html'] ?? ''))
                ),
                $task['options'] ?? []
            );
            $expression = trim($expression . "\n" . implode("\n", $statements));
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
                static function (array $option): array {
                    $label = (string) ($option['label'] ?? $option['text'] ?? $option['value'] ?? '');
                    if ($label === '') {
                        $label = self::textFromHtml((string) ($option['html'] ?? ''));
                    }

                    return [
                        'id' => (string) ($option['id'] ?? $option['n'] ?? ''),
                        'label' => $label,
                    ];
                },
                $task['options'] ?? []
            ));
        }

        // Картинка/SVG, если есть. Используется для matching и подобных задач:
        // ученик видит график и вводит ответ свободным текстом.
        $svg = (string) ($task['svg'] ?? '');
        if ($svg === '' && $rawType === 'fipi') {
            $svg = self::singleSvgFromHtml((string) ($task['html'] ?? ''));
        }
        if ($svg !== '') {
            $result['image_svg'] = $svg;
        }
        $imageUrl = (string) ($task['image'] ?? '');
        if ($imageUrl === '' && $rawType === 'fipi') {
            // У банка ЕГЭ чертёж — растр внутри разметки условия, а не
            // отдельное поле и не инлайновый SVG, как в ОГЭ. Без этого урок
            // показывал одно условие: `textFromHtml` вырезает все теги, и
            // рисунок пропадал молча.
            $imageUrl = self::figureFromHtml((string) ($task['html'] ?? ''));
        }
        if ($imageUrl !== '') {
            $result['image_url'] = $imageUrl;
        }

        return $result;
    }

    /**
     * Приводит «сырой» тип к 'expression' | 'choice' | оригинальное имя.
     * Если у задачи есть options и тип не matching-like — относим к choice.
     * Matching / graph_statements / statements нормализуем к 'expression':
     * ученик увидит картинку + поле ввода (ответ '312' и т.п. сверяется auto-check'ом).
     */
    private function normalizeType(?string $rawType, array $task): string
    {
        $rawType = strtolower(trim((string) $rawType));

        if ($rawType === 'fipi') {
            return !empty($task['options']) && empty($task['multi_select'])
                ? 'choice'
                : 'expression';
        }
        if (in_array($rawType, self::IMAGE_LIKE_TYPES, true)) {
            return 'expression';
        }
        if (in_array($rawType, self::TEXT_EXPRESSION_TYPES, true)) {
            return 'expression';
        }
        if (!empty($task['options'])) {
            return 'choice';
        }
        return match (true) {
            $rawType === '' || $rawType === 'expression' => 'expression',
            str_contains($rawType, 'choice')             => 'choice',
            default                                       => $rawType,
        };
    }

    /** Текст условия или варианта из доверенной HTML-разметки импорта ФИПИ. */
    private static function textFromHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<svg\b.*?<\/svg>/is', '', $html) ?? $html;
        $html = preg_replace('/<br\s*\/?>|<\/(?:p|div|td|tr|li)>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]*\n[ \t]*/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{2,}/u', "\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Условие задания ФИПИ для урока.
     *
     * Отличается от `textFromHtml` одним: обозначения, набранные растрами
     * внутри предложения («SABCD», «AM = 2»), сохраняются. Вырезанные, они
     * оставляют дыры — «На рёбрах и отмечены точки и соответственно,
     * причём , .», — и условие становится нечитаемым. Экраны урока выводят
     * его через `x-html`, поэтому картинка доедет.
     *
     * Самостоятельный чертёж (`fipi-figure`) здесь не нужен: он уезжает
     * отдельным полем `image_url`.
     */
    private static function conditionFromFipiHtml(string $html): string
    {
        if ($html === '' || !str_contains($html, 'fipi-inline')) {
            return self::textFromHtml($html);
        }

        $kept = [];
        $prepared = preg_replace_callback(
            '/<img\b[^>]*>/i',
            static function (array $match) use (&$kept): string {
                if (!str_contains($match[0], 'fipi-inline')) {
                    return '';
                }
                $kept[] = $match[0];

                // Маркер из редких символов, а не нулевой байт: strip_tags
                // внутри textFromHtml вырезает NUL, и картинки терялись.
                return '⟦' . (count($kept) - 1) . '⟧';
            },
            $html
        ) ?? $html;

        $text = self::textFromHtml($prepared);

        return preg_replace_callback(
            '/⟦(\d+)⟧/u',
            static fn (array $match): string => $kept[(int) $match[1]] ?? '',
            $text
        ) ?? $text;
    }

    /** Единственный встроенный SVG условия; несколько рисунков не склеиваем. */
    private static function singleSvgFromHtml(string $html): string
    {
        if (substr_count($html, '<svg') !== 1
            || !preg_match('/<svg\b.*?<\/svg>/is', $html, $match)) {
            return '';
        }

        return $match[0];
    }

    /**
     * Адрес чертежа из разметки условия.
     *
     * Берётся только `fipi-figure` — самостоятельный рисунок. Растры с
     * классом `fipi-inline` это обозначения внутри предложения («SABCD»,
     * «AM = 2»), и показывать их как иллюстрацию к задаче бессмысленно.
     */
    private static function figureFromHtml(string $html): string
    {
        if ($html === '' || !preg_match_all('/<img\b[^>]*>/i', $html, $images)) {
            return '';
        }

        foreach ($images[0] as $img) {
            if (!str_contains($img, 'fipi-figure')) {
                continue;
            }
            if (preg_match('/\bsrc="([^"]+)"/i', $img, $src)) {
                return $src[1];
            }
        }

        return '';
    }

    private function requireRefs(array $refs, array $required): void
    {
        $missing = array_diff($required, array_keys($refs));
        if ($missing) {
            throw new InvalidArgumentException('Missing refs: ' . implode(',', $missing));
        }
    }
}
