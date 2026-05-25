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

    /**
     * Полный бандл «банка навыков» (skills.json) для класса.
     * Структура: { groups: [...], levels: [...], skills: [...] }.
     * Используется страницами /alg-skills/{grade}.
     */
    public function getSkillsBundle(): array
    {
        $cacheKey = "alg_g{$this->grade}_skills_bundle";

        return Cache::remember($cacheKey, 3600, function () {
            $path = "{$this->basePath}/skills.json";
            if (!File::exists($path)) {
                return [];
            }
            return json_decode(File::get($path), true) ?? [];
        });
    }

    public function getSkillBySlug(string $slug): ?array
    {
        foreach ($this->getSkillsBundle()['skills'] ?? [] as $skill) {
            if (($skill['slug'] ?? null) === $slug) {
                return $skill;
            }
        }
        return null;
    }

    /**
     * Возвращает «представительные» задания уровня — без повторов
     * шаблона выражения. Логика воспроизводит JS-генератор
     * scripts/build-grade7-skill-pages.mjs (representativeTasks),
     * чтобы вкладка «Типы примеров» совпадала со статичной версией.
     *
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, array<string, mixed>>
     */
    public static function representativeTasks(array $tasks, int $limit = 12): array
    {
        $seen = [];
        $picked = [];

        foreach ($tasks as $task) {
            $expression = (string) ($task['expression'] ?? '');
            $signature = self::taskFeatureKey($expression) . '::' . self::taskSignature($expression);
            if (isset($seen[$signature])) {
                continue;
            }
            $seen[$signature] = true;
            $task['id'] = count($picked) + 1;
            $picked[] = $task;
            if (count($picked) >= $limit) {
                break;
            }
        }

        return $picked ?: array_slice($tasks, 0, min(count($tasks), $limit));
    }

    /**
     * Готовит выражение для рендера KaTeX:
     * - чисто математический текст (без кириллицы) оборачивается целиком в $...$
     * - смешанный — оборачиваются только числовые фрагменты с операциями
     *
     * Возвращает уже HTML-escape'нутую строку (для безопасной вставки в Blade {!! !!}).
     */
    public static function mathText(string $value): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $value));
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!preg_match('/[А-Яа-яЁё]/u', $text)) {
            return '$' . $escaped . '$';
        }

        $pattern = '/(?<![A-Za-zА-Яа-яЁё])(-?\d+(?:,\d+)?(?:\s*(?:\\\\cdot|[:=+\-])\s*-?\d+(?:,\d+)?|\s*[+\-]\s*\(-?\d+(?:,\d+)?\)|\s*[:=+\-]\s*\([^)]*\)|\s*\\\\cdot\s*\([^)]*\)|\s*\^\d+)*)/u';

        return preg_replace_callback($pattern, function (array $m) {
            $fragment = trim($m[1]);
            return ($fragment !== '' && preg_match('/\d/u', $fragment)) ? '$' . $fragment . '$' : $m[0];
        }, $escaped);
    }

    private static function taskSignature(string $expression): string
    {
        $s = preg_replace('/\\\\frac\{[^}]+\}\{[^}]+\}/u', '\\\\frac{n}{n}', $expression);
        $s = preg_replace('/-?\d+(?:,\d+)?/u', 'n', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim((string) $s);
    }

    private static function taskFeatureKey(string $expression): string
    {
        $features = [];
        if (str_contains($expression, '\\frac')) {
            $features[] = 'fractions';
        }
        if (preg_match('/\([xyz]\s*[-+]\s*\d+\)/u', $expression)) {
            $features[] = 'shifted-variable';
        }
        if (preg_match('/=\s*-?\d+[xyz]\b/u', $expression)) {
            $features[] = 'variable-right';
        }
        if (str_contains($expression, 'z')) {
            $features[] = 'three-vars';
        }
        if (preg_match_all('/\\\\\\\\/', $expression) >= 2) {
            $features[] = 'three-equations';
        }
        if (preg_match_all('/\(/', $expression) >= 2) {
            $features[] = 'nested-parentheses';
        }
        return $features ? implode('|', $features) : 'plain';
    }
}
