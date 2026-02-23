<?php

namespace App\Services;

class OptionRenderModePolicy
{
    public const MODE_TEXT = 'text_options';

    public const MODE_VISUAL = 'visual_options';

    /**
     * Types where answer options are rendered and explicit modality matters.
     */
    private const OPTION_TYPES = [
        'choice',
        'simple_choice',
        'fraction_choice',
        'interval_choice',
        'between_fractions',
        'segment_choice',
        'fraction_options',
        'decimal_choice',
        'sqrt_choice',
        'sqrt_interval',
        'sqrt_segment',
        'sqrt_options',
        'comparison',
        'power_choice',
        'compare_fractions',
        'false_statements',
        'ordering',
        'point_value',
        'fraction_point',
        'count_integers',
        'negative_segment',
        'negative_interval',
    ];

    public function normalizeTopicData(string $topicId, array $data): array
    {
        if (!isset($data['blocks']) || !is_array($data['blocks'])) {
            return $data;
        }

        foreach ($data['blocks'] as $blockIndex => $block) {
            if (!is_array($block) || !isset($block['zadaniya']) || !is_array($block['zadaniya'])) {
                continue;
            }

            foreach ($block['zadaniya'] as $zadanieIndex => $zadanie) {
                if (!is_array($zadanie)) {
                    continue;
                }

                $data['blocks'][$blockIndex]['zadaniya'][$zadanieIndex] = $this->normalizeZadanie($topicId, $zadanie);
            }
        }

        return $data;
    }

    public function normalizeZadanie(string $topicId, array $zadanie): array
    {
        $zadanieMode = $this->sanitizeMode($zadanie['options_render_mode'] ?? null);

        if ($zadanieMode === null && $this->shouldDefaultToExplicitTextMode($topicId, $zadanie)) {
            $zadanieMode = self::MODE_TEXT;
            $zadanie['options_render_mode'] = $zadanieMode;
        } elseif ($zadanieMode !== null) {
            $zadanie['options_render_mode'] = $zadanieMode;
        }

        if (!isset($zadanie['tasks']) || !is_array($zadanie['tasks'])) {
            return $zadanie;
        }

        foreach ($zadanie['tasks'] as $taskIndex => $task) {
            if (!is_array($task)) {
                continue;
            }

            $taskMode = $this->sanitizeMode($task['options_render_mode'] ?? null);

            if ($taskMode !== null) {
                $task['options_render_mode'] = $taskMode;
            } elseif ($this->taskHasOwnOptions($task) && $zadanieMode !== null) {
                // Standardize explicit modality at task level for downstream renderers.
                $task['options_render_mode'] = $zadanieMode;
            }

            $zadanie['tasks'][$taskIndex] = $task;
        }

        return $zadanie;
    }

    public function shouldRenderIntervalSvg(
        ?string $topicId,
        ?string $taskMode,
        ?string $zadanieMode,
        array $options
    ): bool {
        $effectiveMode = $this->sanitizeMode($taskMode) ?? $this->sanitizeMode($zadanieMode);

        if ($effectiveMode === self::MODE_TEXT) {
            return false;
        }

        if ($effectiveMode === self::MODE_VISUAL) {
            return true;
        }

        // Legacy fallback for topics outside the explicit policy rollout.
        if (in_array((string) $topicId, ['07', '13'], true)) {
            return false;
        }

        return $this->allOptionsAreIntervals($options);
    }

    public function allOptionsAreIntervals(array $options): bool
    {
        if ($options === []) {
            return false;
        }

        foreach ($options as $option) {
            $value = is_scalar($option) ? trim((string) $option) : '';

            if ($value === '') {
                return false;
            }

            if (in_array($value, ['нет решений', '(-∞; +∞)'], true)) {
                continue;
            }

            if (!$this->isIntervalOption($value)) {
                return false;
            }
        }

        return true;
    }

    public function isIntervalOption(string $option): bool
    {
        $option = trim($option);

        if ($option === '') {
            return false;
        }

        if (preg_match('/^[\(\[].*?;.*?[\)\]]$/u', $option) === 1) {
            return true;
        }

        return str_contains($option, '∪');
    }

    private function sanitizeMode(mixed $mode): ?string
    {
        if (!is_string($mode) || $mode === '') {
            return null;
        }

        return in_array($mode, [self::MODE_TEXT, self::MODE_VISUAL], true) ? $mode : null;
    }

    private function shouldDefaultToExplicitTextMode(string $topicId, array $zadanie): bool
    {
        if (!in_array($topicId, ['07', '13'], true)) {
            return false;
        }

        $type = (string) ($zadanie['type'] ?? '');

        if (!in_array($type, self::OPTION_TYPES, true)) {
            return false;
        }

        if (!empty($zadanie['options']) && is_array($zadanie['options'])) {
            return true;
        }

        foreach (($zadanie['tasks'] ?? []) as $task) {
            if ($this->taskHasOwnOptions($task)) {
                return true;
            }
        }

        return false;
    }

    private function taskHasOwnOptions(mixed $task): bool
    {
        return is_array($task) && !empty($task['options']) && is_array($task['options']);
    }
}
