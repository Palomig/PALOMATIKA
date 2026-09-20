<?php

namespace App\Services;

use App\Models\TaskTopic;
use Illuminate\Support\Facades\Cache;

/**
 * Банк «Скиллы» — сквозные навыки без привязки к классу и экзамену:
 * десятичные дроби, проценты, степени. Тема = навык, задания внутри —
 * группы примеров с общей инструкцией.
 *
 * В отличие от ОГЭ/ЕГЭ/ВПР у банка нет JSON-файлов в storage: источник —
 * `database/data/skills/*.json` в репозитории, в базу их кладёт
 * `tasks:import-skills`, а читается всё через {@see TaskBankRepository}.
 */
class SkillsTaskDataService
{
    public const BANK = 'skills';

    /**
     * Темы банка в порядке кодов: `[['id' => '01', 'title' => 'Десятичные дроби'], …]`.
     *
     * @return array<int, array{id:string,title:string,description:string}>
     */
    public function getTopics(): array
    {
        return Cache::remember('skills:topics:v1', 3600, function () {
            return TaskTopic::query()
                ->where('bank', self::BANK)
                ->orderBy('topic')
                ->get()
                ->map(static fn (TaskTopic $t) => [
                    'id' => (string) $t->topic,
                    'title' => (string) ($t->payload['meta']['title'] ?? "Навык {$t->topic}"),
                    'description' => (string) ($t->payload['meta']['description'] ?? ''),
                ])
                ->all();
        });
    }

    public function getTopicMeta(string $topicId): array
    {
        foreach ($this->getTopics() as $topic) {
            if ($topic['id'] === $topicId) {
                return $topic;
            }
        }

        return ['id' => $topicId, 'title' => "Навык {$topicId}", 'description' => ''];
    }

    public function getTopicData(string $topicId): array
    {
        return Cache::remember("skills:topic:{$topicId}:v1", 3600, function () use ($topicId) {
            $repository = app(TaskBankRepository::class);
            if (!$repository->hasData(self::BANK, $topicId)) {
                return [];
            }

            return $repository->topicData(self::BANK, $topicId);
        });
    }

    public function getBlocks(string $topicId): array
    {
        return $this->getTopicData($topicId)['blocks'] ?? [];
    }

    /** После импорта: списки тем и сами темы читаются заново. */
    public static function forgetCache(): void
    {
        Cache::forget('skills:topics:v1');
        foreach (TaskTopic::query()->where('bank', self::BANK)->pluck('topic') as $topic) {
            Cache::forget("skills:topic:{$topic}:v1");
        }
        // Вкладки пикера считаются по наличию задач в банках.
        Cache::forget(LessonTaskPickerService::CLASSES_CACHE_KEY);
    }
}
