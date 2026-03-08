<?php

namespace Tests\Feature;

use Tests\TestCase;

class Topic11Zadanie1MatchingSvgTest extends TestCase
{
    public function test_topic_11_z1_tasks_have_three_panel_svg_and_explicit_mapping_answer(): void
    {
        $path = storage_path('app/tasks/topic_11.json');
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $zadanie = null;
        foreach (($data['blocks'] ?? []) as $block) {
            if ((int) ($block['number'] ?? 0) !== 1) {
                continue;
            }
            foreach (($block['zadaniya'] ?? []) as $candidate) {
                if ((int) ($candidate['number'] ?? 0) === 1) {
                    $zadanie = $candidate;
                    break 2;
                }
            }
        }

        $this->assertIsArray($zadanie);
        $this->assertSame('matching', (string) ($zadanie['type'] ?? ''));
        $this->assertCount(6, $zadanie['tasks'] ?? []);

        foreach (($zadanie['tasks'] ?? []) as $task) {
            $svg = (string) ($task['svg'] ?? '');
            $answer = (string) ($task['answer'] ?? '');
            $options = $task['options'] ?? [];

            $this->assertStringContainsString('data-topic11-z1-three-panels="1"', $svg);
            $this->assertMatchesRegularExpression('/^[1-3]{3}$/', $answer);
            $this->assertCount(3, $options);
            $this->assertIsArray($options[0]);
            $this->assertArrayHasKey('id', $options[0]);
            $this->assertArrayHasKey('label', $options[0]);
        }
    }
}
