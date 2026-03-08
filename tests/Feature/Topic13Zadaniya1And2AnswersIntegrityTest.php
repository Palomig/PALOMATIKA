<?php

namespace Tests\Feature;

use Tests\Feature\Support\ResolvesTopicOptionAnswer;
use Tests\TestCase;

class Topic13Zadaniya1And2AnswersIntegrityTest extends TestCase
{
    use ResolvesTopicOptionAnswer;
    public function test_topic_13_zadaniya_1_and_2_in_both_blocks_have_non_null_non_trivial_answers(): void
    {
        $path = storage_path('app/tasks/topic_13.json');
        $this->assertFileExists($path);

        $data = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($data, 'Invalid JSON in topic_13.json');

        $targetPairs = [
            [0, 0], // block 1, zadanie 1
            [0, 1], // block 1, zadanie 2
            [1, 0], // block 2, zadanie 1
            [1, 1], // block 2, zadanie 2
        ];

        foreach ($targetPairs as [$blockIndex, $zadanieIndex]) {
            $tasks = $data['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['tasks'] ?? [];
            $this->assertNotEmpty($tasks, "Missing tasks for block index {$blockIndex}, zadanie index {$zadanieIndex}");

            $answers = [];
            $zadanieType = (string) ($data['blocks'][$blockIndex]['zadaniya'][$zadanieIndex]['type'] ?? '');
            foreach ($tasks as $task) {
                $answer = trim((string) ($task['answer'] ?? ''));
                $this->assertNotSame('', $answer, "Missing answer for task {$task['id']}");
                $options = is_array($task['options'] ?? null) ? $task['options'] : [];
                if ($zadanieType === 'choice' || !empty($options)) {
                    $this->assertNotNull(
                        $this->resolveSelectedOption($task, $options),
                        "Answer for task {$task['id']} must resolve to one option"
                    );
                }
                $answers[] = $answer;
            }

            $this->assertTrue(
                count(array_unique($answers)) > 1 || $answers[0] !== '1',
                "Trivial all-1 answer pattern detected for block index {$blockIndex}, zadanie index {$zadanieIndex}"
            );
        }
    }
}

