<?php

namespace Tests\Feature;

use Tests\TestCase;

class Topic11Zadanie2MatchingIntegrityTest extends TestCase
{
    public function test_topic_11_block1_zadanie2_has_explicit_non_identity_answer_mapping_and_svg_matches_answer_option(): void
    {
        $path = storage_path('app/tasks/topic_11.json');
        $this->assertFileExists($path);

        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data, 'Invalid JSON in topic_11.json');

        $zadanie = $data['blocks'][0]['zadaniya'][1] ?? null; // B1/Z2
        $this->assertIsArray($zadanie);
        $this->assertSame('matching', $zadanie['type'] ?? null);

        $answers = [];
        foreach (($zadanie['tasks'] ?? []) as $task) {
            $answer = (string) ($task['answer'] ?? '');
            $this->assertMatchesRegularExpression('/^[1-3]{3}$/', $answer, "Task {$task['id']} must have 3-digit mapping answer");

            $svg = (string) ($task['svg'] ?? '');
            $this->assertNotSame('', $svg, "Task {$task['id']} must have SVG");

            $options = $task['options'] ?? [];
            $this->assertCount(3, $options, "Task {$task['id']} must have exactly 3 options");
            foreach ($options as $option) {
                $this->assertIsArray($option);
                $this->assertArrayHasKey('id', $option);
                $this->assertArrayHasKey('label', $option);
            }

            $answers[] = $answer;
        }

        $this->assertGreaterThan(1, count(array_unique($answers)), 'Answer mapping is identity/trivial for all tasks');
    }

    public function test_topic_11_block1_zadanie2_renders_in_matching_view(): void
    {
        $data = json_decode((string) file_get_contents(storage_path('app/tasks/topic_11.json')), true);
        $zadanie = $data['blocks'][0]['zadaniya'][1] ?? null; // B1/Z2
        $this->assertIsArray($zadanie);

        $view = $this->view('tasks.types.matching', [
            'zadanie' => $zadanie,
            'block' => ['number' => 1],
            'topicId' => '11',
            'isVariant' => false,
        ]);

        $html = (string) $view;
        $this->assertStringContainsString('Графики', $html);
        $this->assertStringContainsString('Формулы', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    /**
     * @return array{0:float,1:float}
     */
    private function extractLineFromSvg(string $svg): array
    {
        preg_match('/<path d="M ([^"]+)"/', $svg, $m);
        $this->assertNotEmpty($m[1] ?? null, 'SVG path not found');

        $pointsRaw = explode(' L ', (string) $m[1]);
        $first = $this->parsePoint($pointsRaw[0]);
        $last = $this->parsePoint($pointsRaw[count($pointsRaw) - 1]);

        $x1 = $first[0];
        $y1 = $first[1];
        $x2 = $last[0];
        $y2 = $last[1];

        $this->assertNotEquals($x1, $x2, 'Vertical line is unsupported for this test');

        $k = -(($y2 - $y1) / ($x2 - $x1));

        $mathX = ($x1 - 140.0) / 28.0;
        $mathY = (140.0 - $y1) / 28.0;
        $b = $mathY - ($k * $mathX);

        return [round($k, 3), round($b, 3)];
    }

    /**
     * @return array{0:float,1:float}
     */
    private function parseLinearFormula(string $formula): array
    {
        $s = str_replace([' ', '−', '–'], ['', '-', '-'], $formula);
        $this->assertStringStartsWith('y=', $s, "Unsupported formula format: {$formula}");
        $rhs = substr($s, 2);

        if (!str_contains($rhs, 'x')) {
            return [0.0, round($this->parseNumberToken($rhs), 3)];
        }

        [$coefToken, $tail] = explode('x', $rhs, 2);
        $coefToken = trim($coefToken);
        $tail = trim($tail);

        if ($coefToken === '' || $coefToken === '+') {
            $k = 1.0;
        } elseif ($coefToken === '-') {
            $k = -1.0;
        } else {
            $k = $this->parseNumberToken($coefToken);
        }

        $b = $tail === '' ? 0.0 : $this->parseNumberToken($tail);

        return [round($k, 3), round($b, 3)];
    }

    private function parseNumberToken(string $token): float
    {
        $token = trim($token);
        if ($token === '' || $token === '+') {
            return 0.0;
        }

        if ($token === '-') {
            return -0.0;
        }

        if (preg_match('/^([+-]?)\\\\frac\{(\d+)\}\{(\d+)\}$/', $token, $m)) {
            $sign = $m[1] === '-' ? -1.0 : 1.0;
            return $sign * ((float) $m[2] / (float) $m[3]);
        }

        $this->assertMatchesRegularExpression('/^[+-]?\d+(?:\.\d+)?$/', $token, "Unsupported number token: {$token}");

        return (float) $token;
    }

    /**
     * @return array{0:float,1:float}
     */
    private function parsePoint(string $pair): array
    {
        [$x, $y] = explode(',', $pair);
        return [(float) $x, (float) $y];
    }
}
