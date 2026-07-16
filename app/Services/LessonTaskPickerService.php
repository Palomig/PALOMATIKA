<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Возвращает каскадный список опций для picker'а задач на уроке.
 *
 * Унифицированная иерархия:
 *   bank → [grade?] → topic|skill → zadanie|level → task
 *
 * Бэнки oge / ege имеют фиксированные классы (9 / 11).
 * Бэнки vpr / alg-topic / alg-skill требуют выбор класса.
 *
 * Возвращает только задачи поддерживаемых типов (expression / choice).
 */
class LessonTaskPickerService
{
    public const TASK_TYPE_FALLBACK = 'expression';

    /** Разделы ОГЭ. У прочих банков разделов нет. */
    public const OGE_SECTIONS = [
        'part1' => ['title' => '1я часть',      'topics' => ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19']],
        'part2' => ['title' => '2я часть',      'topics' => ['20', '21', '23', '24', '25']],
        'new'   => ['title' => 'Новые задания', 'topics' => ['09', '10', '15', '16', '17']],
    ];

    /** Метка задания «Новые задания» внутри topic_XX.json (zadanie.label). */
    public const NEW_ZADANIE_LABEL = 'Новые задания';

    /**
     * @return array<int, array{id:string, title:string}>
     */
    public function sections(string $bank): array
    {
        if ($bank !== 'oge') {
            return [];
        }
        $out = [];
        foreach (self::OGE_SECTIONS as $id => $s) {
            $out[] = ['id' => $id, 'title' => $s['title']];
        }
        return $out;
    }

    public function grades(string $bank): array
    {
        return match ($bank) {
            'oge'       => [9],
            'ege'       => [11],
            'vpr'       => [5, 6, 7, 8],
            'alg-topic' => AlgTaskDataService::GRADES,
            'alg-skill' => AlgTaskDataService::GRADES,
            default     => [],
        };
    }

    /**
     * @return array<int, array{id:string, title:string}>
     */
    public function topics(string $bank, ?int $grade = null, ?string $section = null): array
    {
        $topics = match ($bank) {
            'oge'       => $this->ogeTopics(),
            'ege'       => $this->egeTopics(),
            'vpr'       => $grade ? $this->vprTopics($grade) : [],
            'alg-topic' => $grade ? $this->algTopics($grade) : [],
            default     => [],
        };

        if ($bank === 'oge' && $section !== null && isset(self::OGE_SECTIONS[$section])) {
            $byId = array_column($topics, null, 'id');
            $topics = [];
            foreach (self::OGE_SECTIONS[$section]['topics'] as $id) {
                if (isset($byId[$id])) {
                    $topics[] = $byId[$id];
                }
            }
        }

        foreach ($topics as &$t) {
            $ex = $this->firstTopicExample($bank, (string) $t['id'], $grade);
            $t['preview']     = $ex['expression'];
            $t['preview_svg'] = $ex['image_svg'];
        }
        return $topics;
    }

    /** Первый пример темы — берём первую поддерживаемую задачу через существующий tasks(). */
    private function firstTopicExample(string $bank, string $id, ?int $grade): array
    {
        $refs = array_filter(['topic_id' => $id, 'grade' => $grade], fn ($v) => $v !== null && $v !== '');
        $first = $this->tasks($bank, $refs)[0] ?? null;
        return [
            'expression' => (string) ($first['expression'] ?? ''),
            'image_svg'  => (string) ($first['image_svg'] ?? ''),
        ];
    }

    /**
     * @return array<int, array{slug:string, id:string, title:string}>
     */
    public function skills(int $grade): array
    {
        $bundle = (new AlgTaskDataService($grade))->getSkillsBundle();
        return array_values(array_map(function ($s) {
            $ex = $this->firstSkillExample($s);
            return [
                'slug'        => (string) ($s['slug']  ?? ''),
                'id'          => (string) ($s['id']    ?? ''),
                'title'       => (string) ($s['title'] ?? ''),
                'preview'     => $ex['expression'],
                'preview_svg' => $ex['image_svg'],
            ];
        }, $bundle['skills'] ?? []));
    }

    /** Первый непустой пример навыка: expression или svg из первой задачи первого уровня. */
    private function firstSkillExample(array $skill): array
    {
        foreach ($skill['levels'] ?? [] as $lvl) {
            foreach ($lvl['tasks'] ?? [] as $t) {
                $expr = (string) ($t['expression'] ?? '');
                $svg  = (string) ($t['svg'] ?? '');
                if ($expr !== '' || $svg !== '') {
                    return ['expression' => $expr, 'image_svg' => $svg];
                }
            }
        }
        return ['expression' => '', 'image_svg' => ''];
    }

    /**
     * @return array<int, array{number:int, instruction:string, task_count:int}>
     */
    public function zadaniya(string $bank, array $refs): array
    {
        $blocks = $this->resolveBlocks($bank, $refs);
        $result = [];
        $seen = [];
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $z) {
                $number = (int) ($z['number'] ?? 0);
                if (!$number || isset($seen[$number])) {
                    continue;
                }
                $tasks = $this->supportedTasks($z);
                if (empty($tasks)) {
                    continue;
                }
                $seen[$number] = true;
                $result[] = [
                    'number'      => $number,
                    'instruction' => $this->shorten((string) ($z['instruction'] ?? ''), 100),
                    'task_count'  => count($tasks),
                ];
            }
        }
        usort($result, fn ($a, $b) => $a['number'] <=> $b['number']);
        return $result;
    }

    /**
     * @return array<int, array{id:string, title:string}>
     */
    public function levels(int $grade, string $skillSlug): array
    {
        $skill = (new AlgTaskDataService($grade))->getSkillBySlug($skillSlug);
        if (!$skill) return [];
        return array_values(array_map(fn ($l) => [
            'id'    => (string) ($l['id'] ?? ''),
            'title' => (string) ($l['title'] ?? $l['id'] ?? ''),
        ], $skill['levels'] ?? []));
    }

    /**
     * Все задачи темы / навыка одним списком, сгруппированные через group_key/group_label.
     *
     * Для не-alg-skill: group_key = zadanie_number, group_label = "№N · instruction".
     * Для alg-skill:    group_key = level_id,       group_label = level title.
     *
     * Для bank='oge' поддерживается $section (ключ self::OGE_SECTIONS):
     * 'new' — только задания с label 'Новые задания' (в JSON это zadanie number 0),
     * 'part1'/'part2' — все остальные. Без $section — легаси-поведение (без «новых»).
     *
     * @return array<int, array{
     *   id:string|int, expression:string, text?:string, image?:string, answer:string,
     *   group_key:string|int, group_label:string, section?:string|null,
     *   zadanie_number?:int, level_id?:string
     * }>
     */
    public function tasks(string $bank, array $refs, ?string $section = null): array
    {
        if ($bank === 'alg-skill') {
            return $this->algSkillTasks($refs);
        }

        if (empty($refs['topic_id'])) return [];

        $filterBySection = $bank === 'oge' && $section !== null;

        $blocks = $this->resolveBlocks($bank, $refs);
        $result = [];
        foreach ($blocks as $block) {
            $blockNumber = (int) ($block['number'] ?? 0);
            foreach ($block['zadaniya'] ?? [] as $z) {
                $isNewZadanie = (string) ($z['label'] ?? '') === self::NEW_ZADANIE_LABEL;
                if ($filterBySection && ($section === 'new') !== $isNewZadanie) {
                    // 'new' — только задания с label 'Новые задания';
                    // part1/part2 — наоборот, без них (чтобы «новые» не дублировались).
                    continue;
                }
                $number = (int) ($z['number'] ?? 0);
                // «Новые задания» живут в zadanie с number 0 — для раздела 'new' это валидно.
                if (!$number && !($isNewZadanie && $section === 'new')) continue;
                $instruction = $this->shorten((string) ($z['instruction'] ?? ''), 80);
                $groupLabel = $isNewZadanie
                    ? self::NEW_ZADANIE_LABEL
                    : ($instruction !== '' ? "№{$number} · {$instruction}" : "№{$number}");
                foreach ($this->supportedTasks($z) as $t) {
                    $taskId = $t['id'] ?? '';
                    $expression = (string) ($t['expression'] ?? $t['prompt'] ?? $t['question'] ?? $t['text'] ?? '');
                    if ($expression === '') {
                        $expression = (string) ($z['instruction'] ?? '');
                    }
                    $result[] = [
                        'uid'            => "{$blockNumber}.{$number}.{$taskId}",
                        'id'             => $taskId,
                        'expression'     => $expression,
                        'text'           => (string) ($t['text'] ?? ''),
                        'image'          => (string) ($t['image'] ?? ''),
                        'answer'         => (string) ($t['answer'] ?? ''),
                        'image_svg'      => (string) ($t['svg'] ?? ''),
                        'group_key'      => $number,
                        'group_label'    => $groupLabel,
                        'zadanie_number' => $number,
                        'section'        => $section,
                    ];
                }
            }
        }
        return $result;
    }

    private function algSkillTasks(array $refs): array
    {
        $grade = (int) ($refs['grade'] ?? 0);
        $slug  = (string) ($refs['skill_slug'] ?? '');
        if (!$grade || !$slug) return [];

        $skill = (new AlgTaskDataService($grade))->getSkillBySlug($slug);
        if (!$skill) return [];

        $result = [];
        foreach ($skill['levels'] ?? [] as $lvl) {
            $levelId    = (string) ($lvl['id'] ?? '');
            $levelTitle = (string) ($lvl['title'] ?? $levelId);
            foreach ($lvl['tasks'] ?? [] as $t) {
                $taskId = $t['id'] ?? '';
                $result[] = [
                    'uid'         => "{$levelId}.{$taskId}",
                    'id'          => $taskId,
                    'expression'  => (string) ($t['expression'] ?? ''),
                    'answer'      => (string) ($t['answer'] ?? ''),
                    'image_svg'   => (string) ($t['svg'] ?? ''),
                    'group_key'   => $levelId,
                    'group_label' => $levelTitle,
                    'level_id'    => $levelId,
                ];
            }
        }
        return $result;
    }

    private function resolveBlocks(string $bank, array $refs): array
    {
        $topicId = (string) ($refs['topic_id'] ?? '');
        if ($topicId === '') return [];

        return match ($bank) {
            'oge'       => (new TaskDataService())->getBlocks($topicId) ?? [],
            'ege'       => (new EgeTaskDataService())->getTopicData($topicId)['blocks'] ?? [],
            'vpr'       => isset($refs['grade'])
                ? ((new VprTaskDataService((int) $refs['grade']))->getTopicData($topicId)['blocks'] ?? [])
                : [],
            'alg-topic' => isset($refs['grade'])
                ? ((new AlgTaskDataService((int) $refs['grade']))->getBlocks($topicId))
                : [],
            default     => [],
        };
    }

    private const IMAGE_LIKE_TYPES = [
        'matching', 'matching_signs', 'matching_4', 'matching_full',
        'graph_statements', 'statements',
        'geometry', 'grid_image', 'grid_image_with_question',
    ];

    /** Текстовые типы без картинки: условие в text/instruction + ответ. */
    private const TEXT_EXPRESSION_TYPES = ['word_problem'];

    private function supportedTasks(array $zadanie): array
    {
        $zadanieType = strtolower(trim((string) ($zadanie['type'] ?? '')));
        $instruction = (string) ($zadanie['instruction'] ?? '');

        // statements: задание целиком — одна задача (нет массива tasks).
        if ($zadanieType === 'statements' && empty($zadanie['tasks'])) {
            $synth = TaskBankResolver::synthesizeStatementsTask($zadanie);
            return $synth['answer'] !== '' ? [$synth] : [];
        }

        $result = [];
        foreach ($zadanie['tasks'] ?? [] as $task) {
            $type = strtolower(trim((string) ($task['task_type'] ?? $zadanieType)));
            $expression = (string) ($task['expression'] ?? $task['prompt'] ?? $task['question'] ?? $task['text'] ?? '');
            $hasAnswer  = (string) ($task['answer'] ?? '') !== '';
            $hasImage   = (string) ($task['svg'] ?? '') !== '' || (string) ($task['image'] ?? '') !== '';

            // Поддерживаются:
            //   expression / word_problem: условие (формула или текст) + ответ (auto-check).
            //   choice-like: выбор из вариантов (есть expression/prompt).
            //   image-like (matching/geometry/grid_image/statements/…): картинка + ответ ⇒
            //     ученик видит SVG и вписывает ответ свободным текстом.
            // Любой поддерживаемый тип с текстом условия + ответом считаем «текстовым»
            // (в т.ч. геометрия без рисунка: текст + числовой ответ).
            $isExpression = ($type === '' || $type === 'expression'
                    || in_array($type, self::TEXT_EXPRESSION_TYPES, true)
                    || in_array($type, self::IMAGE_LIKE_TYPES, true))
                && $expression !== '' && $hasAnswer;
            $isChoiceLike = in_array($type, ['choice', 'simple_choice', 'fraction_choice', 'interval_choice'], true)
                && $expression !== '';
            // Картинка + ответ без текста (matching/grid_image): условие берётся из instruction.
            $isImageLike = in_array($type, self::IMAGE_LIKE_TYPES, true)
                && $hasImage && $hasAnswer && ($expression !== '' || $instruction !== '');

            if ($isExpression || $isChoiceLike || $isImageLike) {
                $result[] = $task;
            }
        }
        return $result;
    }

    private function ogeTopics(): array
    {
        $svc = new TaskDataService();
        $result = [];
        foreach ($svc->getAllTopicsMeta() as $id => $meta) {
            $result[] = ['id' => (string) $id, 'title' => (string) ($meta['title'] ?? "Тема $id")];
        }
        usort($result, fn ($a, $b) => strcmp($a['id'], $b['id']));
        return $result;
    }

    private function egeTopics(): array
    {
        $base = storage_path('app/tasks/ege');
        $ids = [];
        if (File::isDirectory($base)) {
            foreach (File::files($base) as $file) {
                if (preg_match('/^topic_(\d{2})\.json$/', $file->getFilename(), $m)) {
                    $ids[] = $m[1];
                }
            }
        }
        sort($ids);
        $svc = new EgeTaskDataService();
        $allMeta = method_exists($svc, 'getAllTopicsMeta') ? $svc->getAllTopicsMeta() : [];
        $result = [];
        foreach ($ids as $id) {
            $title = $allMeta[$id]['title'] ?? null;
            if (!$title) {
                $data = $svc->getTopicData($id);
                $title = $data['meta']['title'] ?? "Тема $id";
            }
            $result[] = ['id' => $id, 'title' => (string) $title];
        }
        return $result;
    }

    private function vprTopics(int $grade): array
    {
        $svc = new VprTaskDataService($grade);
        $result = [];
        foreach ($svc->getAllTopicsMeta() as $id => $meta) {
            $result[] = ['id' => (string) $id, 'title' => (string) ($meta['title'] ?? "Тема $id")];
        }
        usort($result, fn ($a, $b) => strcmp($a['id'], $b['id']));
        return $result;
    }

    private function algTopics(int $grade): array
    {
        $svc = new AlgTaskDataService($grade);
        $result = [];
        foreach ($svc->getExistingTopicIds() as $id) {
            $meta = $svc->getTopicMeta($id);
            $result[] = ['id' => $id, 'title' => (string) ($meta['title'] ?? "Тема $id")];
        }
        return $result;
    }

    private function shorten(string $s, int $n): string
    {
        $s = trim($s);
        if (mb_strlen($s) <= $n) return $s;
        return mb_substr($s, 0, $n - 1) . '…';
    }
}
