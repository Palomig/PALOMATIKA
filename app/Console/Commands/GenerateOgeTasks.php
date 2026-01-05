<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskStep;
use App\Models\StepBlock;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateOgeTasks extends Command
{
    protected $signature = 'oge:generate-tasks
                            {--topic= : Generate tasks for specific OGE number (06, 07, etc.)}
                            {--count=100 : Number of tasks to generate per topic}
                            {--clear : Clear existing generated tasks first}';

    protected $description = 'Generate OGE math tasks with puzzles algorithmically';

    public function handle(): int
    {
        $this->info('=== OGE Task Generator ===');
        $this->newLine();

        if ($this->option('clear')) {
            $this->clearGeneratedTasks();
        }

        $ogeNumber = $this->option('topic');
        $count = (int) $this->option('count');

        if ($ogeNumber) {
            $this->generateForTopic($ogeNumber, $count);
        } else {
            // Generate for all topics
            $topics = ['06']; // Start with task 6, can add more later
            foreach ($topics as $num) {
                $this->generateForTopic($num, $count);
            }
        }

        $this->newLine();
        $this->info('Done!');

        return Command::SUCCESS;
    }

    private function clearGeneratedTasks(): void
    {
        $this->info('Clearing existing generated tasks...');

        $tasks = Task::where('external_id', 'like', 'gen_%')->get();

        foreach ($tasks as $task) {
            $task->steps()->each(function ($step) {
                $step->blocks()->delete();
                $step->delete();
            });
            $task->delete();
        }

        $this->info("Cleared {$tasks->count()} generated tasks.");
    }

    private function generateForTopic(string $ogeNumber, int $count): void
    {
        $this->info("Generating tasks for OGE #{$ogeNumber}...");

        $topic = Topic::where('oge_number', $ogeNumber)->first();

        if (!$topic) {
            $this->error("Topic with oge_number {$ogeNumber} not found!");
            return;
        }

        $generator = match ($ogeNumber) {
            '06' => fn() => $this->generateTask06(),
            '07' => fn() => $this->generateTask07(),
            '08' => fn() => $this->generateTask08(),
            '10' => fn() => $this->generateTask10(),
            default => null,
        };

        if (!$generator) {
            $this->warn("No generator for topic {$ogeNumber} yet.");
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $taskData = $generator();
            $this->createTask($topic, $taskData, $i);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created {$count} tasks for topic #{$ogeNumber}");
    }

    private function createTask(Topic $topic, array $data, int $index): void
    {
        DB::transaction(function () use ($topic, $data, $index) {
            $task = Task::create([
                'topic_id' => $topic->id,
                'external_id' => 'gen_' . $topic->oge_number . '_' . time() . '_' . $index,
                'text' => $data['text'],
                'text_html' => $data['text_html'],
                'correct_answer' => $data['answer'],
                'answer_type' => $data['answer_type'] ?? 'number',
                'difficulty' => $data['difficulty'] ?? 2,
                'is_active' => true,
            ]);

            foreach ($data['steps'] as $stepIndex => $stepData) {
                $step = TaskStep::create([
                    'task_id' => $task->id,
                    'step_number' => $stepIndex + 1,
                    'instruction' => $stepData['instruction'],
                    'template' => $stepData['template'],
                    'correct_answers' => $stepData['correct_answers'],
                ]);

                $sortOrder = 1;
                foreach ($stepData['blocks'] as $block) {
                    StepBlock::create([
                        'task_step_id' => $step->id,
                        'content' => $block['content'],
                        'is_correct' => $block['is_correct'] ?? false,
                        'is_trap' => $block['is_trap'] ?? false,
                        'trap_explanation' => $block['trap_explanation'] ?? null,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        });
    }

    /**
     * Task 06: Fractions and powers
     * Multiple complexity levels
     */
    private function generateTask06(): array
    {
        $type = rand(0, 8);

        return match ($type) {
            0 => $this->generateFractionMultiply(),
            1 => $this->generateFractionDivide(),
            2 => $this->generateFractionAddSubtract(),
            3 => $this->generateBracketExpression(),      // (a/b ± c/d) · e/f
            4 => $this->generateMixedNumberExpression(),  // (m n/p ± k) · q
            5 => $this->generateComplexBracket(),         // (a/b ± c/d) · e/f with harder numbers
            6 => $this->generatePowerExpression(),        // a·(1/n)² - b·(1/n)
            7 => $this->generateDecimalExpression(),      // 2.1/(6.6-2.4)
            8 => $this->generateDivisionByBracket(),      // 0.9/(1 + 1/5)
            default => $this->generateFractionMultiply(),
        };
    }

    /**
     * (a/b ± c/d) · e/f - bracket expressions
     */
    private function generateBracketExpression(): array
    {
        // Pick compatible denominators
        $configs = [
            ['commonDen' => 10, 'b' => 2, 'd' => 5],
            ['commonDen' => 10, 'b' => 5, 'd' => 2],
            ['commonDen' => 12, 'b' => 3, 'd' => 4],
            ['commonDen' => 12, 'b' => 4, 'd' => 6],
            ['commonDen' => 15, 'b' => 3, 'd' => 5],
            ['commonDen' => 20, 'b' => 4, 'd' => 5],
            ['commonDen' => 20, 'b' => 5, 'd' => 10],
            ['commonDen' => 24, 'b' => 4, 'd' => 6],
            ['commonDen' => 30, 'b' => 5, 'd' => 6],
        ];

        $config = $configs[array_rand($configs)];
        $commonDen = $config['commonDen'];
        $b = $config['b'];
        $d = $config['d'];

        $a = rand(1, $b * 2);
        $c = rand(1, $d * 2);

        $isAdd = rand(0, 1) === 1;

        // Calculate bracket result
        $mult1 = $commonDen / $b;
        $mult2 = $commonDen / $d;
        $bracketNum = $isAdd ? ($a * $mult1 + $c * $mult2) : ($a * $mult1 - $c * $mult2);

        // Make sure positive
        if ($bracketNum <= 0) {
            [$a, $b, $c, $d] = [$c, $d, $a, $b];
            $bracketNum = abs($bracketNum);
            if ($bracketNum === 0) $bracketNum = rand(1, 10);
        }

        // Pick multiplier that simplifies with commonDen
        $gcdBracket = $this->gcd($bracketNum, $commonDen);
        $bracketSimplifiedNum = $bracketNum / $gcdBracket;
        $bracketSimplifiedDen = $commonDen / $gcdBracket;

        // Multiplier fraction
        $f = $bracketSimplifiedDen; // This will cancel nicely
        $e = rand(1, 5);

        $finalNum = $bracketSimplifiedNum * $e;
        $finalDen = 1; // f cancels with bracketSimplifiedDen

        // Simplify final
        if ($finalDen === 1) {
            $answer = (string) $finalNum;
        } else {
            $gcd = $this->gcd($finalNum, $finalDen);
            $answer = ($finalDen / $gcd === 1)
                ? (string) ($finalNum / $gcd)
                : ($finalNum / $gcd) . '/' . ($finalDen / $gcd);
        }

        $op = $isAdd ? '+' : '-';
        $expressionText = "({$a}/{$b} {$op} {$c}/{$d}) · {$e}/{$f}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\left(\\frac{{$a}}{{$b}} {$op} \\frac{{$c}}{{$d}}\\right) \\cdot \\frac{{$e}}{{$f}}</span>",
            'answer' => $answer,
            'answer_type' => 'number',
            'difficulty' => 3,
            'steps' => [
                [
                    'instruction' => 'Вычислите выражение в скобках (приведите к общему знаменателю)',
                    'template' => "\\frac{{$a}}{{$b}} {$op} \\frac{{$c}}{{$d}} = \\frac{[___]}{[___]}",
                    'correct_answers' => [(string) $bracketNum, (string) $commonDen],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $bracketNum, 'is_correct' => true],
                        ['content' => (string) $commonDen, 'is_correct' => true],
                        ['content' => (string) ($bracketNum + rand(1, 5)), 'is_trap' => true],
                        ['content' => (string) ($commonDen * 2), 'is_trap' => true, 'trap_explanation' => 'Знаменатель удвоился — проверьте приведение'],
                        ['content' => (string) abs($a * $mult1 - $c * $mult2 + rand(1, 3)), 'is_trap' => true],
                    ]),
                ],
                [
                    'instruction' => 'Умножьте на дробь',
                    'template' => "\\frac{{$bracketNum}}{{$commonDen}} \\cdot \\frac{{$e}}{{$f}} = [___]",
                    'correct_answers' => [$answer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => $answer, 'is_correct' => true],
                        ['content' => (string) ($finalNum + 1), 'is_trap' => true],
                        ['content' => (string) max(1, $finalNum - 1), 'is_trap' => true],
                        ['content' => (string) ($e + $f), 'is_trap' => true],
                    ]),
                ],
            ],
        ];
    }

    /**
     * Mixed number expressions: (9/16 + 2 3/8) · 4
     */
    private function generateMixedNumberExpression(): array
    {
        // Generate a mixed number m n/p
        $whole = rand(1, 5);           // Whole part: 1-5
        $p = [2, 3, 4, 5, 6, 8][rand(0, 5)];  // Denominator
        $n = rand(1, $p - 1);          // Numerator < denominator

        // Convert to improper fraction: (whole * p + n) / p
        $mixedNum = $whole * $p + $n;
        $mixedDen = $p;

        // First fraction a/b with same or related denominator
        $b = $p * rand(1, 2);
        $a = rand(1, $b - 1);

        $isAdd = rand(0, 1) === 1;

        // Common denominator
        $commonDen = $this->lcm($b, $mixedDen);
        $mult1 = $commonDen / $b;
        $mult2 = $commonDen / $mixedDen;

        $num1 = $a * $mult1;
        $num2 = $mixedNum * $mult2;

        $bracketNum = $isAdd ? ($num1 + $num2) : abs($num1 - $num2);
        if (!$isAdd && $num1 < $num2) {
            // Swap so we get positive result
            $bracketNum = $num2 - $num1;
        }

        // Multiplier (often whole number for cleaner answer)
        $multiplier = rand(2, 6);

        // Choose multiplier that simplifies nicely
        $gcd = $this->gcd($bracketNum, $commonDen);
        $simplifiedNum = $bracketNum / $gcd;
        $simplifiedDen = $commonDen / $gcd;

        // Final answer
        $finalNum = $simplifiedNum * $multiplier;
        $finalDen = $simplifiedDen;

        $gcdFinal = $this->gcd($finalNum, $finalDen);
        $finalNum = $finalNum / $gcdFinal;
        $finalDen = $finalDen / $gcdFinal;

        if ($finalDen === 1) {
            $answer = (string) $finalNum;
        } else {
            $answer = "{$finalNum}/{$finalDen}";
        }

        $op = $isAdd ? '+' : '-';
        $mixedStr = "{$whole} {$n}/{$p}";  // e.g., "2 3/8"
        $expressionText = "({$a}/{$b} {$op} {$mixedStr}) · {$multiplier}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\left(\\frac{{$a}}{{$b}} {$op} {$whole}\\frac{{$n}}{{$p}}\\right) \\cdot {$multiplier}</span>",
            'answer' => $answer,
            'answer_type' => 'number',
            'difficulty' => 3,
            'steps' => [
                [
                    'instruction' => "Переведите смешанное число {$whole} {$n}/{$p} в неправильную дробь",
                    'template' => "{$whole}\\frac{{$n}}{{$p}} = \\frac{[___]}{{$p}}",
                    'correct_answers' => [(string) $mixedNum],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $mixedNum, 'is_correct' => true],
                        ['content' => (string) ($whole + $n), 'is_trap' => true, 'trap_explanation' => 'Нужно умножить целую часть на знаменатель и прибавить числитель'],
                        ['content' => (string) ($whole * $n), 'is_trap' => true, 'trap_explanation' => 'Умножать нужно на знаменатель, не на числитель'],
                        ['content' => (string) ($mixedNum + $p), 'is_trap' => true],
                    ]),
                ],
                [
                    'instruction' => 'Вычислите скобки и умножьте',
                    'template' => "Ответ = [___]",
                    'correct_answers' => [$answer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => $answer, 'is_correct' => true],
                        ['content' => (string) (intval($answer) + rand(1, 3)), 'is_trap' => true],
                        ['content' => (string) max(1, intval($answer) - rand(1, 2)), 'is_trap' => true],
                        ['content' => (string) ($multiplier * $whole), 'is_trap' => true],
                    ]),
                ],
            ],
        ];
    }

    /**
     * More complex bracket: (a/b ± c/d) · e/f with larger numbers
     */
    private function generateComplexBracket(): array
    {
        // Like (17/10 - 1/20) · 2/15

        // Pick denominators with common factors
        $bases = [5, 10, 15, 20, 25, 26, 30];
        $b = $bases[array_rand($bases)];
        $d = $bases[array_rand($bases)];

        $a = rand(1, min(20, $b + 10));
        $c = rand(1, min(10, $d));

        $isAdd = rand(0, 1) === 1;
        $op = $isAdd ? '+' : '-';

        // Calculate
        $commonDen = $this->lcm($b, $d);
        $num1 = $a * ($commonDen / $b);
        $num2 = $c * ($commonDen / $d);

        $bracketNum = $isAdd ? $num1 + $num2 : $num1 - $num2;

        if ($bracketNum <= 0) {
            // Swap
            [$a, $b, $c, $d] = [$c, $d, $a, $b];
            $bracketNum = abs($bracketNum);
            if ($bracketNum === 0) return $this->generateBracketExpression(); // Retry
        }

        // Pick nice multiplier
        $gcd = $this->gcd($bracketNum, $commonDen);
        $simpNum = $bracketNum / $gcd;
        $simpDen = $commonDen / $gcd;

        // e/f should partially cancel
        $e = rand(1, 5);
        $f = $simpDen * rand(1, 3);

        $finalNum = $simpNum * $e;
        $finalDen = $f / $this->gcd($simpDen, $f) * $simpDen / $this->gcd($simpDen, $f);

        // Recalculate properly
        $finalNum = $bracketNum * $e;
        $finalDen = $commonDen * $f;
        $gcdFinal = $this->gcd($finalNum, $finalDen);
        $finalNum /= $gcdFinal;
        $finalDen /= $gcdFinal;

        if ($finalDen === 1) {
            $answer = (string) (int) $finalNum;
        } else {
            $answer = ((int) $finalNum) . '/' . ((int) $finalDen);
        }

        $expressionText = "({$a}/{$b} {$op} {$c}/{$d}) · {$e}/{$f}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\left(\\frac{{$a}}{{$b}} {$op} \\frac{{$c}}{{$d}}\\right) \\cdot \\frac{{$e}}{{$f}}</span>",
            'answer' => $answer,
            'answer_type' => 'number',
            'difficulty' => 4,
            'steps' => [
                [
                    'instruction' => 'Приведите дроби в скобках к общему знаменателю',
                    'template' => "\\frac{{$a}}{{$b}} {$op} \\frac{{$c}}{{$d}} = \\frac{[___]}{{$commonDen}}",
                    'correct_answers' => [(string) $bracketNum],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $bracketNum, 'is_correct' => true],
                        ['content' => (string) ($bracketNum + rand(1, 10)), 'is_trap' => true],
                        ['content' => (string) max(1, $bracketNum - rand(1, 5)), 'is_trap' => true],
                        ['content' => (string) ($a + $c), 'is_trap' => true, 'trap_explanation' => 'Нельзя складывать числители напрямую без общего знаменателя'],
                    ]),
                ],
                [
                    'instruction' => 'Умножьте результат на дробь и сократите',
                    'template' => "\\frac{{$bracketNum}}{{$commonDen}} \\cdot \\frac{{$e}}{{$f}} = [___]",
                    'correct_answers' => [$answer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => $answer, 'is_correct' => true],
                        ['content' => (string) ($bracketNum * $e), 'is_trap' => true, 'trap_explanation' => 'Не забудьте разделить на знаменатели'],
                        ['content' => strval(intval($finalNum) + 1) . ($finalDen > 1 ? "/{$finalDen}" : ''), 'is_trap' => true],
                    ]),
                ],
            ],
        ];
    }

    private function lcm(int $a, int $b): int
    {
        return abs($a * $b) / $this->gcd($a, $b);
    }

    private function shuffleBlocks(array $blocks): array
    {
        shuffle($blocks);
        return $blocks;
    }

    /**
     * Задание 3: a·(1/n)² - b·(1/n) - power expressions
     * Example: 10·(1/5)² - 12·(1/5)
     */
    private function generatePowerExpression(): array
    {
        $n = [3, 4, 5, 6, 7, 8, 9][rand(0, 6)];

        // a·(1/n)² ± b·(1/n) = a/n² ± b/n = (a ± b·n) / n²
        $a = rand(5, 25);
        $b = rand(5, 20);

        $isSubtract = rand(0, 1) === 1;
        $op = $isSubtract ? '-' : '+';

        // Result: a/n² ± b/n = a/n² ± (b·n)/n² = (a ± b·n) / n²
        $nSquared = $n * $n;
        $term1 = $a;           // a/n² -> numerator a
        $term2 = $b * $n;      // b/n = b·n/n² -> numerator b·n

        $resultNum = $isSubtract ? $term1 - $term2 : $term1 + $term2;
        $resultDen = $nSquared;

        // Handle negative results
        $isNegative = $resultNum < 0;
        $resultNum = abs($resultNum);

        $gcd = $this->gcd($resultNum, $resultDen);
        $finalNum = $resultNum / $gcd;
        $finalDen = $resultDen / $gcd;

        if ($finalDen === 1) {
            $answer = ($isNegative ? '-' : '') . (string) $finalNum;
        } else {
            $answer = ($isNegative ? '-' : '') . "{$finalNum}/{$finalDen}";
        }

        // Decimal answer
        $decimalAnswer = round(($isNegative ? -1 : 1) * $finalNum / $finalDen, 2);

        $expressionText = "{$a}·(1/{$n})² {$op} {$b}·(1/{$n})";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">{$a} \\cdot \\left(\\frac{1}{{$n}}\\right)^2 {$op} {$b} \\cdot \\frac{1}{{$n}}</span>",
            'answer' => (string) $decimalAnswer,
            'answer_type' => 'number',
            'difficulty' => 3,
            'steps' => [
                [
                    'instruction' => "Вычислите (1/{$n})² = 1/{$nSquared}",
                    'template' => "\\left(\\frac{1}{{$n}}\\right)^2 = \\frac{1}{[___]}",
                    'correct_answers' => [(string) $nSquared],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $nSquared, 'is_correct' => true],
                        ['content' => (string) ($n * 2), 'is_trap' => true, 'trap_explanation' => 'При возведении в квадрат нужно умножить на себя, а не на 2'],
                        ['content' => (string) $n, 'is_trap' => true],
                        ['content' => (string) ($nSquared + $n), 'is_trap' => true],
                    ]),
                ],
                [
                    'instruction' => 'Подставьте и вычислите',
                    'template' => "{$a} \\cdot \\frac{1}{{$nSquared}} {$op} {$b} \\cdot \\frac{1}{{$n}} = [___]",
                    'correct_answers' => [(string) $decimalAnswer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $decimalAnswer, 'is_correct' => true],
                        ['content' => (string) ($decimalAnswer + 0.1), 'is_trap' => true],
                        ['content' => (string) abs($decimalAnswer - 0.2), 'is_trap' => true],
                        ['content' => (string) round($a / $nSquared, 2), 'is_trap' => true, 'trap_explanation' => 'Не забудьте второе слагаемое'],
                    ]),
                ],
            ],
        ];
    }

    /**
     * Задание 4: Decimal expressions
     * Examples: 2.1/(6.6-2.4), (9.5+8.9)/2.3, 27/(3·4.5)
     */
    private function generateDecimalExpression(): array
    {
        $type = rand(0, 2);

        if ($type === 0) {
            // a/(b-c) or a/(b+c)
            return $this->generateDecimalDivision();
        } elseif ($type === 1) {
            // (a+b)/c or (a-b)/c
            return $this->generateDecimalNumerator();
        } else {
            // a/(b·c) or (a·b)/c
            return $this->generateDecimalMultiply();
        }
    }

    private function generateDecimalDivision(): array
    {
        // a/(b±c) where result is clean
        $isAdd = rand(0, 1) === 1;
        $op = $isAdd ? '+' : '-';

        // Pick b and c so b±c gives nice number
        $result = [2, 2.5, 3, 4, 4.2, 5, 6, 7, 8][rand(0, 8)];
        $b = round(rand(30, 90) / 10, 1);
        $c = $isAdd ? round($result - $b + rand(10, 50) / 10, 1) : round($b - $result, 1);

        if ($c <= 0) $c = round(rand(10, 30) / 10, 1);

        $denominator = $isAdd ? $b + $c : $b - $c;
        if ($denominator <= 0) {
            $denominator = abs($denominator) + 0.1;
        }

        // Pick a so a/denominator is clean
        $answer = round(rand(5, 30) / 10, 1);
        $a = round($answer * $denominator, 1);

        // Recalculate for precision
        $answer = round($a / $denominator, 2);

        $expressionText = "{$a}/({$b}{$op}{$c})";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\frac{{$a}}{{$b}{$op}{$c}}</span>",
            'answer' => (string) $answer,
            'answer_type' => 'number',
            'difficulty' => 2,
            'steps' => [
                [
                    'instruction' => "Вычислите знаменатель: {$b} {$op} {$c}",
                    'template' => "{$b} {$op} {$c} = [___]",
                    'correct_answers' => [(string) $denominator],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $denominator, 'is_correct' => true],
                        ['content' => (string) ($b + $c), 'is_trap' => !$isAdd, 'trap_explanation' => 'Проверьте знак операции'],
                        ['content' => (string) abs($b - $c), 'is_trap' => $isAdd, 'trap_explanation' => 'Проверьте знак операции'],
                        ['content' => (string) ($denominator + 1), 'is_trap' => true],
                    ]),
                ],
                [
                    'instruction' => 'Разделите',
                    'template' => "{$a} : {$denominator} = [___]",
                    'correct_answers' => [(string) $answer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $answer, 'is_correct' => true],
                        ['content' => (string) round($answer + 0.5, 1), 'is_trap' => true],
                        ['content' => (string) round($a / ($denominator + 1), 1), 'is_trap' => true],
                    ]),
                ],
            ],
        ];
    }

    private function generateDecimalNumerator(): array
    {
        // (a±b)/c
        $isAdd = rand(0, 1) === 1;
        $op = $isAdd ? '+' : '-';

        $c = [2, 2.2, 2.3, 2.5, 3, 4, 5][rand(0, 6)];
        $answer = round(rand(20, 80) / 10, 1);

        $numerator = round($answer * $c, 1);

        // Split numerator into a and b
        $a = round(rand(50, 120) / 10, 1);
        $b = $isAdd ? round($numerator - $a, 1) : round($a - $numerator, 1);

        if ($b <= 0) {
            $b = round(rand(10, 50) / 10, 1);
            $numerator = $isAdd ? $a + $b : $a - $b;
            $answer = round($numerator / $c, 2);
        }

        $expressionText = "({$a}{$op}{$b})/{$c}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\frac{{$a}{$op}{$b}}{{$c}}</span>",
            'answer' => (string) $answer,
            'answer_type' => 'number',
            'difficulty' => 2,
            'steps' => [
                [
                    'instruction' => "Вычислите числитель: {$a} {$op} {$b}",
                    'template' => "{$a} {$op} {$b} = [___]",
                    'correct_answers' => [(string) round($isAdd ? $a + $b : $a - $b, 1)],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) round($isAdd ? $a + $b : $a - $b, 1), 'is_correct' => true],
                        ['content' => (string) round($isAdd ? $a - $b : $a + $b, 1), 'is_trap' => true],
                        ['content' => (string) round($a * $b, 1), 'is_trap' => true],
                    ]),
                ],
                [
                    'instruction' => 'Разделите на ' . $c,
                    'template' => round($isAdd ? $a + $b : $a - $b, 1) . " : {$c} = [___]",
                    'correct_answers' => [(string) $answer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $answer, 'is_correct' => true],
                        ['content' => (string) round($answer + 1, 1), 'is_trap' => true],
                        ['content' => (string) round($answer * $c, 1), 'is_trap' => true, 'trap_explanation' => 'Нужно делить, а не умножать'],
                    ]),
                ],
            ],
        ];
    }

    private function generateDecimalMultiply(): array
    {
        // (a·b)/c or a/(b·c)
        $isNumerator = rand(0, 1) === 1;

        if ($isNumerator) {
            // (a·b)/c
            $a = round(rand(20, 90) / 10, 1);
            $b = round(rand(10, 20) / 10, 1);
            $c = round(rand(5, 15) / 10, 1);

            $numerator = round($a * $b, 2);
            $answer = round($numerator / $c, 2);

            $expressionText = "({$a}·{$b})/{$c}";

            return [
                'text' => "Найдите значение выражения: {$expressionText}",
                'text_html' => "Найдите значение выражения: <span class=\"math\">\\frac{{$a} \\cdot {$b}}{{$c}}</span>",
                'answer' => (string) $answer,
                'answer_type' => 'number',
                'difficulty' => 2,
                'steps' => [
                    [
                        'instruction' => "Вычислите числитель: {$a} · {$b}",
                        'template' => "{$a} · {$b} = [___]",
                        'correct_answers' => [(string) $numerator],
                        'blocks' => $this->shuffleBlocks([
                            ['content' => (string) $numerator, 'is_correct' => true],
                            ['content' => (string) round($a + $b, 1), 'is_trap' => true, 'trap_explanation' => 'Нужно умножить, а не сложить'],
                            ['content' => (string) round($numerator + 1, 1), 'is_trap' => true],
                        ]),
                    ],
                    [
                        'instruction' => "Разделите на {$c}",
                        'template' => "{$numerator} : {$c} = [___]",
                        'correct_answers' => [(string) $answer],
                        'blocks' => $this->shuffleBlocks([
                            ['content' => (string) $answer, 'is_correct' => true],
                            ['content' => (string) round($answer + 2, 1), 'is_trap' => true],
                        ]),
                    ],
                ],
            ];
        } else {
            // a/(b·c)
            $b = round(rand(20, 50) / 10, 1);
            $c = round(rand(10, 30) / 10, 1);
            $denominator = round($b * $c, 2);

            $answer = rand(2, 8);
            $a = $answer * $denominator;

            $expressionText = "{$a}/({$b}·{$c})";

            return [
                'text' => "Найдите значение выражения: {$expressionText}",
                'text_html' => "Найдите значение выражения: <span class=\"math\">\\frac{{$a}}{{$b} \\cdot {$c}}</span>",
                'answer' => (string) $answer,
                'answer_type' => 'number',
                'difficulty' => 2,
                'steps' => [
                    [
                        'instruction' => "Вычислите знаменатель: {$b} · {$c}",
                        'template' => "{$b} · {$c} = [___]",
                        'correct_answers' => [(string) $denominator],
                        'blocks' => $this->shuffleBlocks([
                            ['content' => (string) $denominator, 'is_correct' => true],
                            ['content' => (string) round($b + $c, 1), 'is_trap' => true],
                            ['content' => (string) round($denominator * 2, 1), 'is_trap' => true],
                        ]),
                    ],
                    [
                        'instruction' => "Разделите {$a} на {$denominator}",
                        'template' => "{$a} : {$denominator} = [___]",
                        'correct_answers' => [(string) $answer],
                        'blocks' => $this->shuffleBlocks([
                            ['content' => (string) $answer, 'is_correct' => true],
                            ['content' => (string) ($answer + 1), 'is_trap' => true],
                            ['content' => (string) ($answer - 1), 'is_trap' => true],
                        ]),
                    ],
                ],
            ];
        }
    }

    /**
     * Задание 5: a/(1 ± 1/n)
     * Example: 0.9/(1 + 1/5), 2.6/(1 - 1/14)
     */
    private function generateDivisionByBracket(): array
    {
        $n = [2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 14][rand(0, 10)];

        $isAdd = rand(0, 1) === 1;
        $op = $isAdd ? '+' : '-';

        // 1 ± 1/n = (n ± 1)/n
        $bracketNum = $isAdd ? $n + 1 : $n - 1;
        $bracketDen = $n;

        // Pick 'a' so that a / ((n±1)/n) = a * n / (n±1) is clean
        // a * n should be divisible by (n±1)
        $multiplier = rand(1, 5);
        $a = round($multiplier * $bracketNum / $n, 1);

        // Result: a / ((n±1)/n) = a * n / (n±1)
        $answer = round($a * $n / $bracketNum, 2);

        // Clean up if possible
        if (abs($answer - round($answer)) < 0.01) {
            $answer = (int) round($answer);
        }

        $expressionText = "{$a}/(1 {$op} 1/{$n})";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\frac{{$a}}{1 {$op} \\frac{1}{{$n}}}</span>",
            'answer' => (string) $answer,
            'answer_type' => 'number',
            'difficulty' => 3,
            'steps' => [
                [
                    'instruction' => "Вычислите знаменатель: 1 {$op} 1/{$n}",
                    'template' => "1 {$op} \\frac{1}{{$n}} = \\frac{[___]}{{$n}}",
                    'correct_answers' => [(string) $bracketNum],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $bracketNum, 'is_correct' => true],
                        ['content' => (string) $n, 'is_trap' => true, 'trap_explanation' => 'Не забудьте прибавить/вычесть 1'],
                        ['content' => (string) ($isAdd ? $n - 1 : $n + 1), 'is_trap' => true, 'trap_explanation' => 'Проверьте знак'],
                        ['content' => (string) ($n + 2), 'is_trap' => true],
                    ]),
                ],
                [
                    'instruction' => "Разделите {$a} на {$bracketNum}/{$n} (умножьте на перевёрнутую дробь)",
                    'template' => "{$a} \\cdot \\frac{{$n}}{{$bracketNum}} = [___]",
                    'correct_answers' => [(string) $answer],
                    'blocks' => $this->shuffleBlocks([
                        ['content' => (string) $answer, 'is_correct' => true],
                        ['content' => (string) round($a * $bracketNum / $n, 1), 'is_trap' => true, 'trap_explanation' => 'Дробь нужно перевернуть при делении'],
                        ['content' => (string) round($answer + 0.5, 1), 'is_trap' => true],
                    ]),
                ],
            ],
        ];
    }

    private function generateFractionMultiply(): array
    {
        // Generate fractions that result in nice answers
        // a/b · c/d = (a·c)/(b·d)

        // Pick numbers that simplify nicely
        $pairs = [
            [[3, 4], [6, 5]],    // 3/4 · 6/5 = 18/20 = 9/10
            [[21, 5], [3, 7]],   // 21/5 · 3/7 = 63/35 = 9/5
            [[3, 5], [25, 4]],   // 3/5 · 25/4 = 75/20 = 15/4
            [[9, 5], [2, 3]],    // 9/5 · 2/3 = 18/15 = 6/5
            [[5, 3], [9, 2]],    // 5/3 · 9/2 = 45/6 = 15/2
            [[7, 5], [12, 35]],  // 7/5 · 12/35 = 84/175 = 12/25
            [[2, 3], [9, 4]],    // 2/3 · 9/4 = 18/12 = 3/2
            [[4, 7], [21, 8]],   // 4/7 · 21/8 = 84/56 = 3/2
            [[5, 6], [18, 25]],  // 5/6 · 18/25 = 90/150 = 3/5
            [[8, 9], [27, 16]],  // 8/9 · 27/16 = 216/144 = 3/2
        ];

        // Or generate random
        if (rand(0, 1) === 0 && !empty($pairs)) {
            $pair = $pairs[array_rand($pairs)];
            [$a, $b] = $pair[0];
            [$c, $d] = $pair[1];
        } else {
            // Generate random fractions
            $a = rand(2, 15);
            $b = rand(2, 12);
            $c = rand(2, 15);
            $d = rand(2, 12);

            // Make sure they're not equal
            while ($a === $b) $b = rand(2, 12);
            while ($c === $d) $d = rand(2, 12);
        }

        $numerator = $a * $c;
        $denominator = $b * $d;
        $gcd = $this->gcd($numerator, $denominator);
        $resultNum = $numerator / $gcd;
        $resultDen = $denominator / $gcd;

        // Format answer
        if ($resultDen === 1) {
            $answer = (string) $resultNum;
        } else {
            $answer = "{$resultNum}/{$resultDen}";
        }

        // Decimal answer for alternative
        $decimalAnswer = round($resultNum / $resultDen, 2);

        $expression = "\\frac{{$a}}{{$b}} \\cdot \\frac{{$c}}{{$d}}";
        $expressionText = "{$a}/{$b} · {$c}/{$d}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\${$expression}\$</span>",
            'answer' => $answer,
            'answer_type' => 'number',
            'difficulty' => 2,
            'steps' => [
                [
                    'instruction' => 'Умножьте числители и знаменатели',
                    'template' => "\\frac{{$a} \\cdot {$c}}{{$b} \\cdot {$d}} = \\frac{[___]}{[___]}",
                    'correct_answers' => [(string) $numerator, (string) $denominator],
                    'blocks' => $this->generateFractionBlocks($numerator, $denominator),
                ],
                [
                    'instruction' => 'Сократите дробь',
                    'template' => "\\frac{{$numerator}}{{$denominator}} = [___]",
                    'correct_answers' => [$answer],
                    'blocks' => $this->generateAnswerBlocks($answer, $resultNum, $resultDen),
                ],
            ],
        ];
    }

    private function generateFractionDivide(): array
    {
        // a/b : c/d = a/b · d/c = (a·d)/(b·c)

        $a = rand(2, 15);
        $b = rand(2, 10);
        $c = rand(2, 10);
        $d = rand(2, 15);

        while ($a === $b) $b = rand(2, 10);
        while ($c === $d) $d = rand(2, 15);

        $numerator = $a * $d;
        $denominator = $b * $c;
        $gcd = $this->gcd($numerator, $denominator);
        $resultNum = $numerator / $gcd;
        $resultDen = $denominator / $gcd;

        if ($resultDen === 1) {
            $answer = (string) $resultNum;
        } else {
            $answer = "{$resultNum}/{$resultDen}";
        }

        $expression = "\\frac{{$a}}{{$b}} : \\frac{{$c}}{{$d}}";
        $expressionText = "{$a}/{$b} : {$c}/{$d}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\${$expression}\$</span>",
            'answer' => $answer,
            'answer_type' => 'number',
            'difficulty' => 2,
            'steps' => [
                [
                    'instruction' => 'При делении дробей — умножаем на перевёрнутую',
                    'template' => "\\frac{{$a}}{{$b}} \\cdot \\frac{[___]}{[___]}",
                    'correct_answers' => [(string) $d, (string) $c],
                    'blocks' => $this->generateFlipBlocks($c, $d),
                ],
                [
                    'instruction' => 'Вычислите и сократите',
                    'template' => "\\frac{{$a} \\cdot {$d}}{{$b} \\cdot {$c}} = [___]",
                    'correct_answers' => [$answer],
                    'blocks' => $this->generateAnswerBlocks($answer, $resultNum, $resultDen),
                ],
            ],
        ];
    }

    private function generateFractionAddSubtract(): array
    {
        // Use predefined compatible pairs
        $configs = [
            ['b' => 2, 'd' => 3, 'commonDen' => 6],
            ['b' => 2, 'd' => 5, 'commonDen' => 10],
            ['b' => 3, 'd' => 4, 'commonDen' => 12],
            ['b' => 4, 'd' => 5, 'commonDen' => 20],
            ['b' => 3, 'd' => 5, 'commonDen' => 15],
            ['b' => 2, 'd' => 7, 'commonDen' => 14],
            ['b' => 3, 'd' => 8, 'commonDen' => 24],
            ['b' => 5, 'd' => 6, 'commonDen' => 30],
        ];

        $config = $configs[array_rand($configs)];
        $b = $config['b'];
        $d = $config['d'];
        $commonDen = $config['commonDen'];

        $a = rand(1, $b - 1) ?: 1;
        $c = rand(1, $d - 1) ?: 1;

        $isSubtract = rand(0, 1) === 1;

        $mult1 = $commonDen / $b;
        $mult2 = $commonDen / $d;
        $num1 = $a * $mult1;
        $num2 = $c * $mult2;

        if ($isSubtract) {
            // Make sure result is positive
            if ($num1 < $num2) {
                [$a, $b, $c, $d] = [$c, $d, $a, $b];
                [$num1, $num2] = [$num2, $num1];
            }
            $resultNum = $num1 - $num2;
            $op = '-';
        } else {
            $resultNum = $num1 + $num2;
            $op = '+';
        }

        $gcd = $this->gcd($resultNum, $commonDen);
        $finalNum = $resultNum / $gcd;
        $finalDen = $commonDen / $gcd;

        if ($finalDen === 1) {
            $answer = (string) $finalNum;
        } elseif ($finalNum === 0) {
            $answer = '0';
        } else {
            $answer = "{$finalNum}/{$finalDen}";
        }

        $expression = "\\frac{{$a}}{{$b}} {$op} \\frac{{$c}}{{$d}}";
        $expressionText = "{$a}/{$b} {$op} {$c}/{$d}";

        return [
            'text' => "Найдите значение выражения: {$expressionText}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\${$expression}\$</span>",
            'answer' => $answer,
            'answer_type' => 'number',
            'difficulty' => 2,
            'steps' => [
                [
                    'instruction' => 'Приведите к общему знаменателю',
                    'template' => "\\frac{[___]}{{$commonDen}} {$op} \\frac{[___]}{{$commonDen}}",
                    'correct_answers' => [(string) $num1, (string) $num2],
                    'blocks' => $this->generateCommonDenBlocks($num1, $num2, $commonDen),
                ],
                [
                    'instruction' => 'Выполните действие и сократите',
                    'template' => "\\frac{{$num1} {$op} {$num2}}{{$commonDen}} = [___]",
                    'correct_answers' => [$answer],
                    'blocks' => $this->generateAnswerBlocks($answer, $finalNum, $finalDen),
                ],
            ],
        ];
    }

    /**
     * Task 07: Numbers on coordinate line
     */
    private function generateTask07(): array
    {
        // Simple: find where number is on line
        $a = rand(-10, 10);
        $b = rand(-10, 10);
        while ($b === $a) $b = rand(-10, 10);

        // Which is bigger?
        $answer = $a > $b ? (string) $a : (string) $b;

        return [
            'text' => "Какое из чисел больше: {$a} или {$b}?",
            'text_html' => "Какое из чисел больше: {$a} или {$b}?",
            'answer' => $answer,
            'difficulty' => 1,
            'steps' => [
                [
                    'instruction' => 'Выберите большее число',
                    'template' => 'Ответ: [___]',
                    'correct_answers' => [$answer],
                    'blocks' => [
                        ['content' => (string) $a, 'is_correct' => $a > $b],
                        ['content' => (string) $b, 'is_correct' => $b > $a],
                        ['content' => (string) ($a + $b), 'is_trap' => true, 'trap_explanation' => 'Это сумма чисел, не большее из них'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Task 08: Square roots and powers
     */
    private function generateTask08(): array
    {
        $perfectSquares = [4, 9, 16, 25, 36, 49, 64, 81, 100, 121, 144];
        $n = $perfectSquares[array_rand($perfectSquares)];
        $root = (int) sqrt($n);

        return [
            'text' => "Найдите значение выражения: √{$n}",
            'text_html' => "Найдите значение выражения: <span class=\"math\">\\sqrt{{$n}}</span>",
            'answer' => (string) $root,
            'difficulty' => 1,
            'steps' => [
                [
                    'instruction' => 'Найдите квадратный корень',
                    'template' => '√' . $n . ' = [___]',
                    'correct_answers' => [(string) $root],
                    'blocks' => [
                        ['content' => (string) $root, 'is_correct' => true],
                        ['content' => (string) ($root + 1), 'is_trap' => true, 'trap_explanation' => ($root + 1) . '² = ' . (($root + 1) * ($root + 1)) . ', не ' . $n],
                        ['content' => (string) ($root - 1), 'is_trap' => true, 'trap_explanation' => ($root - 1) . '² = ' . (($root - 1) * ($root - 1)) . ', не ' . $n],
                        ['content' => (string) ($n / 2), 'is_trap' => true, 'trap_explanation' => 'Корень — это не половина числа'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Task 10: Probability
     */
    private function generateTask10(): array
    {
        $total = rand(10, 50) * 2; // Even number
        $favorable = rand(1, $total - 1);

        $gcd = $this->gcd($favorable, $total);
        $numSimp = $favorable / $gcd;
        $denSimp = $total / $gcd;

        $probability = round($favorable / $total, 2);
        $answer = (string) $probability;

        $context = [
            "В урне {$total} шаров, из них {$favorable} красных.",
            "В классе {$total} учеников, из них {$favorable} девочек.",
            "На полке {$total} книг, из них {$favorable} учебников.",
        ];

        $contextText = $context[array_rand($context)];

        return [
            'text' => "{$contextText} Найдите вероятность того, что случайно выбранный объект будет нужного типа.",
            'text_html' => "{$contextText} Найдите вероятность.",
            'answer' => $answer,
            'difficulty' => 2,
            'steps' => [
                [
                    'instruction' => 'Вероятность = благоприятные / все',
                    'template' => 'P = [___] / [___]',
                    'correct_answers' => [(string) $favorable, (string) $total],
                    'blocks' => [
                        ['content' => (string) $favorable, 'is_correct' => true],
                        ['content' => (string) $total, 'is_correct' => true],
                        ['content' => (string) ($total - $favorable), 'is_trap' => true, 'trap_explanation' => 'Это число НЕблагоприятных исходов'],
                        ['content' => (string) ($total + $favorable), 'is_trap' => true],
                    ],
                ],
                [
                    'instruction' => 'Вычислите',
                    'template' => 'P = ' . $favorable . '/' . $total . ' = [___]',
                    'correct_answers' => [$answer],
                    'blocks' => [
                        ['content' => $answer, 'is_correct' => true],
                        ['content' => (string) round(1 - $probability, 2), 'is_trap' => true, 'trap_explanation' => 'Это вероятность противоположного события'],
                        ['content' => (string) round($probability * 100), 'is_trap' => true, 'trap_explanation' => 'Вероятность записывается от 0 до 1, не в процентах'],
                    ],
                ],
            ],
        ];
    }

    private function generateFractionBlocks(int $num, int $den): array
    {
        $blocks = [
            ['content' => (string) $num, 'is_correct' => true],
            ['content' => (string) $den, 'is_correct' => true],
        ];

        // Add traps
        $traps = [
            $num + rand(1, 5),
            $den + rand(1, 5),
            abs($num - $den),
            $num * 2,
        ];

        foreach (array_unique($traps) as $trap) {
            if ($trap != $num && $trap != $den && $trap > 0) {
                $blocks[] = ['content' => (string) $trap, 'is_trap' => true];
            }
        }

        shuffle($blocks);
        return array_slice($blocks, 0, 6);
    }

    private function generateAnswerBlocks(string $answer, int $num, int $den): array
    {
        $blocks = [
            ['content' => $answer, 'is_correct' => true],
        ];

        // Generate wrong answers
        if ($den !== 1) {
            $blocks[] = ['content' => "{$den}/{$num}", 'is_trap' => true, 'trap_explanation' => 'Числитель и знаменатель перепутаны'];
        }

        $wrongNum = $num + rand(1, 3);
        if ($den !== 1) {
            $blocks[] = ['content' => "{$wrongNum}/{$den}", 'is_trap' => true];
        } else {
            $blocks[] = ['content' => (string) $wrongNum, 'is_trap' => true];
        }

        $blocks[] = ['content' => (string) ($num + $den), 'is_trap' => true, 'trap_explanation' => 'Это сумма числителя и знаменателя'];

        shuffle($blocks);
        return $blocks;
    }

    private function generateFlipBlocks(int $c, int $d): array
    {
        return [
            ['content' => (string) $d, 'is_correct' => true],
            ['content' => (string) $c, 'is_correct' => true],
            ['content' => (string) ($c + $d), 'is_trap' => true],
            ['content' => (string) abs($c - $d), 'is_trap' => true, 'trap_explanation' => 'При делении дробей нужно перевернуть вторую дробь'],
        ];
    }

    private function generateCommonDenBlocks(int $num1, int $num2, int $den): array
    {
        $blocks = [
            ['content' => (string) $num1, 'is_correct' => true],
            ['content' => (string) $num2, 'is_correct' => true],
        ];

        // Traps
        $traps = [$num1 + 1, $num2 - 1, $den, $num1 + $num2];
        foreach (array_unique($traps) as $trap) {
            if ($trap != $num1 && $trap != $num2 && $trap > 0) {
                $blocks[] = ['content' => (string) $trap, 'is_trap' => true];
            }
        }

        shuffle($blocks);
        return array_slice($blocks, 0, 6);
    }

    private function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b !== 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $a ?: 1;
    }
}
