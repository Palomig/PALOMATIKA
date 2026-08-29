<?php
namespace App\Services;

class EgeVariantBuilderService
{
    public function __construct(private readonly EgeTaskDataService $taskData) {}

    /**
     * Карта мини-режимов — единый источник для билдера, контроллера и UI.
     *
     * @return array<string, array{title:string, description:string, icon:string, topics:array<int,string>, count:int, variant_mode:string}>
     */
    public static function miniModes(string $level): array
    {
        $range = static fn (int $from, int $to): array => array_map(
            static fn (int $topic): string => str_pad((string) $topic, 2, '0', STR_PAD_LEFT),
            range($from, $to)
        );

        if ($level === EgeTaskDataService::LEVEL_BASE) {
            return [
                'practical' => [
                    'title' => 'Практические задачи',
                    'description' => 'Расчёты, таблицы, вероятность и логика',
                    'icon' => '🧭', 'topics' => $range(1, 8), 'count' => 5,
                    'variant_mode' => \App\Models\OgeVariant::MODE_MINI_PRACTICAL,
                ],
                'geometry' => [
                    'title' => 'Геометрия',
                    'description' => 'Площади, планиметрия и стереометрия',
                    'icon' => '📐', 'topics' => $range(9, 13), 'count' => 5,
                    'variant_mode' => \App\Models\OgeVariant::MODE_MINI_GEOMETRY,
                ],
                'calculation' => [
                    'title' => 'Вычисления и алгебра',
                    'description' => 'Проценты, степени, уравнения и свойства чисел',
                    'icon' => '🔢', 'topics' => $range(14, 21), 'count' => 5,
                    'variant_mode' => \App\Models\OgeVariant::MODE_MINI_CALCULATION,
                ],
                'mixed' => [
                    'title' => 'Смешанный',
                    'description' => 'Задания из всей базовой работы',
                    'icon' => '🔀', 'topics' => $range(1, 21), 'count' => 5,
                    'variant_mode' => \App\Models\OgeVariant::MODE_MINI_MIXED,
                ],
            ];
        }

        return [
            'part1' => [
                'title' => '1-я часть',
                'description' => 'Короткие задания с кратким ответом',
                'icon' => '⚡', 'topics' => $range(1, 12), 'count' => 5,
                'variant_mode' => \App\Models\OgeVariant::MODE_MINI_PART1,
            ],
            'part2' => [
                'title' => '2-я часть',
                'description' => 'Задания с развёрнутым решением',
                'icon' => '✍️', 'topics' => $range(13, 19), 'count' => 3,
                'variant_mode' => \App\Models\OgeVariant::MODE_MINI_PART2,
            ],
            'geometry' => [
                'title' => 'Геометрия',
                'description' => 'Планиметрия, векторы и стереометрия',
                'icon' => '📐', 'topics' => $range(1, 3), 'count' => 3,
                'variant_mode' => \App\Models\OgeVariant::MODE_MINI_GEOMETRY,
            ],
            'mixed' => [
                'title' => 'Смешанный',
                'description' => 'Задания из обеих частей профиля',
                'icon' => '🔀', 'topics' => $range(1, 19), 'count' => 5,
                'variant_mode' => \App\Models\OgeVariant::MODE_MINI_MIXED,
            ],
        ];
    }

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
        $tasks = $this->buildFromTopics($this->topics(), $excludeByTopic);

        mt_srand();

        return ['tasks' => $tasks, 'variantNumber' => $variantNumber];
    }

    /**
     * Собрать короткий вариант из случайных разных номеров режима.
     *
     * @param array<int,string> $topics
     * @param array<string,array<int,int>> $excludeByTopic
     */
    public function buildMini(string $hash, array $topics, int $count, array $excludeByTopic = []): array
    {
        $seed = crc32($hash);
        mt_srand($seed);

        $topics = array_values(array_unique(array_map(
            static fn ($topic): string => str_pad((string) $topic, 2, '0', STR_PAD_LEFT),
            $topics
        )));
        $topics = array_values(array_filter(
            $topics,
            fn (string $topic): bool => $this->taskData->topicDataExists($topic)
        ));

        if ($topics === []) {
            return ['tasks' => [], 'variantNumber' => (abs($seed) % 999) + 1];
        }

        shuffle($topics);
        $topics = array_slice($topics, 0, min($count, count($topics)));
        usort($topics, static fn (string $a, string $b): int => (int) $a <=> (int) $b);

        $tasks = $this->buildFromTopics($topics, $excludeByTopic);
        mt_srand();

        return ['tasks' => $tasks, 'variantNumber' => (abs($seed) % 999) + 1];
    }

    /** @param array<int,string> $topics @param array<string,array<int,int>> $excludeByTopic */
    private function buildFromTopics(array $topics, array $excludeByTopic): array
    {
        $tasks = [];

        foreach ($topics as $topicId) {
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

        return $tasks;
    }
}
