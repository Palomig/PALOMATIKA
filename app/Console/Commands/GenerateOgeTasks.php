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
     * Найдите значение выражения: a/b · c/d или a/b : c/d
     */
    private function generateTask06(): array
    {
        $type = rand(0, 2); // 0 = multiply, 1 = divide, 2 = add/subtract

        if ($type === 0) {
            return $this->generateFractionMultiply();
        } elseif ($type === 1) {
            return $this->generateFractionDivide();
        } else {
            return $this->generateFractionAddSubtract();
        }
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
            'answer_type' => 'fraction',
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
            'answer_type' => 'fraction',
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
        // a/b - c/d with common denominator
        $commonDen = rand(2, 10) * rand(2, 5); // 4-50
        $b = $commonDen / rand(1, 3);
        if ($b < 2) $b = $commonDen;
        $b = (int) $b;

        $d = $commonDen / rand(1, 3);
        if ($d < 2) $d = $commonDen;
        $d = (int) $d;

        // Ensure b and d divide commonDen
        while ($commonDen % $b !== 0) $b++;
        while ($commonDen % $d !== 0) $d++;

        $a = rand(1, $b - 1);
        $c = rand(1, $d - 1);

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
            'answer_type' => 'fraction',
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
