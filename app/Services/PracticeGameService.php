<?php

namespace App\Services;

use InvalidArgumentException;

class PracticeGameService
{
    public function allMiniGames(): array
    {
        return array_values(config('practice_games.mini_games', []));
    }

    public function getMiniGame(string $slug): array
    {
        $game = config("practice_games.mini_games.{$slug}");

        if (!is_array($game)) {
            throw new InvalidArgumentException("Unknown practice game [{$slug}]");
        }

        return $game;
    }

    public function resolveLevel(string $slug, int $score): array
    {
        $game = $this->getMiniGame($slug);

        foreach ($game['levels'] as $level) {
            $from = (int) ($level['score_from'] ?? 0);
            $to = $level['score_to'];

            if ($score < $from) {
                continue;
            }

            if ($to === null || $score <= (int) $to) {
                return $level;
            }
        }

        return end($game['levels']);
    }

    public function generateQuestion(string $slug, int $score): array
    {
        $level = $this->resolveLevel($slug, $score);
        $taskTypes = $level['task_types'] ?? [];
        $taskType = $taskTypes[array_rand($taskTypes)];

        $question = $this->generateQuestionByTaskType($slug, $taskType);
        $question['level'] = $level['level'];

        return $question;
    }

    public function generateQuestionByTaskType(string $slug, string $taskType): array
    {
        $this->getMiniGame($slug);

        return match ($taskType) {
            'move_positive_term' => $this->movePositiveTerm(),
            'move_negative_term' => $this->moveNegativeTerm(),
            'move_negative_term_before_x' => $this->moveNegativeTermBeforeX(),
            'move_positive_term_after_x' => $this->movePositiveTermAfterX(),
            'move_positive_multiplier' => $this->movePositiveMultiplier(),
            'move_negative_multiplier' => $this->moveNegativeMultiplier(),
            'move_negative_term_after_constant' => $this->moveNegativeTermAfterConstant(),
            default => throw new InvalidArgumentException("Unknown practice task type [{$taskType}]"),
        };
    }

    private function movePositiveTerm(): array
    {
        $term = random_int(2, 9);
        $result = random_int(5, 18);

        return $this->buildQuestion(
            'move_positive_term',
            'Какой шаг правильный?',
            "x + {$term} = {$result}",
            "x = {$result} - {$term}",
            "x = {$result} + {$term}"
        );
    }

    private function moveNegativeTerm(): array
    {
        $term = random_int(2, 9);
        $result = random_int(3, 18);

        return $this->buildQuestion(
            'move_negative_term',
            'Какой шаг правильный?',
            "x - {$term} = {$result}",
            "x = {$result} + {$term}",
            "x = {$result} - {$term}"
        );
    }

    private function moveNegativeTermBeforeX(): array
    {
        $term = random_int(2, 9);
        $coefficient = random_int(2, 9);
        $result = random_int(6, 20);

        return $this->buildQuestion(
            'move_negative_term_before_x',
            'Что получится после переноса слагаемого?',
            "-{$term} + {$coefficient}x = {$result}",
            "{$coefficient}x = {$result} + {$term}",
            "{$coefficient}x = {$result} - {$term}"
        );
    }

    private function movePositiveTermAfterX(): array
    {
        $term = random_int(2, 9);
        $coefficient = random_int(2, 9);
        $result = random_int(8, 21);

        return $this->buildQuestion(
            'move_positive_term_after_x',
            'Что получится после переноса слагаемого?',
            "{$coefficient}x + {$term} = {$result}",
            "{$coefficient}x = {$result} - {$term}",
            "{$coefficient}x = {$result} + {$term}"
        );
    }

    private function movePositiveMultiplier(): array
    {
        $coefficient = random_int(2, 9);
        $result = random_int(8, 36);

        return $this->buildQuestion(
            'move_positive_multiplier',
            'Как перенести множитель?',
            "{$coefficient}x = {$result}",
            "x = {$result} / {$coefficient}",
            "x = {$result} - {$coefficient}"
        );
    }

    private function moveNegativeMultiplier(): array
    {
        $coefficient = random_int(2, 9);
        $result = random_int(8, 36);

        return $this->buildQuestion(
            'move_negative_multiplier',
            'Как перенести отрицательный множитель?',
            "-{$coefficient}x = {$result}",
            "x = {$result} / -{$coefficient}",
            "x = {$result} + {$coefficient}"
        );
    }

    private function moveNegativeTermAfterConstant(): array
    {
        $constant = random_int(6, 12);
        $coefficient = random_int(2, 7);
        $result = random_int(1, 5);

        return $this->buildQuestion(
            'move_negative_term_after_constant',
            'Какой следующий шаг верный?',
            "{$constant} - {$coefficient}x = {$result}",
            "-{$coefficient}x = {$result} - {$constant}",
            "-{$coefficient}x = {$result} + {$constant}"
        );
    }

    private function buildQuestion(
        string $taskType,
        string $prompt,
        string $equation,
        string $correctLabel,
        string $wrongLabel
    ): array {
        $options = [
            ['id' => 'a', 'label' => $correctLabel, 'is_correct' => true],
            ['id' => 'b', 'label' => $wrongLabel, 'is_correct' => false],
        ];

        shuffle($options);

        return [
            'task_type' => $taskType,
            'prompt' => $prompt,
            'equation' => $equation,
            'options' => array_values($options),
        ];
    }
}
