<?php

namespace Tests\Feature\Support;

trait ResolvesTopicOptionAnswer
{
    /**
     * @param array<int, mixed> $options
     */
    private function resolveSelectedOption(array $task, array $options): ?array
    {
        $answerRaw = trim((string) ($task['answer'] ?? ''));
        if ($answerRaw === '') {
            return null;
        }

        if (preg_match('/^[1-9][0-9]*$/', $answerRaw)) {
            $idx = ((int) $answerRaw) - 1;
            if (!array_key_exists($idx, $options)) {
                return null;
            }
            $opt = $options[$idx];
            return [
                'id' => is_array($opt) ? (string) ($opt['id'] ?? '') : '',
                'text' => is_array($opt)
                    ? (string) ($opt['label'] ?? $opt['text'] ?? $opt['value'] ?? '')
                    : (string) $opt,
                'raw' => $opt,
            ];
        }

        foreach ($options as $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $id = (string) ($opt['id'] ?? $opt['option_id'] ?? '');
            if ($id !== '' && $id === $answerRaw) {
                return [
                    'id' => $id,
                    'text' => (string) ($opt['label'] ?? $opt['text'] ?? $opt['value'] ?? ''),
                    'raw' => $opt,
                ];
            }
        }

        return null;
    }
}
