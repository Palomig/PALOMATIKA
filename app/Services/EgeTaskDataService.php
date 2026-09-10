<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Сервис для работы с данными заданий ЕГЭ — профиль (П) или база (Б).
 *
 * Уровни — два независимых проекта банка ФИПИ с разной нумерацией заданий
 * (профиль 1–20, база 1–21), поэтому в БД это разные банки: `ege` и
 * `ege_b`. Вторым классом их не разводим: правила чтения, фильтр статусов
 * и сборка варианта у уровней одни, расходятся только НАБОР тем и место
 * хранения — ровно то, что здесь параметризовано. Копия разошлась бы с
 * оригиналом, как уже случилось с картами названий.
 *
 *   new EgeTaskDataService()                              // профиль
 *   new EgeTaskDataService(EgeTaskDataService::LEVEL_BASE) // база
 */
class EgeTaskDataService
{
    public const LEVEL_PROF = 'prof';
    public const LEVEL_BASE = 'base';

    /** Банк уровня в таблицах заданий. */
    public const BANK_PROF = 'ege';
    public const BANK_BASE = 'ege_b';

    protected string $basePath;
    protected string $level;
    protected string $bank;

    /**
     * Метаданные заданий профиля ЕГЭ (1–20) — запасные, если темы нет в базе.
     *
     * Нумерация — план КИМ 2027 (проект ФИПИ от 21.08.2026): добавлены
     * задания 6 (случайная величина) и 17 (модель из другого предмета),
     * отдельного «наибольшего и наименьшего» больше нет — оно внутри 9
     * вместе с графиком производной, а экономическая уехала из части 2
     * в часть 1 и стала тринадцатой. У базы структура не менялась.
     *
     * Названия обязаны совпадать с нумерацией ФИПИ: карта уже однажды
     * отстала от неё, и задание 10 значилось «Графики», хотя у ФИПИ 10 —
     * текстовая задача. Список тем на витрине брал названия отсюда,
     * страница задания — из банка, и одно и то же задание называлось
     * по-разному.
     */
    protected array $topicsMeta = [
        '01' => [
            'title' => 'Планиметрия',
            'description' => 'Геометрия на плоскости',
            'color' => 'purple',
            'icon' => 'shapes',
        ],
        '02' => [
            'title' => 'Векторы',
            'description' => 'Координаты и векторы',
            'color' => 'indigo',
            'icon' => 'arrow-right',
        ],
        '03' => [
            'title' => 'Стереометрия',
            'description' => 'Геометрия в пространстве',
            'color' => 'blue',
            'icon' => 'cube',
        ],
        '04' => [
            'title' => 'Вероятность (простая)',
            'description' => 'Теория вероятностей',
            'color' => 'cyan',
            'icon' => 'dice',
        ],
        '05' => [
            'title' => 'Вероятность (сложная)',
            'description' => 'Сложные задачи на вероятность',
            'color' => 'teal',
            'icon' => 'percent',
        ],
        '06' => [
            'title' => 'Случайная величина и распределения',
            'description' => 'Математическое ожидание, дисперсия, отклонение',
            'color' => 'sky',
            'icon' => 'chart-bar',
        ],
        '07' => [
            'title' => 'Уравнение',
            'description' => 'Простейшие уравнения',
            'color' => 'emerald',
            'icon' => 'equals',
        ],
        '08' => [
            'title' => 'Преобразование выражений',
            'description' => 'Вычисления и преобразования выражений',
            'color' => 'green',
            'icon' => 'function',
        ],
        '09' => [
            'title' => 'Производная, первообразная, экстремумы',
            'description' => 'График производной, касательная, наибольшее и наименьшее значения',
            'color' => 'lime',
            'icon' => 'trending-up',
        ],
        '10' => [
            'title' => 'Прикладная задача с формулой',
            'description' => 'Физические и практические задачи',
            'color' => 'yellow',
            'icon' => 'calculator',
        ],
        '11' => [
            'title' => 'Текстовая задача',
            'description' => 'Движение, работа, проценты и смеси',
            'color' => 'amber',
            'icon' => 'chart-line',
        ],
        '12' => [
            'title' => 'Графики функций',
            'description' => 'Чтение и анализ графиков',
            'color' => 'orange',
            'icon' => 'wrench',
        ],
        '13' => [
            'title' => 'Экономическая задача',
            'description' => 'Финансовая математика: кредиты, вклады, оптимизация',
            'color' => 'violet',
            'icon' => 'dollar',
        ],
        '14' => [
            'title' => 'Уравнение (часть 2)',
            'description' => 'Тригонометрические, показательные, логарифмические',
            'color' => 'rose',
            'icon' => 'sigma',
        ],
        '15' => [
            'title' => 'Стереометрия (часть 2)',
            'description' => 'Построения и вычисления в пространстве',
            'color' => 'pink',
            'icon' => 'box',
        ],
        '16' => [
            'title' => 'Неравенство',
            'description' => 'Логарифмические и показательные неравенства',
            'color' => 'fuchsia',
            'icon' => 'less-than',
        ],
        '17' => [
            'title' => 'Задача из другого предмета',
            'description' => 'Модель из физики, химии и других предметов',
            'color' => 'red',
            'icon' => 'beaker',
        ],
        '18' => [
            'title' => 'Планиметрия (часть 2)',
            'description' => 'Сложные планиметрические задачи',
            'color' => 'purple',
            'icon' => 'triangle',
        ],
        '19' => [
            'title' => 'Задача с параметром',
            'description' => 'Задачи с параметрами',
            'color' => 'indigo',
            'icon' => 'variable',
        ],
        '20' => [
            'title' => 'Числа и их свойства',
            'description' => 'Теория чисел',
            'color' => 'blue',
            'icon' => 'hash',
        ],
    ];

    /**
     * Метаданные заданий базы ЕГЭ (1–21) — запасные, как и у профиля.
     *
     * Названия те же, что в классификаторе банка (`classify_ege_tasks.py`,
     * карта TITLES) и в спецификации ФИПИ-2026. Настоящее название темы
     * приходит с самой задачей при импорте, эта карта нужна там, где банк
     * ещё не залит: цвет, иконка, подпись пустой темы.
     */
    protected array $baseTopicsMeta = [
        '01' => ['title' => 'Простейшая текстовая задача', 'description' => 'Бытовой расчёт в одно действие', 'color' => 'purple', 'icon' => 'calculator'],
        '02' => ['title' => 'Соответствие величин и значений', 'description' => 'Величины и их правдоподобные значения', 'color' => 'indigo', 'icon' => 'link'],
        '03' => ['title' => 'Чтение таблицы', 'description' => 'Выбор значения из таблицы', 'color' => 'blue', 'icon' => 'table'],
        '04' => ['title' => 'Расчёт по формуле', 'description' => 'Подстановка в готовую формулу', 'color' => 'cyan', 'icon' => 'function'],
        '05' => ['title' => 'Вероятность', 'description' => 'Простейшие вероятности', 'color' => 'teal', 'icon' => 'dice'],
        '06' => ['title' => 'Выбор оптимального варианта', 'description' => 'Сравнение вариантов по цене и условиям', 'color' => 'emerald', 'icon' => 'scale'],
        '07' => ['title' => 'График реальной зависимости', 'description' => 'Чтение графиков и диаграмм', 'color' => 'green', 'icon' => 'trending-up'],
        '08' => ['title' => 'Логика', 'description' => 'Верные и неверные утверждения', 'color' => 'lime', 'icon' => 'check-square'],
        '09' => ['title' => 'Площадь по клеткам', 'description' => 'Фигуры на клетчатой бумаге', 'color' => 'yellow', 'icon' => 'grid'],
        '10' => ['title' => 'Планиметрия', 'description' => 'Геометрия на плоскости', 'color' => 'amber', 'icon' => 'shapes'],
        '11' => ['title' => 'Объёмы и подобие', 'description' => 'Объём и изменение размеров', 'color' => 'orange', 'icon' => 'cube'],
        '12' => ['title' => 'Планиметрия', 'description' => 'Геометрия на плоскости по рисунку', 'color' => 'red', 'icon' => 'shapes'],
        '13' => ['title' => 'Стереометрия', 'description' => 'Геометрия в пространстве', 'color' => 'rose', 'icon' => 'cube'],
        '14' => ['title' => 'Вычисления', 'description' => 'Значение числового выражения', 'color' => 'pink', 'icon' => 'equals'],
        '15' => ['title' => 'Проценты', 'description' => 'Проценты, доли и отношения', 'color' => 'fuchsia', 'icon' => 'percent'],
        '16' => ['title' => 'Степени и стандартный вид', 'description' => 'Действия со степенями', 'color' => 'violet', 'icon' => 'superscript'],
        '17' => ['title' => 'Уравнение', 'description' => 'Простейшие уравнения', 'color' => 'purple', 'icon' => 'variable'],
        '18' => ['title' => 'Числа на координатной прямой', 'description' => 'Числа, прямая и неравенства', 'color' => 'indigo', 'icon' => 'ruler'],
        '19' => ['title' => 'Свойства чисел', 'description' => 'Делимость и признаки', 'color' => 'blue', 'icon' => 'hash'],
        '20' => ['title' => 'Текстовая задача', 'description' => 'Движение, работа, смеси', 'color' => 'cyan', 'icon' => 'route'],
        '21' => ['title' => 'Текстовая задача повышенной сложности', 'description' => 'Перебор и рассуждение', 'color' => 'teal', 'icon' => 'brain'],
    ];

    public function __construct(string $level = self::LEVEL_PROF)
    {
        $this->level = $level === self::LEVEL_BASE ? self::LEVEL_BASE : self::LEVEL_PROF;
        $this->bank = $this->level === self::LEVEL_BASE ? self::BANK_BASE : self::BANK_PROF;
        if ($this->level === self::LEVEL_BASE) {
            $this->topicsMeta = $this->baseTopicsMeta;
        }

        // JSON-файлы остались только у профиля: база приезжает сразу в БД,
        // отката на файл у неё нет и быть не может.
        $this->basePath = storage_path('app/tasks/ege');

        // Автоматически создаём директорию если её нет
        if (!File::isDirectory($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    /** Банк уровня — им адресуются таблицы заданий. */
    public function bank(): string
    {
        return $this->bank;
    }

    public function level(): string
    {
        return $this->level;
    }

    /**
     * Получить метаданные задания ЕГЭ
     */
    public function getTopicMeta(string $topicId): array
    {
        // Сначала проверяем данные из JSON файла
        $data = $this->getTopicData($topicId);
        if (!empty($data['meta'])) {
            return $data['meta'];
        }

        // Fallback на захардкоженные метаданные
        return $this->topicsMeta[$topicId] ?? [
            'title' => "Задание $topicId",
            'description' => '',
            'color' => 'gray',
            'icon' => 'book',
        ];
    }

    /**
     * Получить все метаданные заданий
     */
    public function getAllTopicsMeta(): array
    {
        return $this->topicsMeta;
    }

    /**
     * Получить данные задания из JSON
     */
    public function getTopicData(string $topicId): array
    {
        $cacheKey = "{$this->bank}_topic_data_{$topicId}";

        return Cache::remember($cacheKey, 3600, fn () => $this->readTopic($topicId));
    }

    /**
     * Тема из базы. Отката на `storage/app/tasks/ege/topic_NN.json` больше нет.
     *
     * Откат был нужен, пока банк переезжал в базу: выкладка кода и перенос
     * данных идут порознь. Переезд давно закончен, а на переходе профиля
     * к нумерации КИМ 2027 откат обернулся ложью на витрине: у новых заданий
     * 6 и 17 задач в банке нет, и вместо честного «данных нет» сервис поднял
     * файлы 2026 года — под шестым номером всплыло «Простейшие уравнения»,
     * под семнадцатым «Планиметрия (сложная)». Файлы на проде остались, но
     * больше не читаются: единственный источник профиля — база.
     */
    protected function readTopic(string $topicId): array
    {
        $repository = app(TaskBankRepository::class);
        if ($repository->hasData($this->bank, $topicId)) {
            return $repository->topicData($this->bank, $topicId);
        }

        return [];
    }

    /**
     * Получить блоки задания.
     *
     * `$status` фильтрует задачи по статусу из payload — так же, как это
     * делает {@see getRandomTaskFromTopic} для варианта ученика. Без фильтра
     * в выдачу попадают и черновики: у ЕГЭ это задачи, для которых ещё нет
     * ответа, и в сгенерированном варианте они выглядят обычными.
     */
    public function getBlocks(string $topicId, ?string $status = null): array
    {
        $data = $this->getTopicData($topicId);
        $blocks = $data['blocks'] ?? [];

        if ($status === null) {
            return $blocks;
        }

        foreach ($blocks as $bi => $block) {
            foreach (($block['zadaniya'] ?? []) as $zi => $zadanie) {
                $tasks = array_values(array_filter(
                    $zadanie['tasks'] ?? [],
                    static fn ($task) => ($task['status'] ?? 'production') === $status
                ));
                $blocks[$bi]['zadaniya'][$zi]['tasks'] = $tasks;
            }
        }

        return $blocks;
    }

    /**
     * Получить статистику задания
     */
    public function getTopicStats(string $topicId): array
    {
        $blocks = $this->getBlocks($topicId);

        $totalTasks = 0;
        $totalZadaniya = 0;

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                $totalZadaniya++;
                $totalTasks += count($zadanie['tasks'] ?? []);
            }
        }

        return [
            'blocks' => count($blocks),
            'zadaniya' => $totalZadaniya,
            'tasks' => $totalTasks,
        ];
    }

    /**
     * Получить случайные задания из темы
     */
    public function getRandomTasks(string $topicId, int $count = 1, ?string $status = 'production'): array
    {
        $blocks = $this->getBlocks($topicId, $status);
        $meta = $this->getTopicMeta($topicId);
        $allTasks = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    $allTasks[] = [
                        'topic_id' => $topicId,
                        'exam_type' => 'ege',
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'],
                        'type' => $zadanie['type'] ?? 'geometry',
                        'svg_type' => $zadanie['svg_type'] ?? null,
                        'task' => $task,
                    ];
                }
            }
        }

        if (empty($allTasks)) {
            return [];
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, $count);
    }

    /**
     * Проверить существование файла данных задания
     */
    public function topicDataExists(string $topicId): bool
    {
        return app(TaskBankRepository::class)->hasData($this->bank, $topicId);
    }

    /**
     * Получить список всех заданий с данными
     */
    public function getAvailableTopics(): array
    {
        $available = [];

        foreach (array_keys($this->topicsMeta) as $topicId) {
            if ($this->topicDataExists($topicId)) {
                $available[$topicId] = array_merge(
                    $this->getTopicMeta($topicId),
                    ['stats' => $this->getTopicStats($topicId)]
                );
            }
        }

        return $available;
    }

    /**
     * Получить случайные задания из конкретного zadanie
     */
    public function getRandomTasksFromZadanie(string $topicId, int $blockNumber, int $zadanieNumber, int $count = 1, ?string $status = 'production'): array
    {
        $blocks = $this->getBlocks($topicId, $status);
        $meta = $this->getTopicMeta($topicId);

        foreach ($blocks as $block) {
            if (($block['number'] ?? 0) != $blockNumber) continue;

            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (($zadanie['number'] ?? 0) != $zadanieNumber) continue;

                $tasks = $zadanie['tasks'] ?? [];
                if (empty($tasks)) return [];

                $selectedKeys = array_rand($tasks, min($count, count($tasks)));
                if (!is_array($selectedKeys)) $selectedKeys = [$selectedKeys];

                $result = [];
                foreach ($selectedKeys as $key) {
                    $result[] = [
                        'topic_id' => $topicId,
                        'exam_type' => 'ege',
                        'topic_title' => $meta['title'],
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'],
                        'type' => $zadanie['type'] ?? 'word_problem',
                        'svg_type' => $zadanie['svg_type'] ?? null,
                        'task' => $tasks[$key],
                    ];
                }

                return $result;
            }
        }

        return [];
    }

    /**
     * Выбрать случайную задачу из топика (статус production).
     * Возвращает структуру, совместимую с EgeVariantBuilderService.
     */
    public function getRandomTaskFromTopic(string $topicId, ?string $status = 'production', array $excludeTaskIds = []): ?array
    {
        $data = $this->getTopicData($topicId);
        $candidates = [];

        foreach ($data['blocks'] ?? [] as $blockIdx => $block) {
            foreach ($block['zadaniya'] ?? [] as $zadIdx => $zadanie) {
                foreach ($zadanie['tasks'] ?? [] as $task) {
                    if ($status && ($task['status'] ?? 'production') !== $status) continue;
                    $candidates[] = [
                        'task'           => $task,
                        'topic_id'       => $topicId,
                        'block_number'   => $block['number'] ?? ($blockIdx + 1),
                        'zadanie_number' => $zadanie['number'] ?? ($zadIdx + 1),
                        'task_number'    => (int) ltrim($topicId, '0'),
                        'type'           => $zadanie['type'] ?? 'expression',
                        'instruction'    => $zadanie['instruction'] ?? '',
                    ];
                }
            }
        }

        if (empty($candidates)) return null;

        // Анти-повтор: предпочитаем нерешённые примеры; если банк исчерпан — любой.
        if ($excludeTaskIds !== []) {
            $fresh = array_values(array_filter(
                $candidates,
                fn ($c) => !in_array((int) ($c['task']['id'] ?? 0), $excludeTaskIds, true)
            ));
            if ($fresh !== []) $candidates = $fresh;
        }

        return $candidates[array_rand($candidates)];
    }

    /**
     * Очистить весь кэш данных
     */
    public function clearCache(): void
    {
        foreach (array_keys($this->topicsMeta) as $topicId) {
            Cache::forget("{$this->bank}_topic_data_{$topicId}");
        }
    }
}
