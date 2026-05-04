<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Сервис данных банка заданий по алгебре (5–8 классы).
 * Данные: storage/app/tasks/alg/grade_{N}/topic_NN.json
 *
 * Темы определяются динамически: контроллер показывает все topic_NN.json,
 * существующие в директории класса. Чтобы добавить новую тему — положить
 * JSON-файл (структура совместима с банком ОГЭ / ВПР).
 */
class AlgTaskDataService
{
    public const GRADES = [5, 6, 7, 8];

    protected string $basePath;

    public function __construct(protected int $grade)
    {
        if (!in_array($grade, self::GRADES, true)) {
            throw new \InvalidArgumentException("Класс {$grade} не поддерживается для алгебры");
        }

        $this->basePath = storage_path("app/tasks/alg/grade_{$grade}");

        if (!File::isDirectory($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    public function getGrade(): int
    {
        return $this->grade;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Список существующих topic_NN.json в директории класса (отсортирован).
     *
     * @return array<int, string>  topicId с лидирующим нулём, например ['01', '02', ...]
     */
    public function getExistingTopicIds(): array
    {
        if (!File::isDirectory($this->basePath)) {
            return [];
        }

        $ids = [];
        foreach (File::files($this->basePath) as $file) {
            if (preg_match('/^topic_(\d{2})\.json$/', $file->getFilename(), $m)) {
                $ids[] = $m[1];
            }
        }
        sort($ids);
        return $ids;
    }

    public function topicDataExists(string $topicId): bool
    {
        return File::exists("{$this->basePath}/topic_{$topicId}.json");
    }

    public function getTopicData(string $topicId): array
    {
        $cacheKey = "alg_g{$this->grade}_topic_{$topicId}";

        return Cache::remember($cacheKey, 3600, function () use ($topicId) {
            $path = "{$this->basePath}/topic_{$topicId}.json";
            if (!File::exists($path)) {
                return [];
            }
            return json_decode(File::get($path), true) ?? [];
        });
    }

    public function getBlocks(string $topicId): array
    {
        return $this->getTopicData($topicId)['blocks'] ?? [];
    }

    public function getTopicMeta(string $topicId): array
    {
        $data = $this->getTopicData($topicId);
        $meta = $data['meta'] ?? [];

        return array_merge([
            'title'       => $meta['title']       ?? "Тема {$topicId}",
            'description' => $meta['description'] ?? '',
            'color'       => $meta['color']       ?? 'emerald',
            'icon'        => $meta['icon']        ?? 'book',
        ], array_filter($meta, fn($v) => $v !== null && $v !== ''));
    }

    public function getTopicStats(string $topicId): array
    {
        $blocks = $this->getBlocks($topicId);
        $total = 0;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $total += count($zadanie['tasks'] ?? []);
            }
        }
        return ['blocks' => count($blocks), 'tasks' => $total, 'total_tasks' => $total];
    }
}
