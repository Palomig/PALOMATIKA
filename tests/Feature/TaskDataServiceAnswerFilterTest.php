<?php

namespace Tests\Feature;

use App\Services\TaskDataService;
use Tests\TestCase;

class TaskDataServiceAnswerFilterTest extends TestCase
{
    private function makeService(): TaskDataService
    {
        // Mock DB status lookup to return empty (falls back to JSON status field)
        $service = $this->getMockBuilder(TaskDataService::class)
            ->onlyMethods(['getTopicStatusMapFromDb'])
            ->getMock();

        $service->method('getTopicStatusMapFromDb')->willReturn([]);

        return $service;
    }

    public function test_topic_18_draft_tasks_excluded_with_production_status(): void
    {
        $service = $this->makeService();

        // block 1, zadanie 3 has only draft tasks with "нет в базе" answers
        $tasks = $service->getRandomTasksFromZadanie('18', 1, 3, 10, 'production');

        $this->assertSame([], $tasks);
    }

    public function test_topic_18_production_tasks_returned_with_production_status(): void
    {
        $service = $this->makeService();

        // block 1, zadanie 1 has production tasks with real answers
        $tasks = $service->getRandomTasksFromZadanie('18', 1, 1, 3, 'production');

        $this->assertNotEmpty($tasks);

        foreach ($tasks as $task) {
            $answer = trim((string) ($task['task']['answer'] ?? ''));
            $this->assertNotSame('', $answer);
            $this->assertNotSame('нет в базе', mb_strtolower($answer));
        }
    }
}
