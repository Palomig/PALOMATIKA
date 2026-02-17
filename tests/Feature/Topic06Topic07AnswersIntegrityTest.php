<?php

namespace Tests\Feature;

use Tests\TestCase;

class Topic06Topic07AnswersIntegrityTest extends TestCase
{
    public function test_topics_06_07_have_non_empty_answers_for_all_tasks(): void
    {
        foreach (['06', '07'] as $topicId) {
            $path = storage_path("app/tasks/topic_{$topicId}.json");
            $this->assertFileExists($path);

            $data = json_decode((string) file_get_contents($path), true);
            $this->assertIsArray($data, "Invalid JSON in {$path}");

            foreach ($data['blocks'] ?? [] as $block) {
                foreach ($block['zadaniya'] ?? [] as $zadanie) {
                    foreach ($zadanie['tasks'] ?? [] as $task) {
                        $answer = trim((string) ($task['answer'] ?? ''));
                        $this->assertNotSame('', $answer, "Missing answer in topic {$topicId}, task {$task['id']}");
                        $this->assertNotSame('нет в базе', mb_strtolower($answer), "Unknown answer marker in topic {$topicId}, task {$task['id']}");
                    }
                }
            }
        }
    }
}
