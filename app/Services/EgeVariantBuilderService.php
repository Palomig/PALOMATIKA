<?php
namespace App\Services;

class EgeVariantBuilderService
{
    protected array $allTopics = [
        '01','02','03','04','05','06','07','08','09','10',
        '11','12','13','14','15','16','17','18','19','20',
    ];

    public function __construct(private readonly EgeTaskDataService $taskData) {}

    /** @param array<string, array<int,int>> $excludeByTopic анти-повтор: topic_id => решённые task_ids */
    public function build(string $hash, array $excludeByTopic = []): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;
        $tasks = [];

        foreach ($this->allTopics as $topicId) {
            $item = $this->taskData->getRandomTaskFromTopic($topicId, 'production', $excludeByTopic[$topicId] ?? []);
            if (!$item) continue;

            $tasks[] = array_merge($item['task'], [
                'topic_id'       => $topicId,
                'topic_title'    => $this->taskData->getTopicMeta($topicId)['title'],
                'task_number'    => (int) ltrim($topicId, '0'),
                'type'           => $item['type'],
                'instruction'    => $item['instruction'],
                'block_number'   => $item['block_number'],
                'zadanie_number' => $item['zadanie_number'],
                'correct_answer' => $item['task']['answer'] ?? null,
            ]);
        }

        mt_srand();

        return ['tasks' => $tasks, 'variantNumber' => $variantNumber];
    }
}
