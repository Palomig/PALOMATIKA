<?php

namespace App\Services;

use Illuminate\Support\Str;

class MiniVariantService
{
    protected TaskDataService $taskData;

    // Algebra topics: 06-14
    protected array $algebraTopics = ['06', '07', '08', '09', '10', '11', '12', '13', '14'];

    // Geometry topics: 15-19
    protected array $geometryTopics = ['15', '16', '17', '18', '19'];

    public function __construct(TaskDataService $taskData)
    {
        $this->taskData = $taskData;
    }

    /**
     * Generate tasks for algebra mini-OGE (5 tasks, one per random topic from 06-14)
     */
    public function generateAlgebra(): array
    {
        $topics = $this->algebraTopics;
        shuffle($topics);
        $selected = array_slice($topics, 0, 5);

        return $this->pickOnePerTopic($selected, 'production');
    }

    /**
     * Generate tasks for geometry mini-OGE (5 tasks, one per topic 15-19)
     */
    public function generateGeometry(): array
    {
        return $this->pickOnePerTopic($this->geometryTopics, 'production');
    }

    /**
     * Generate tasks for mixed mini-OGE (4 algebra + 3 geometry)
     */
    public function generateMixed(): array
    {
        // 4 random algebra topics
        $algTopics = $this->algebraTopics;
        shuffle($algTopics);
        $algSelected = array_slice($algTopics, 0, 4);

        // 3 random geometry topics from 15, 16, 17
        $geoPool = ['15', '16', '17'];
        shuffle($geoPool);
        $geoSelected = array_slice($geoPool, 0, 3);

        $algTasks = $this->pickOnePerTopic($algSelected, 'production');
        $geoTasks = $this->pickOnePerTopic($geoSelected, 'production');

        return array_merge($algTasks, $geoTasks);
    }

    /**
     * Generate tasks for full variant (one per topic 06-19)
     */
    public function generateFull(): array
    {
        $allTopics = array_merge($this->algebraTopics, $this->geometryTopics);
        return $this->pickOnePerTopic($allTopics, 'production');
    }

    /**
     * Pick one random task from each of the given topic IDs.
     *
     * @param string|null $status Filter tasks by status ('production', 'draft', or null for all)
     */
    protected function pickOnePerTopic(array $topicIds, ?string $status = null): array
    {
        $result = [];
        $taskNumber = 1;

        foreach ($topicIds as $topicId) {
            $tasks = $this->taskData->getRandomTasks($topicId, 1, $status);
            if (!empty($tasks)) {
                $task = $tasks[0];
                $task['task_number'] = (int) ltrim($topicId, '0');
                $result[] = $task;
                $taskNumber++;
            }
        }

        return $result;
    }

    /**
     * Generate a unique hash for a new variant.
     */
    public function generateHash(): string
    {
        return strtolower(Str::random(10));
    }
}
