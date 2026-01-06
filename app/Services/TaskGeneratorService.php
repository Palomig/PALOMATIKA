<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Service for generating random test tasks from parsed PDF data
 *
 * This service works with structured task data to generate random tests.
 * It can pick random tasks from different topics (6, 7, 8, etc.) to create
 * a complete OGE-style test.
 *
 * Data structure expected in JSON:
 * {
 *   "topic_id": "07",
 *   "title": "Числа, координатная прямая",
 *   "structured_blocks": [
 *     {
 *       "number": 1,
 *       "title": "ФИПИ",
 *       "zadaniya": [
 *         {
 *           "number": 1,
 *           "instruction": "На координатной прямой отмечено число a...",
 *           "type": "choice",
 *           "tasks": [
 *             {
 *               "id": 1,
 *               "image": "img-000.png",
 *               "options": ["$a - 6 < 0$", "$6 - a > 0$", "$a - 7 > 0$"],
 *               "correct": 0
 *             }
 *           ]
 *         }
 *       ]
 *     }
 *   ]
 * }
 */
class TaskGeneratorService
{
    /**
     * Available task types
     */
    const TYPE_CHOICE = 'choice';              // Multiple choice with image
    const TYPE_SIMPLE_CHOICE = 'simple_choice'; // Simple choice without per-task images
    const TYPE_FRACTION_CHOICE = 'fraction_choice';
    const TYPE_INTERVAL_CHOICE = 'interval_choice';
    const TYPE_SQRT_CHOICE = 'sqrt_choice';
    const TYPE_EXPRESSION = 'expression';       // Math expression to evaluate

    /**
     * Path to parsed JSON files
     */
    protected string $parsedPath;

    /**
     * Loaded topic data cache
     */
    protected array $topicCache = [];

    public function __construct()
    {
        $this->parsedPath = storage_path('app/parsed');
    }

    /**
     * Get all available topics
     */
    public function getAvailableTopics(): array
    {
        $topics = [];
        $files = glob($this->parsedPath . '/topic_*.json');

        foreach ($files as $file) {
            $data = $this->loadTopicFromFile($file);
            if ($data && !empty($data['structured_blocks'])) {
                $topics[] = [
                    'topic_id' => $data['topic_id'],
                    'title' => $data['title'] ?? '',
                    'blocks_count' => count($data['structured_blocks']),
                    'tasks_count' => $this->countTasks($data),
                ];
            }
        }

        usort($topics, fn($a, $b) => $a['topic_id'] <=> $b['topic_id']);

        return $topics;
    }

    /**
     * Get random tasks for a test
     *
     * @param array $topicIds Topics to include (e.g., ['06', '07', '08'])
     * @param int $tasksPerTopic How many tasks from each topic
     * @return array Array of tasks ready for test display
     */
    public function generateTest(array $topicIds, int $tasksPerTopic = 1): array
    {
        $testTasks = [];

        foreach ($topicIds as $topicId) {
            $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
            $tasks = $this->getRandomTasksFromTopic($topicId, $tasksPerTopic);
            $testTasks = array_merge($testTasks, $tasks);
        }

        // Shuffle all tasks
        shuffle($testTasks);

        // Add sequential test numbers
        foreach ($testTasks as $index => &$task) {
            $task['test_number'] = $index + 1;
        }

        return $testTasks;
    }

    /**
     * Get random tasks from a specific topic
     */
    public function getRandomTasksFromTopic(string $topicId, int $count = 1): array
    {
        $data = $this->loadTopic($topicId);
        if (!$data || empty($data['structured_blocks'])) {
            return [];
        }

        // Collect all tasks from all blocks
        $allTasks = [];
        foreach ($data['structured_blocks'] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (!empty($zadanie['tasks'])) {
                    foreach ($zadanie['tasks'] as $task) {
                        $allTasks[] = [
                            'topic_id' => $topicId,
                            'topic_title' => $data['title'] ?? '',
                            'block_number' => $block['number'],
                            'block_title' => $block['title'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'] ?? '',
                            'type' => $zadanie['type'] ?? self::TYPE_CHOICE,
                            'task' => $task,
                        ];
                    }
                } else {
                    // Zadanie without sub-tasks (simple choice)
                    $allTasks[] = [
                        'topic_id' => $topicId,
                        'topic_title' => $data['title'] ?? '',
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'] ?? '',
                        'type' => $zadanie['type'] ?? self::TYPE_SIMPLE_CHOICE,
                        'task' => [
                            'options' => $zadanie['options'] ?? [],
                            'image' => $zadanie['image'] ?? null,
                            'correct' => $zadanie['correct'] ?? null,
                        ],
                    ];
                }
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        // Select random tasks
        shuffle($allTasks);
        return array_slice($allTasks, 0, min($count, count($allTasks)));
    }

    /**
     * Get a specific task by topic, block, zadanie, and task id
     */
    public function getTask(string $topicId, int $blockNumber, int $zadanieNumber, int $taskId): ?array
    {
        $data = $this->loadTopic($topicId);
        if (!$data || empty($data['structured_blocks'])) {
            return null;
        }

        foreach ($data['structured_blocks'] as $block) {
            if ($block['number'] !== $blockNumber) continue;

            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if ($zadanie['number'] !== $zadanieNumber) continue;

                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if (($task['id'] ?? 0) === $taskId) {
                        return [
                            'topic_id' => $topicId,
                            'topic_title' => $data['title'] ?? '',
                            'block_number' => $block['number'],
                            'block_title' => $block['title'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'] ?? '',
                            'type' => $zadanie['type'] ?? self::TYPE_CHOICE,
                            'task' => $task,
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate task structure
     */
    public function validateTaskStructure(array $data): array
    {
        $errors = [];

        if (empty($data['topic_id'])) {
            $errors[] = 'Missing topic_id';
        }

        if (empty($data['structured_blocks'])) {
            $errors[] = 'Missing structured_blocks';
            return $errors;
        }

        foreach ($data['structured_blocks'] as $blockIndex => $block) {
            if (!isset($block['number'])) {
                $errors[] = "Block {$blockIndex}: missing number";
            }
            if (!isset($block['title'])) {
                $errors[] = "Block {$blockIndex}: missing title";
            }

            foreach ($block['zadaniya'] ?? [] as $zIndex => $zadanie) {
                if (!isset($zadanie['number'])) {
                    $errors[] = "Block {$block['number']}, Zadanie {$zIndex}: missing number";
                }
                if (!isset($zadanie['type'])) {
                    $errors[] = "Block {$block['number']}, Zadanie {$zadanie['number']}: missing type";
                }

                // Check tasks have options
                foreach ($zadanie['tasks'] ?? [] as $tIndex => $task) {
                    if (empty($task['options']) && empty($task['expression'])) {
                        $errors[] = "Block {$block['number']}, Zadanie {$zadanie['number']}, Task {$tIndex}: missing options or expression";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Check answer for a task
     */
    public function checkAnswer(array $task, $userAnswer): bool
    {
        $correctAnswer = $task['task']['correct'] ?? null;

        if ($correctAnswer === null) {
            // No correct answer defined - can't check
            return false;
        }

        return $userAnswer === $correctAnswer;
    }

    /**
     * Create example structured data from manually created topic07
     */
    public function createExampleStructure(string $topicId): array
    {
        // This returns an example structure that can be used as a template
        return [
            'topic_id' => $topicId,
            'title' => 'Example Topic',
            'created_at' => now()->format('Y-m-d H:i:s'),
            'structured_blocks' => [
                [
                    'number' => 1,
                    'title' => 'ФИПИ',
                    'zadaniya' => [
                        [
                            'number' => 1,
                            'instruction' => 'На координатной прямой отмечено число a. Какое из утверждений является верным?',
                            'type' => self::TYPE_CHOICE,
                            'tasks' => [
                                [
                                    'id' => 1,
                                    'image' => 'img-000.png',
                                    'options' => ['$a - 6 < 0$', '$6 - a > 0$', '$a - 7 > 0$', '$8 - a < 0$'],
                                    'correct' => 0, // First option is correct (0-indexed)
                                ],
                                [
                                    'id' => 2,
                                    'image' => 'img-001.png',
                                    'options' => ['$5 - a < 0$', '$a - 6 > 0$', '$a - 5 < 0$', '$4 - a > 0$'],
                                    'correct' => 3,
                                ],
                            ],
                        ],
                        [
                            'number' => 2,
                            'instruction' => 'Между какими целыми числами заключено число?',
                            'type' => self::TYPE_INTERVAL_CHOICE,
                            'tasks' => [
                                [
                                    'id' => 1,
                                    'expression' => '\frac{130}{11}',
                                    'options' => ['10 и 11', '11 и 12', '12 и 13', '13 и 14'],
                                    'correct' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Load topic data from file
     */
    protected function loadTopic(string $topicId): ?array
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

        if (isset($this->topicCache[$topicId])) {
            return $this->topicCache[$topicId];
        }

        $filePath = "{$this->parsedPath}/topic_{$topicId}.json";

        if (!File::exists($filePath)) {
            return null;
        }

        $data = json_decode(File::get($filePath), true);
        $this->topicCache[$topicId] = $data;

        return $data;
    }

    /**
     * Load topic from file path
     */
    protected function loadTopicFromFile(string $filePath): ?array
    {
        if (!File::exists($filePath)) {
            return null;
        }

        return json_decode(File::get($filePath), true);
    }

    /**
     * Count total tasks in topic data
     */
    protected function countTasks(array $data): int
    {
        $count = 0;

        foreach ($data['structured_blocks'] ?? [] as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (!empty($zadanie['tasks'])) {
                    $count += count($zadanie['tasks']);
                } else {
                    $count++; // Simple choice zadanie counts as 1 task
                }
            }
        }

        return $count;
    }
}
