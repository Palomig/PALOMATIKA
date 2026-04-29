<?php

namespace App\Services;

use App\Models\PracticeGameRun;
use App\Models\User;
use InvalidArgumentException;

class PracticeGameService
{
    public function __construct(
        private readonly PracticeGraphRenderer $graphRenderer = new PracticeGraphRenderer(),
    ) {}

    public function startRun(User $user, string $slug): array
    {
        $this->getMiniGame($slug);

        $question = $this->generateQuestion($slug, 0);
        $run = PracticeGameRun::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'score' => 0,
            'started_at' => now(),
            'current_question' => [
                'correct_id' => $this->extractCorrectId($question),
                'task_type' => $question['task_type'] ?? null,
                'level' => $question['level'] ?? null,
            ],
        ]);

        return [
            'run' => $run,
            'question' => $this->stripAnswers($question),
        ];
    }

    public function submitAnswer(PracticeGameRun $run, string $optionId): array
    {
        if (!$run->isOpen()) {
            return [
                'status' => 'over',
                'score' => $run->score,
                'reason' => $run->end_reason,
            ];
        }

        $correctId = (string) ($run->current_question['correct_id'] ?? '');
        if ($correctId === '' || $optionId !== $correctId) {
            $run->forceFill([
                'end_reason' => PracticeGameRun::REASON_WRONG,
                'ended_at' => now(),
                'current_question' => null,
            ])->save();

            return [
                'status' => 'over',
                'score' => $run->score,
                'reason' => PracticeGameRun::REASON_WRONG,
            ];
        }

        $newScore = $run->score + 1;
        $next = $this->generateQuestion($run->slug, $newScore);

        $run->forceFill([
            'score' => $newScore,
            'current_question' => [
                'correct_id' => $this->extractCorrectId($next),
                'task_type' => $next['task_type'] ?? null,
                'level' => $next['level'] ?? null,
            ],
        ])->save();

        return [
            'status' => 'continue',
            'score' => $newScore,
            'question' => $this->stripAnswers($next),
        ];
    }

    public function markTimeout(PracticeGameRun $run): array
    {
        if ($run->isOpen()) {
            $run->forceFill([
                'end_reason' => PracticeGameRun::REASON_TIMEOUT,
                'ended_at' => now(),
                'current_question' => null,
            ])->save();
        }

        return [
            'status' => 'over',
            'score' => $run->score,
            'reason' => $run->end_reason ?? PracticeGameRun::REASON_TIMEOUT,
        ];
    }

    private function extractCorrectId(array $question): string
    {
        foreach ($question['options'] ?? [] as $option) {
            if (!empty($option['is_correct'])) {
                return (string) $option['id'];
            }
        }

        throw new InvalidArgumentException('Generated question has no correct option');
    }

    private function stripAnswers(array $question): array
    {
        $options = array_map(static function (array $option): array {
            unset($option['is_correct']);
            return $option;
        }, $question['options'] ?? []);

        $question['options'] = array_values($options);

        return $question;
    }

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

    public function allCategories(): array
    {
        return array_values(config('practice_games.categories', []));
    }

    public function getCategory(string $slug): array
    {
        $category = config("practice_games.categories.{$slug}");

        if (!is_array($category)) {
            throw new InvalidArgumentException("Unknown practice category [{$slug}]");
        }

        return $category;
    }

    public function gamesByCategory(string $slug): array
    {
        return array_values(array_filter(
            $this->allMiniGames(),
            static fn (array $game): bool => ($game['category'] ?? null) === $slug,
        ));
    }

    public function resolveLevel(string $slug, int $score): array
    {
        $game = $this->getMiniGame($slug);
        $levels = $game['levels'] ?? [];

        if (empty($levels)) {
            throw new InvalidArgumentException("Practice game [{$slug}] has no levels configured");
        }

        foreach ($levels as $level) {
            $from = (int) ($level['score_from'] ?? 0);
            $to = $level['score_to'];

            if ($score < $from) {
                continue;
            }

            if ($to === null || $score <= (int) $to) {
                return $level;
            }
        }

        return $levels[array_key_last($levels)];
    }

    public function generateQuestion(string $slug, int $score): array
    {
        $level = $this->resolveLevel($slug, $score);
        $taskTypes = $level['task_types'] ?? [];

        if (empty($taskTypes)) {
            throw new InvalidArgumentException(
                "Practice game [{$slug}] level [{$level['level']}] has no task types"
            );
        }

        $taskType = $taskTypes[array_rand($taskTypes)];
        $question = $this->generateQuestionByTaskType($taskType);
        $question['level'] = $level['level'];

        return $question;
    }

    public function generateQuestionByTaskType(string $taskType): array
    {
        $template = $this->buildTemplate($taskType);

        return [
            'task_type' => $taskType,
            'prompt' => $template['prompt'],
            'equation' => $template['equation'] ?? null,
            'graph' => $template['graph'] ?? null,
            'options' => $this->shuffleOptions($template['correct'], $template['wrong']),
        ];
    }

    private function buildTemplate(string $taskType): array
    {
        return match ($taskType) {
            'move_positive_term' => $this->templateMovePositiveTerm(),
            'move_negative_term' => $this->templateMoveNegativeTerm(),
            'move_negative_term_before_x' => $this->templateMoveNegativeTermBeforeX(),
            'move_positive_term_after_x' => $this->templateMovePositiveTermAfterX(),
            'move_positive_multiplier' => $this->templateMovePositiveMultiplier(),
            'move_negative_multiplier' => $this->templateMoveNegativeMultiplier(),
            'move_positive_multiplier_small_result' => $this->templateMovePositiveMultiplierSmallResult(),
            'move_negative_multiplier_small_result' => $this->templateMoveNegativeMultiplierSmallResult(),
            'move_negative_term_after_constant' => $this->templateMoveNegativeTermAfterConstant(),
            'linear_sign_k' => $this->templateLinearSignK(),
            'linear_sign_b' => $this->templateLinearSignB(),
            'parabola_sign_a' => $this->templateParabolaSignA(),
            'parabola_sign_c' => $this->templateParabolaSignC(),
            default => throw new InvalidArgumentException("Unknown practice task type [{$taskType}]"),
        };
    }

    private function templateMovePositiveTerm(): array
    {
        $term = random_int(2, 9);
        $result = random_int(5, 18);

        return [
            'prompt' => 'Какой шаг правильный?',
            'equation' => "x + {$term} = {$result}",
            'correct' => $this->textOption("x = {$result} - {$term}"),
            'wrong' => $this->textOption("x = {$result} + {$term}"),
        ];
    }

    private function templateMoveNegativeTerm(): array
    {
        $term = random_int(2, 9);
        $result = random_int(3, 18);

        return [
            'prompt' => 'Какой шаг правильный?',
            'equation' => "x - {$term} = {$result}",
            'correct' => $this->textOption("x = {$result} + {$term}"),
            'wrong' => $this->textOption("x = {$result} - {$term}"),
        ];
    }

    private function templateMoveNegativeTermBeforeX(): array
    {
        $term = random_int(2, 9);
        $coefficient = random_int(2, 9);
        $result = random_int(6, 20);

        return [
            'prompt' => 'Что получится после переноса слагаемого?',
            'equation' => "-{$term} + {$coefficient}x = {$result}",
            'correct' => $this->textOption("{$coefficient}x = {$result} + {$term}"),
            'wrong' => $this->textOption("{$coefficient}x = {$result} - {$term}"),
        ];
    }

    private function templateMovePositiveTermAfterX(): array
    {
        $term = random_int(2, 9);
        $coefficient = random_int(2, 9);
        $result = random_int(8, 21);

        return [
            'prompt' => 'Что получится после переноса слагаемого?',
            'equation' => "{$coefficient}x + {$term} = {$result}",
            'correct' => $this->textOption("{$coefficient}x = {$result} - {$term}"),
            'wrong' => $this->textOption("{$coefficient}x = {$result} + {$term}"),
        ];
    }

    private function templateMovePositiveMultiplier(): array
    {
        $coefficient = random_int(2, 9);
        $result = random_int(8, 36);

        return [
            'prompt' => 'Как перенести множитель?',
            'equation' => "{$coefficient}x = {$result}",
            'correct' => $this->fractionOption('x = ', (string) $result, (string) $coefficient),
            'wrong' => $this->textOption("x = {$result} - {$coefficient}"),
        ];
    }

    private function templateMoveNegativeMultiplier(): array
    {
        $coefficient = random_int(2, 9);
        $result = random_int(8, 36);

        return [
            'prompt' => 'Как перенести отрицательный множитель?',
            'equation' => "-{$coefficient}x = {$result}",
            'correct' => $this->fractionOption('x = ', (string) $result, "-{$coefficient}"),
            'wrong' => $this->textOption("x = {$result} + {$coefficient}"),
        ];
    }

    private function templateMovePositiveMultiplierSmallResult(): array
    {
        $coefficient = random_int(4, 12);
        $result = random_int(1, 3);

        return [
            'prompt' => 'Как перенести множитель?',
            'equation' => "{$coefficient}x = {$result}",
            'correct' => $this->fractionOption('x = ', (string) $result, (string) $coefficient),
            'wrong' => $this->fractionOption('x = ', (string) $coefficient, (string) $result),
        ];
    }

    private function templateMoveNegativeMultiplierSmallResult(): array
    {
        $coefficient = random_int(4, 12);
        $result = random_int(1, 3);

        return [
            'prompt' => 'Как перенести отрицательный множитель?',
            'equation' => "-{$coefficient}x = {$result}",
            'correct' => $this->fractionOption('x = ', (string) $result, "-{$coefficient}"),
            'wrong' => $this->fractionOption('x = ', "-{$coefficient}", (string) $result),
        ];
    }

    private function templateMoveNegativeTermAfterConstant(): array
    {
        $constant = random_int(6, 12);
        $coefficient = random_int(2, 7);
        $result = random_int(1, 5);

        return [
            'prompt' => 'Какой следующий шаг верный?',
            'equation' => "{$constant} - {$coefficient}x = {$result}",
            'correct' => $this->textOption("-{$coefficient}x = {$result} - {$constant}"),
            'wrong' => $this->textOption("-{$coefficient}x = {$result} + {$constant}"),
        ];
    }

    private function templateLinearSignK(): array
    {
        $kAbs = (float) $this->pickOne([1, 2]);
        $k = (random_int(0, 1) === 1) ? $kAbs : -$kAbs;
        $b = (float) $this->pickOne([-3, -2, -1, 1, 2, 3]);

        return [
            'prompt' => 'Какой знак коэффициента k в функции y = kx + b?',
            'graph' => $this->graphRenderer->linear($k, (float) $b),
            'correct' => $this->textOption($k > 0 ? 'k > 0' : 'k < 0'),
            'wrong' => $this->textOption($k > 0 ? 'k < 0' : 'k > 0'),
        ];
    }

    private function templateLinearSignB(): array
    {
        $kAbs = (float) $this->pickOne([1, 2]);
        $k = (random_int(0, 1) === 1) ? $kAbs : -$kAbs;
        $b = (float) $this->pickOne([-3, -2, -1, 1, 2, 3]);

        return [
            'prompt' => 'Какой знак коэффициента b в функции y = kx + b?',
            'graph' => $this->graphRenderer->linear($k, (float) $b),
            'correct' => $this->textOption($b > 0 ? 'b > 0' : 'b < 0'),
            'wrong' => $this->textOption($b > 0 ? 'b < 0' : 'b > 0'),
        ];
    }

    private function templateParabolaSignA(): array
    {
        $aAbs = (float) $this->pickOne([0.5, 1.0]);
        $a = (random_int(0, 1) === 1) ? $aAbs : -$aAbs;
        $c = (float) $this->pickOne([-2, -1, 0, 1, 2]);

        return [
            'prompt' => 'Какой знак коэффициента a в функции y = ax² + c?',
            'graph' => $this->graphRenderer->parabola($a, $c),
            'correct' => $this->textOption($a > 0 ? 'a > 0' : 'a < 0'),
            'wrong' => $this->textOption($a > 0 ? 'a < 0' : 'a > 0'),
        ];
    }

    private function templateParabolaSignC(): array
    {
        $aAbs = (float) $this->pickOne([0.5, 1.0]);
        $a = (random_int(0, 1) === 1) ? $aAbs : -$aAbs;
        $c = (float) $this->pickOne([-3, -2, -1, 1, 2, 3]);

        return [
            'prompt' => 'Какой знак коэффициента c в функции y = ax² + c?',
            'graph' => $this->graphRenderer->parabola($a, $c),
            'correct' => $this->textOption($c > 0 ? 'c > 0' : 'c < 0'),
            'wrong' => $this->textOption($c > 0 ? 'c < 0' : 'c > 0'),
        ];
    }

    private function pickOne(array $values): mixed
    {
        return $values[array_rand($values)];
    }

    private function textOption(string $label): array
    {
        return ['label' => $label];
    }

    private function fractionOption(string $prefix, string $numerator, string $denominator): array
    {
        return [
            'label' => "{$prefix}{$numerator} / {$denominator}",
            'fraction' => [
                'prefix' => $prefix,
                'numerator' => $numerator,
                'denominator' => $denominator,
            ],
        ];
    }

    private function shuffleOptions(array $correct, array $wrong): array
    {
        $options = [
            array_merge(['id' => 'a', 'is_correct' => true], $correct),
            array_merge(['id' => 'b', 'is_correct' => false], $wrong),
        ];

        shuffle($options);

        return array_values($options);
    }
}
