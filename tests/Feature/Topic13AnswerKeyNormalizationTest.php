<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Support\ResolvesTopicOptionAnswer;
use Tests\TestCase;

class Topic13AnswerKeyNormalizationTest extends TestCase
{
    use ResolvesTopicOptionAnswer;
    public function test_topic_13_required_zadaniya_have_populated_answers(): void
    {
        $path = storage_path('app/tasks/topic_13.json');
        $this->assertFileExists($path);

        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data, 'Invalid JSON in topic_13.json');

        $required = [3, 5, 6, 7, 11, 12];

        foreach ($required as $zadanieNumber) {
            $zadanie = $this->findBlock1Zadanie($data, $zadanieNumber);
            $this->assertIsArray($zadanie, "Missing Block 1 Zadanie {$zadanieNumber}");

            foreach (($zadanie['tasks'] ?? []) as $task) {
                $answer = trim((string) ($task['answer'] ?? ''));
                $this->assertNotSame('', $answer, "Missing answer for Z{$zadanieNumber} task {$task['id']}");
            }
        }
    }

    public function test_topic_13_z6_z11_z12_answer_distribution_is_not_trivially_all_1(): void
    {
        $path = storage_path('app/tasks/topic_13.json');
        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data, 'Invalid JSON in topic_13.json');

        foreach ([6, 11, 12] as $zadanieNumber) {
            $zadanie = $this->findBlock1Zadanie($data, $zadanieNumber);
            $this->assertIsArray($zadanie, "Missing Block 1 Zadanie {$zadanieNumber}");

            $answers = [];
            foreach (($zadanie['tasks'] ?? []) as $task) {
                $answer = trim((string) ($task['answer'] ?? ''));
                $this->assertNotSame('', $answer, "Invalid answer for Z{$zadanieNumber} task {$task['id']}");
                $answers[] = $answer;
            }

            $this->assertNotSame(
                ['1'],
                array_values(array_unique($answers)),
                "Z{$zadanieNumber} still has trivial all-1 distribution"
            );
        }
    }

    public function test_topic_13_z6_answer_points_to_true_solution_option_after_shuffle(): void
    {
        $path = storage_path('app/tasks/topic_13.json');
        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data, 'Invalid JSON in topic_13.json');

        $z6 = $this->findBlock1Zadanie($data, 6);
        $this->assertIsArray($z6);

        foreach (($z6['tasks'] ?? []) as $task) {
            $options = is_array($task['options'] ?? null) ? $task['options'] : [];
            $this->assertCount(4, $options, "Z6 task {$task['id']} must contain 4 options");

            $selected = $this->resolveSelectedOption($task, $options);
            $this->assertNotNull($selected, "Z6 task {$task['id']} selected option must resolve");

            $expected = $this->solveFactoredQuadratic((string) ($task['expression'] ?? ''));
            $this->assertNotNull($expected, "Failed to solve Z6 task {$task['id']} expression");
            $this->assertSame($expected, (string) ($selected['text'] ?? ''), "Z6 task {$task['id']} answer is not mapped to the true option");
        }
    }

    public function test_topic_13_z11_z12_runtime_svg_prompt_matches_selected_answer_option(): void
    {
        Cache::forget('topic_data_13');
        $blocks = app(TaskDataService::class)->getBlocks('13');

        foreach ([11, 12] as $zadanieNumber) {
            $zadanie = $blocks[0]['zadaniya'][$zadanieNumber - 1] ?? null;
            $this->assertIsArray($zadanie);

            foreach (($zadanie['tasks'] ?? []) as $task) {
                $options = is_array($task['options'] ?? null) ? $task['options'] : [];
                $this->assertCount(4, $options, "Z{$zadanieNumber} task {$task['id']} must contain 4 options");

                $selected = $this->resolveSelectedOption($task, $options);
                $this->assertNotNull($selected, "Z{$zadanieNumber} task {$task['id']} selected option must resolve");
                $selectedExpression = (string) ($selected['text'] ?? '');

                $svg = (string) ($task['svg'] ?? '');
                $this->assertStringStartsWith('<svg ', $svg);

                $solution = $this->solveQuadraticInequality($selectedExpression);
                $this->assertNotNull($solution, "Could not solve selected option for Z{$zadanieNumber} task {$task['id']}");

                foreach ($solution['labels'] as $label) {
                    $this->assertStringContainsString(">{$label}</text>", $svg);
                }

                $this->assertSame($solution['circle_count'], substr_count($svg, '<circle '));
            }
        }
    }

    private function findBlock1Zadanie(array $data, int $number): ?array
    {
        foreach (($data['blocks'][0]['zadaniya'] ?? []) as $zadanie) {
            if ((int) ($zadanie['number'] ?? 0) === $number) {
                return is_array($zadanie) ? $zadanie : null;
            }
        }

        return null;
    }

    private function solveFactoredQuadratic(string $expression): ?string
    {
        $normalized = trim($expression);
        $normalized = str_replace(['\\leq', '\\geq', '≤', '≥'], ['<=', '>=', '<=', '>='], $normalized);
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;

        if (!preg_match('/^\(x([+-])(\d+(?:,\d+)?)\)\(x([+-])(\d+(?:,\d+)?)\)(<=|>=|<|>)0$/u', $normalized, $m)) {
            return null;
        }

        $a = (float) str_replace(',', '.', $m[2]);
        $b = (float) str_replace(',', '.', $m[4]);
        $leftRoot = $m[1] === '+' ? -$a : $a;
        $rightRoot = $m[3] === '+' ? -$b : $b;
        if ($leftRoot > $rightRoot) {
            [$leftRoot, $rightRoot] = [$rightRoot, $leftRoot];
        }

        $op = $m[5];
        $l = $this->formatNumber($leftRoot);
        $r = $this->formatNumber($rightRoot);

        return match ($op) {
            '<=' => "[{$l}; {$r}]",
            '<' => "({$l}; {$r})",
            '>=' => "(-∞; {$l}] ∪ [{$r}; +∞)",
            '>' => "(-∞; {$l}) ∪ ({$r}; +∞)",
            default => null,
        };
    }

    /**
     * @return array{labels:array<int,string>,circle_count:int}|null
     */
    private function solveQuadraticInequality(string $expression): ?array
    {
        $expr = str_replace([' ', '²'], ['', '^2'], trim($expression));
        $expr = str_replace(['\\leq', '\\geq', '≤', '≥'], ['<=', '>=', '<=', '>='], $expr);

        $roots = null;
        $op = null;

        if (preg_match('/^x\^2([+-]\d+(?:,\d+)?)x(<=|>=|<|>)0$/u', $expr, $mx)) {
            $b = (float) str_replace(',', '.', $mx[1]);
            $roots = [0.0, -$b];
            $op = $mx[2];
        } elseif (preg_match('/^x\^2([+-]\d+(?:,\d+)?)(<=|>=|<|>)0$/u', $expr, $mc)) {
            $c = (float) str_replace(',', '.', $mc[1]);
            if ($c > 0) {
                return null;
            }
            $r = sqrt(abs($c));
            $roots = [-$r, $r];
            $op = $mc[2];
        } else {
            return null;
        }

        if ($roots[0] > $roots[1]) {
            [$roots[0], $roots[1]] = [$roots[1], $roots[0]];
        }

        $r1 = $roots[0];
        $r2 = $roots[1];

        return match ($op) {
            '<=' => ['labels' => [$this->formatLabel($r1), $this->formatLabel($r2)], 'circle_count' => 2],
            '<' => ['labels' => [$this->formatLabel($r1), $this->formatLabel($r2)], 'circle_count' => 2],
            '>=' => ['labels' => [$this->formatLabel($r1), $this->formatLabel($r2)], 'circle_count' => 2],
            '>' => ['labels' => [$this->formatLabel($r1), $this->formatLabel($r2)], 'circle_count' => 2],
            default => null,
        };
    }

    private function formatNumber(float $value): string
    {
        $rounded = round($value, 10);
        if (abs($rounded - round($rounded)) < 1e-9) {
            return (string) (int) round($rounded);
        }

        return str_replace('.', ',', rtrim(rtrim(sprintf('%.10F', $rounded), '0'), '.'));
    }

    private function formatLabel(float $value): string
    {
        return str_replace('-', '−', $this->formatNumber($value));
    }
}
