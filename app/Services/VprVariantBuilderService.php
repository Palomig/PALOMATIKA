<?php
namespace App\Services;

class VprVariantBuilderService
{
    /** Все 18 топиков ВПР */
    protected array $allTopics = [
        '01','02','03','04','05','06','07','08','09',
        '10','11','12','13','14','15','16','17','18',
    ];

    public function __construct(private readonly VprTaskDataService $taskData) {}

    /**
     * Собрать вариант ВПР детерминированно из хэша.
     *
     * @return array{tasks: array, variantNumber: int}
     */
    public function build(string $hash): array
    {
        return $this->buildFromTopics($hash, $this->allTopics);
    }

    /**
     * Собрать мини-вариант ВПР из ограниченного набора тем.
     *
     * @return array{tasks: array, variantNumber: int}
     */
    public function buildMini(string $hash, int $taskCount = 5): array
    {
        $availableTopics = array_values(array_filter(
            $this->allTopics,
            fn (string $topicId) => $this->taskData->topicDataExists($topicId)
        ));

        if ($availableTopics === []) {
            return ['tasks' => [], 'variantNumber' => 1];
        }

        $seed = crc32($hash);
        mt_srand($seed);
        shuffle($availableTopics);
        mt_srand();

        $selectedTopics = array_slice($availableTopics, 0, max(1, min($taskCount, count($availableTopics))));
        usort($selectedTopics, fn (string $left, string $right) => (int) $left <=> (int) $right);

        return $this->buildFromTopics($hash, $selectedTopics);
    }

    /**
     * @param array<int, string> $topics
     * @return array{tasks: array, variantNumber: int}
     */
    private function buildFromTopics(string $hash, array $topics): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;
        $tasks = [];

        foreach ($topics as $topicId) {
            $item = $this->taskData->getRandomTaskFromTopic($topicId, 'production');
            if (!$item) {
                continue;
            }

            $tasks[] = array_merge($item['task'], [
                'topic_id'       => $topicId,
                'topic_title'    => $this->taskData->getTopicMeta($topicId)['title'],
                'task_number'    => (int) ltrim($topicId, '0'),
                'type'           => $item['type'],
                'instruction'    => $item['instruction'],
                'block_number'   => $item['block_number'],
                'zadanie_number' => $item['zadanie_number'],
                'correct_answer' => is_array($item['task']['answer'] ?? null)
                    ? implode('; ', $item['task']['answer'])
                    : ($item['task']['answer'] ?? null),
            ]);
        }

        mt_srand();

        return [
            'tasks' => $tasks,
            'variantNumber' => $variantNumber,
        ];
    }
}
