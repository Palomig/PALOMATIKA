<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\HomeworkTopicTask;
use App\Models\OgeVariant;
use App\Support\TaskConditionFormatter;
use App\Support\TaskConditionHtml;
use Illuminate\Support\Collection;

/** Read-only projection of issued snapshots. Never starts or regenerates a task. */
class HomeworkTaskPreviewService
{
    /** @param Collection<int, Homework> $homeworks */
    public function forHomeworks(Collection $homeworks): array
    {
        $homeworks = $homeworks->unique('id');
        $photoIds = $homeworks->where('homework_type', 'topic_photo_practice')->pluck('id');
        $photoTasks = $photoIds->isEmpty() ? collect() : HomeworkTopicTask::query()
            ->whereIn('homework_id', $photoIds)
            ->orderBy('task_order')
            ->get(['id', 'homework_id', 'task_order', 'topic_number', 'task_payload'])
            ->groupBy('homework_id');
        $hashes = $homeworks->where('homework_type', 'full_variant')->pluck('variant_hash')->filter()->unique();
        $variants = $hashes->isEmpty() ? collect() : OgeVariant::query()
            ->whereIn('hash', $hashes)->get(['id', 'hash', 'exam_type', 'mode', 'config_json'])->keyBy('hash');

        $result = [];
        foreach ($homeworks as $homework) {
            $tasks = [];
            if ($homework->homework_type === 'topic_photo_practice') {
                foreach ($photoTasks->get($homework->id, collect()) as $task) {
                    $tasks[] = $this->condition($task->task_payload ?? [], $task->task_order, (string) $task->topic_number);
                }
            } elseif ($homework->homework_type === 'full_variant') {
                $variant = $variants->get($homework->variant_hash);
                $snapshot = $variant?->config_json['tasks'] ?? [];
                if (is_array($snapshot)) {
                    foreach (array_values($snapshot) as $i => $payload) {
                        if (is_array($payload)) {
                            $tasks[] = $this->condition($payload, $i + 1, (string) ($payload['topic_id'] ?? ''));
                        }
                    }
                }
            }
            $result[$homework->id] = $tasks;
        }

        return $result;
    }

    /** Only display fields leave this method, including in nested options/statements. */
    public function condition(array $payload, int $number, string $topic = ''): array
    {
        $inner = is_array($payload['task'] ?? null) ? $payload['task'] : [];
        $data = array_replace($inner, array_filter($payload, fn ($v) => $v !== null && $v !== ''));
        $topic = $this->first($data, ['topic_id']) ?: $topic;
        if (ctype_digit($topic)) {
            $topic = str_pad($topic, 2, '0', STR_PAD_LEFT);
        }
        $html = $this->first($data, ['text_html', 'html', 'condition_html']);
        $text = $html ?: $this->first($data, ['text', 'question']);
        // Full HTML is already the issued condition; do not append a flattened duplicate.
        $expression = $html !== '' ? '' : $this->first($data, ['expression']);
        if ($html === '' && $expression === '') {
            $left = $this->first($data, ['left']);
            $right = $this->first($data, ['right']);
            $expression = $left !== '' && $right !== ''
                ? $left . ' \\text{ и } ' . $right
                : $this->first($data, ['segment']);
        }
        $parts = TaskConditionFormatter::compose($this->first($data, ['instruction']), $text, $expression);
        $svg = $this->first($data, ['svg', 'image_svg']);
        $image = $this->first($data, ['image_url', 'image']);
        if ($svg === '' && str_starts_with(ltrim($image), '<svg')) {
            $svg = $image;
            $image = '';
        }
        if ($svg !== '' && str_contains($text, $svg)) {
            $svg = '';
        }
        $options = [];
        $rawOptions = $data['options'] ?? $data['graph_options'] ?? [];
        if (is_array($rawOptions)) {
            foreach (array_values($rawOptions) as $i => $option) {
                if (is_array($option)) {
                    $content = $this->first($option, ['html', 'text', 'expression', 'label', 'svg']);
                    $drawing = $this->first($option, ['svg']);
                    $options[] = [
                        'label' => $this->first($option, ['n', 'index', 'id']) ?: (string) ($i + 1),
                        'html' => $this->mathHtml($content) . ($drawing !== '' && !str_contains($content, $drawing) ? TaskConditionHtml::render($drawing) : ''),
                    ];
                } elseif (is_scalar($option)) {
                    $options[] = ['label' => (string) ($i + 1), 'html' => $this->mathHtml((string) $option)];
                }
            }
        }
        $statements = [];
        $rawStatements = $data['selected_statements'] ?? $data['statements'] ?? [];
        if (is_array($rawStatements)) {
            foreach (array_values($rawStatements) as $i => $statement) {
                $content = is_array($statement) ? $this->first($statement, ['text', 'html']) : (is_scalar($statement) ? (string) $statement : '');
                $statements[] = ['label' => $i + 1, 'html' => TaskConditionHtml::render($content)];
            }
        }

        return [
            'number' => $number,
            'instruction' => $parts['instruction'],
            'text_html' => TaskConditionHtml::render($parts['text']),
            'expression_html' => $this->mathHtml($parts['expression'], true),
            'svg_html' => $svg !== '' ? TaskConditionHtml::render($svg) : '',
            'image_url' => $this->imageUrl($image, $topic),
            'options' => $options,
            'statements' => $statements,
        ];
    }

    private function mathHtml(string $value, bool $mathField = false): string
    {
        // Do not wrap bank HTML or prose containing its own inline math. Cyrillic
        // inside LaTeX \text{...} is part of a formula, not evidence of prose.
        $outsideText = preg_replace('/\\\\text\{[^{}]*\}/u', '', $value) ?? $value;
        if ($value !== '' && !TaskConditionHtml::looksLikeMarkup($value)
            && !str_contains($value, '$') && !str_contains($value, '\\(') && !str_contains($value, '\\[')
            && !preg_match('/[а-яё]/iu', $outsideText)
            && ($mathField || preg_match('/\\\\[a-zA-Z]+|[=^_]/', $value))) {
            $value = '\\(' . $value . '\\)';
        }

        return TaskConditionHtml::render($value);
    }

    private function first(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_scalar($data[$key]) && trim((string) $data[$key]) !== '') {
                return trim((string) $data[$key]);
            }
        }

        return '';
    }

    private function imageUrl(string $image, string $topic): ?string
    {
        if ($image === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $image) || (str_starts_with($image, '/') && !str_starts_with($image, '//'))) {
            return $image;
        }
        if (str_contains($image, ':') || str_contains($image, '..') || $topic === '') {
            return null;
        }

        return '/images/tasks/' . rawurlencode($topic) . '/' . $image;
    }
}
