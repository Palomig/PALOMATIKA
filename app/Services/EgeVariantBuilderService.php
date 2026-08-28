<?php
namespace App\Services;

class EgeVariantBuilderService
{
    public function __construct(private readonly EgeTaskDataService $taskData) {}

    /**
     * Номера заданий берутся из карты уровня: у профиля их 19, у базы 21.
     * Ключи карты — строки «01»…«21», но «10» и дальше PHP хранит целыми,
     * поэтому приводим обратно.
     *
     * @return array<int, string>
     */
    private function topics(): array
    {
        return array_map(
            static fn ($topic) => str_pad((string) $topic, 2, '0', STR_PAD_LEFT),
            array_keys($this->taskData->getAllTopicsMeta())
        );
    }

    /** @param array<string, array<int,int>> $excludeByTopic анти-повтор: topic_id => решённые task_ids */
    public function build(string $hash, array $excludeByTopic = []): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $variantNumber = (abs($seed) % 999) + 1;
        $tasks = [];

        foreach ($this->topics() as $topicId) {
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
