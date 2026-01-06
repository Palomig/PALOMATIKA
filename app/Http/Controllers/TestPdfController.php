<?php

namespace App\Http\Controllers;

use App\Services\PdfParserService;
use App\Services\PdfTaskParser;
use App\Services\TaskGeneratorService;
use App\Services\AdvancedPdfParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TestPdfController extends Controller
{
    protected PdfParserService $pdfParser;
    protected TaskGeneratorService $taskGenerator;
    protected AdvancedPdfParser $advancedParser;

    public function __construct(
        PdfParserService $pdfParser,
        TaskGeneratorService $taskGenerator,
        AdvancedPdfParser $advancedParser
    ) {
        $this->pdfParser = $pdfParser;
        $this->taskGenerator = $taskGenerator;
        $this->advancedParser = $advancedParser;
    }

    /**
     * Show test generator interface
     */
    public function testGenerator()
    {
        $availableTopics = $this->taskGenerator->getAvailableTopics();

        // Add manual topics (06-13, 18-19) which have structured data in controller
        $manualTopics = [
            ['topic_id' => '06', 'title' => 'Вычисления', 'tasks_count' => 174],
            ['topic_id' => '07', 'title' => 'Числа, координатная прямая', 'tasks_count' => 85],
            ['topic_id' => '08', 'title' => 'Квадратные корни и степени', 'tasks_count' => 278],
            ['topic_id' => '09', 'title' => 'Уравнения', 'tasks_count' => 177],
            ['topic_id' => '10', 'title' => 'Теория вероятностей', 'tasks_count' => 148],
            ['topic_id' => '11', 'title' => 'Графики функций', 'tasks_count' => 120],
            ['topic_id' => '12', 'title' => 'Расчеты по формулам', 'tasks_count' => 100],
            ['topic_id' => '13', 'title' => 'Неравенства', 'tasks_count' => 150],
            ['topic_id' => '18', 'title' => 'Фигуры на квадратной решётке', 'tasks_count' => 161],
            ['topic_id' => '19', 'title' => 'Анализ геометрических высказываний', 'tasks_count' => 136],
        ];

        // Merge, avoiding duplicates
        $allTopics = $manualTopics;
        foreach ($availableTopics as $topic) {
            $exists = false;
            foreach ($allTopics as $t) {
                if ($t['topic_id'] === $topic['topic_id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $allTopics[] = $topic;
            }
        }

        usort($allTopics, fn($a, $b) => $a['topic_id'] <=> $b['topic_id']);

        return view('test.generator', compact('allTopics'));
    }

    /**
     * Generate a random test
     */
    public function generateRandomTest(Request $request)
    {
        $request->validate([
            'topics' => 'required|array|min:1',
            'topics.*' => 'string|max:10',
            'tasks_per_topic' => 'integer|min:1|max:10',
        ]);

        $topicIds = $request->input('topics');
        $tasksPerTopic = $request->input('tasks_per_topic', 1);

        $testTasks = [];

        foreach ($topicIds as $topicId) {
            $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);

            // Check if it's a manual topic (06-13)
            if ($topicId === '06') {
                $tasks = $this->getRandomTasksFromManualData06($tasksPerTopic);
            } elseif ($topicId === '07') {
                $tasks = $this->getRandomTasksFromManualData07($tasksPerTopic);
            } elseif ($topicId === '08') {
                $tasks = $this->getRandomTasksFromManualData08($tasksPerTopic);
            } elseif ($topicId === '09') {
                $tasks = $this->getRandomTasksFromManualData09($tasksPerTopic);
            } elseif ($topicId === '10') {
                $tasks = $this->getRandomTasksFromManualData10($tasksPerTopic);
            } elseif ($topicId === '11') {
                $tasks = $this->getRandomTasksFromManualData11($tasksPerTopic);
            } elseif ($topicId === '12') {
                $tasks = $this->getRandomTasksFromManualData12($tasksPerTopic);
            } elseif ($topicId === '13') {
                $tasks = $this->getRandomTasksFromManualData13($tasksPerTopic);
            } elseif ($topicId === '18') {
                $tasks = $this->getRandomTasksFromManualData18($tasksPerTopic);
            } elseif ($topicId === '19') {
                $tasks = $this->getRandomTasksFromManualData19($tasksPerTopic);
            } else {
                $tasks = $this->taskGenerator->getRandomTasksFromTopic($topicId, $tasksPerTopic);
            }

            $testTasks = array_merge($testTasks, $tasks);
        }

        shuffle($testTasks);

        // Add sequential test numbers
        foreach ($testTasks as $index => &$task) {
            $task['test_number'] = $index + 1;
        }

        return view('test.random-test', compact('testTasks'));
    }

    /**
     * Get random tasks from manual topic 06 data
     */
    protected function getRandomTasksFromManualData06(int $count): array
    {
        $blocks = $this->getAllBlocksData();
        return $this->extractRandomTasks($blocks, '06', 'Вычисления', $count);
    }

    /**
     * Get random tasks from manual topic 07 data
     */
    protected function getRandomTasksFromManualData07(int $count): array
    {
        $blocks = $this->getAllBlocksData07();
        return $this->extractRandomTasks($blocks, '07', 'Числа, координатная прямая', $count);
    }

    /**
     * Get random tasks from manual topic 08 data
     */
    protected function getRandomTasksFromManualData08(int $count): array
    {
        $blocks = $this->getAllBlocksData08();
        return $this->extractRandomTasks($blocks, '08', 'Квадратные корни и степени', $count);
    }

    /**
     * Get random tasks from manual topic 09 data
     */
    protected function getRandomTasksFromManualData09(int $count): array
    {
        $blocks = $this->getAllBlocksData09();
        return $this->extractRandomTasks($blocks, '09', 'Уравнения', $count);
    }

    /**
     * Get random tasks from manual topic 10 data
     */
    protected function getRandomTasksFromManualData10(int $count): array
    {
        $blocks = $this->getAllBlocksData10();
        return $this->extractRandomTasks($blocks, '10', 'Теория вероятностей', $count);
    }

    /**
     * Get random tasks from manual topic 11 data
     */
    protected function getRandomTasksFromManualData11(int $count): array
    {
        $blocks = $this->getAllBlocksData11();
        return $this->extractRandomTasks($blocks, '11', 'Графики функций', $count);
    }

    /**
     * Get random tasks from manual topic 12 data
     */
    protected function getRandomTasksFromManualData12(int $count): array
    {
        $blocks = $this->getAllBlocksData12();
        return $this->extractRandomTasks($blocks, '12', 'Расчеты по формулам', $count);
    }

    /**
     * Get random tasks from manual topic 13 data
     */
    protected function getRandomTasksFromManualData13(int $count): array
    {
        $blocks = $this->getAllBlocksData13();
        return $this->extractRandomTasks($blocks, '13', 'Неравенства', $count);
    }

    /**
     * Extract random tasks from block structure
     */
    protected function extractRandomTasks(array $blocks, string $topicId, string $topicTitle, int $count): array
    {
        $allTasks = [];

        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (!empty($zadanie['tasks'])) {
                    foreach ($zadanie['tasks'] as $task) {
                        $allTasks[] = [
                            'topic_id' => $topicId,
                            'topic_title' => $topicTitle,
                            'block_number' => $block['number'],
                            'block_title' => $block['title'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'] ?? '',
                            'type' => $zadanie['type'] ?? 'expression',
                            'task' => $task,
                        ];
                    }
                } else {
                    // Simple zadanie without tasks array
                    $allTasks[] = [
                        'topic_id' => $topicId,
                        'topic_title' => $topicTitle,
                        'block_number' => $block['number'],
                        'block_title' => $block['title'],
                        'zadanie_number' => $zadanie['number'],
                        'instruction' => $zadanie['instruction'] ?? '',
                        'type' => $zadanie['type'] ?? 'simple_choice',
                        'task' => [
                            'options' => $zadanie['options'] ?? [],
                            'image' => $zadanie['image'] ?? null,
                        ],
                    ];
                }
            }
        }

        shuffle($allTasks);
        return array_slice($allTasks, 0, min($count, count($allTasks)));
    }

    /**
     * Show PDF parser web interface
     */
    public function pdfParserIndex()
    {
        $parsedPages = $this->getParsedPagesList();
        return view('test.pdf-parser', compact('parsedPages'));
    }

    /**
     * Handle PDF upload and parse
     */
    public function uploadPdf(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:20480',
            'topic_id' => 'required|string|max:10',
            'title' => 'nullable|string|max:255',
        ]);

        $topicId = str_pad($request->input('topic_id'), 2, '0', STR_PAD_LEFT);
        $title = $request->input('title') ?: null; // Let parser detect title if not provided

        try {
            // Save uploaded PDF
            $pdfFile = $request->file('pdf_file');
            $pdfFilename = "task_{$topicId}.pdf";
            $pdfPath = storage_path('app/pdf');

            if (!File::isDirectory($pdfPath)) {
                File::makeDirectory($pdfPath, 0755, true);
            }

            $pdfFile->move($pdfPath, $pdfFilename);

            // Check dependencies
            $deps = $this->pdfParser->checkDependencies();
            if (!$deps['ok']) {
                return back()->with('error', 'Не установлены зависимости: ' . implode(', ', $deps['missing']) . '. Установите: ' . $deps['install_hint']);
            }

            // Use advanced parser to create fully structured data
            $parsedData = $this->advancedParser->parseToStructured($pdfFilename, $topicId, $title);

            // Save parsed data as JSON
            $jsonPath = storage_path("app/parsed/topic_{$topicId}.json");
            $jsonDir = dirname($jsonPath);

            if (!File::isDirectory($jsonDir)) {
                File::makeDirectory($jsonDir, 0755, true);
            }

            File::put($jsonPath, json_encode($parsedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $blocksCount = count($parsedData['structured_blocks'] ?? []);
            $tasksCount = $this->countTasksInBlocks($parsedData['structured_blocks'] ?? []);

            return redirect()->route('test.parsed', $topicId)
                ->with('success', "PDF успешно распарсен! Блоков: {$blocksCount}, заданий: {$tasksCount}, изображений: {$parsedData['images_count']}.");

        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка парсинга: ' . $e->getMessage());
        }
    }

    /**
     * Count total tasks in structured blocks
     */
    protected function countTasksInBlocks(array $blocks): int
    {
        $count = 0;
        foreach ($blocks as $block) {
            foreach ($block['zadaniya'] ?? [] as $zadanie) {
                if (!empty($zadanie['tasks'])) {
                    $count += count($zadanie['tasks']);
                } else {
                    $count++; // Simple zadanie counts as 1
                }
            }
        }
        return $count;
    }

    /**
     * Show dynamically parsed page
     */
    public function showParsedPage(string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $jsonPath = storage_path("app/parsed/topic_{$topicId}.json");

        if (!File::exists($jsonPath)) {
            abort(404, 'Страница не найдена. Сначала загрузите PDF.');
        }

        $data = json_decode(File::get($jsonPath), true);

        return view('test.parsed-page', compact('data', 'topicId'));
    }

    /**
     * Download parsed JSON data
     */
    public function downloadJson(string $topicId)
    {
        $topicId = str_pad($topicId, 2, '0', STR_PAD_LEFT);
        $jsonPath = storage_path("app/parsed/topic_{$topicId}.json");

        if (!File::exists($jsonPath)) {
            abort(404, 'JSON не найден');
        }

        return response()->download($jsonPath, "topic_{$topicId}.json");
    }

    /**
     * Get list of all parsed pages
     */
    protected function getParsedPagesList(): array
    {
        $parsedDir = storage_path('app/parsed');
        $pages = [];

        if (!File::isDirectory($parsedDir)) {
            return $pages;
        }

        $files = glob($parsedDir . '/topic_*.json');

        foreach ($files as $file) {
            $data = json_decode(File::get($file), true);
            if ($data) {
                $pages[] = [
                    'topic_id' => $data['topic_id'] ?? '',
                    'title' => $data['title'] ?? 'Без названия',
                    'images_count' => $data['images_count'] ?? 0,
                    'blocks_count' => count($data['blocks'] ?? []),
                    'created_at' => $data['created_at'] ?? '',
                ];
            }
        }

        // Sort by topic_id
        usort($pages, fn($a, $b) => $a['topic_id'] <=> $b['topic_id']);

        return $pages;
    }

    /**
     * Display parsed tasks from PDF for topic 06
     */
    public function topic06()
    {
        // Use manual data with all blocks from PDF
        $blocks = $this->getAllBlocksData();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic06', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data from PDF
     */
    protected function getAllBlocksData(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // Задание 1 - Простые дроби
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{3}{4} \cdot \frac{6}{5}'],
                            ['id' => 7, 'expression' => '\frac{12}{5} : \frac{15}{2}'],
                            ['id' => 13, 'expression' => '\frac{1}{4} \cdot \frac{3}{25}'],
                            ['id' => 19, 'expression' => '\frac{14}{25} + \frac{3}{2}'],
                            ['id' => 2, 'expression' => '\frac{21}{5} \cdot \frac{3}{7}'],
                            ['id' => 8, 'expression' => '\frac{6}{5} : \frac{4}{11}'],
                            ['id' => 14, 'expression' => '\frac{1}{5} \cdot \frac{27}{50}'],
                            ['id' => 20, 'expression' => '\frac{9}{4} + \frac{8}{5}'],
                            ['id' => 3, 'expression' => '\frac{3}{5} \cdot \frac{25}{4}'],
                            ['id' => 9, 'expression' => '\frac{3}{5} : \frac{4}{35}'],
                            ['id' => 15, 'expression' => '\frac{1}{2} \cdot \frac{9}{25}'],
                            ['id' => 21, 'expression' => '\frac{11}{5} + \frac{13}{4}'],
                            ['id' => 4, 'expression' => '\frac{9}{5} \cdot \frac{2}{3}'],
                            ['id' => 10, 'expression' => '\frac{15}{4} : \frac{3}{7}'],
                            ['id' => 16, 'expression' => '\frac{1}{5} \cdot \frac{3}{4}'],
                            ['id' => 22, 'expression' => '\frac{1}{10} + \frac{21}{50}'],
                            ['id' => 5, 'expression' => '\frac{5}{3} \cdot \frac{9}{2}'],
                            ['id' => 11, 'expression' => '\frac{21}{2} : \frac{3}{5}'],
                            ['id' => 17, 'expression' => '\frac{1}{2} \cdot \frac{13}{50}'],
                            ['id' => 23, 'expression' => '\frac{3}{4} + \frac{7}{25}'],
                            ['id' => 6, 'expression' => '\frac{7}{5} \cdot \frac{12}{35}'],
                            ['id' => 12, 'expression' => '\frac{14}{5} : \frac{7}{2}'],
                            ['id' => 18, 'expression' => '\frac{1}{10} \cdot \frac{23}{20}'],
                            ['id' => 24, 'expression' => '\frac{4}{25} + \frac{15}{4}'],
                        ]
                    ],
                    // Задание 2 - Десятичные
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '9{,}3 + 7{,}8'],
                            ['id' => 4, 'expression' => '5{,}7 - 7{,}6'],
                            ['id' => 7, 'expression' => '5{,}2 \cdot 3{,}1'],
                            ['id' => 10, 'expression' => '\frac{8{,}2}{4{,}1}'],
                            ['id' => 2, 'expression' => '8{,}7 + 4{,}6'],
                            ['id' => 5, 'expression' => '4{,}9 - 9{,}4'],
                            ['id' => 8, 'expression' => '2{,}1 \cdot 9{,}6'],
                            ['id' => 11, 'expression' => '\frac{13{,}2}{1{,}2}'],
                            ['id' => 3, 'expression' => '6{,}9 + 7{,}4'],
                            ['id' => 6, 'expression' => '6{,}1 - 2{,}5'],
                            ['id' => 9, 'expression' => '8{,}9 \cdot 4{,}3'],
                            ['id' => 12, 'expression' => '\frac{6{,}5}{1{,}3}'],
                        ]
                    ],
                    // Задание 3 - Приведение к знаменателю
                    [
                        'number' => 3,
                        'instruction' => 'Представьте выражение в виде дроби с указанным знаменателем',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{7}{9} - \frac{2}{5}', 'denominator' => 90],
                            ['id' => 2, 'expression' => '\frac{6}{7} - \frac{3}{5}', 'denominator' => 70],
                            ['id' => 3, 'expression' => '\frac{1}{7} + \frac{3}{4}', 'denominator' => 56],
                            ['id' => 4, 'expression' => '\frac{5}{8} + \frac{1}{3}', 'denominator' => 48],
                            ['id' => 5, 'expression' => '\frac{3}{4} - \frac{8}{11}', 'denominator' => 88],
                            ['id' => 6, 'expression' => '\frac{2}{3} - \frac{7}{13}', 'denominator' => 78],
                        ]
                    ],
                    // Задание 4 - Сложные дроби
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{\frac{1}{30} + \frac{1}{42}}'],
                            ['id' => 3, 'expression' => '\frac{1}{\frac{1}{36} + \frac{1}{45}}'],
                            ['id' => 5, 'expression' => '\frac{1}{\frac{1}{21} + \frac{1}{28}}'],
                            ['id' => 2, 'expression' => '\frac{1}{\frac{1}{36} - \frac{1}{44}}'],
                            ['id' => 4, 'expression' => '\frac{1}{\frac{1}{35} - \frac{1}{60}}'],
                            ['id' => 6, 'expression' => '\frac{1}{\frac{1}{72} - \frac{1}{99}}'],
                        ]
                    ]
                ]
            ],

            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // Задание 1 - Сложные выражения с дробями
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\left(\frac{17}{10} - \frac{1}{20}\right) \cdot \frac{2}{15}'],
                            ['id' => 4, 'expression' => '\left(\frac{10}{13} + \frac{15}{4}\right) \cdot \frac{26}{5}'],
                            ['id' => 7, 'expression' => '\left(\frac{3}{4} - \frac{1}{6}\right) \cdot 3'],
                            ['id' => 10, 'expression' => '\left(\frac{2}{20} + \frac{7}{30}\right) \cdot 15'],
                            ['id' => 2, 'expression' => '\left(\frac{5}{22} - \frac{8}{11}\right) \cdot \frac{11}{5}'],
                            ['id' => 5, 'expression' => '\left(\frac{17}{26} + \frac{11}{13}\right) \cdot \frac{17}{6}'],
                            ['id' => 8, 'expression' => '\left(\frac{2}{5} + \frac{13}{15}\right) \cdot 6'],
                            ['id' => 11, 'expression' => '\left(\frac{9}{10} - \frac{7}{15}\right) \cdot 3'],
                            ['id' => 3, 'expression' => '\left(\frac{5}{26} - \frac{3}{25}\right) \cdot \frac{13}{2}'],
                            ['id' => 6, 'expression' => '\left(\frac{11}{12} + \frac{11}{20}\right) \cdot \frac{15}{8}'],
                            ['id' => 9, 'expression' => '\left(\frac{3}{8} - \frac{1}{20}\right) \cdot 10'],
                            ['id' => 12, 'expression' => '\left(\frac{1}{6} + \frac{1}{4}\right) \cdot 9'],
                        ]
                    ],
                    // Задание 2 - Смешанные числа
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\left(\frac{9}{16} + 2\frac{3}{8}\right) \cdot 4'],
                            ['id' => 5, 'expression' => '\left(1\frac{3}{4} + 2\frac{4}{5}\right) \cdot 30'],
                            ['id' => 9, 'expression' => '4\frac{7}{8} : \left(2\frac{3}{4} + 1\frac{10}{19}\right)'],
                            ['id' => 2, 'expression' => '\left(\frac{4}{9} - 3\frac{1}{15}\right) \cdot 9'],
                            ['id' => 6, 'expression' => '\left(\frac{1}{13} - 2\frac{3}{4}\right) \cdot 26'],
                            ['id' => 10, 'expression' => '1\frac{1}{12} : \left(1\frac{13}{18} - 2\frac{5}{9}\right)'],
                            ['id' => 3, 'expression' => '\left(2\frac{3}{4} + 2\frac{1}{5}\right) \cdot 16'],
                            ['id' => 7, 'expression' => '1\frac{8}{17} : \left(\frac{12}{17} + 2\frac{7}{11}\right)'],
                            ['id' => 11, 'expression' => '3\frac{1}{2} : \left(1\frac{4}{15} + 2\frac{9}{10}\right)'],
                            ['id' => 4, 'expression' => '\left(1\frac{11}{16} - 3\frac{7}{8}\right) \cdot 4'],
                            ['id' => 8, 'expression' => '3\frac{4}{9} : \left(1\frac{5}{9} - \frac{4}{7}\right)'],
                            ['id' => 12, 'expression' => '4\frac{1}{4} : \left(2\frac{7}{10} - 3\frac{1}{8}\right)'],
                        ]
                    ],
                    // Задание 3 - Степени дробей
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '10 \cdot \left(\frac{1}{5}\right)^2 - 12 \cdot \frac{1}{5}'],
                            ['id' => 3, 'expression' => '21 \cdot \left(\frac{1}{7}\right)^2 - 10 \cdot \frac{1}{7}'],
                            ['id' => 5, 'expression' => '18 \cdot \left(\frac{1}{9}\right)^2 - 20 \cdot \frac{1}{9}'],
                            ['id' => 2, 'expression' => '8 \cdot \left(\frac{1}{4}\right)^2 - 14 \cdot \frac{1}{4}'],
                            ['id' => 4, 'expression' => '6 \cdot \left(\frac{1}{3}\right)^2 - 17 \cdot \frac{1}{3}'],
                            ['id' => 6, 'expression' => '15 \cdot \left(\frac{1}{5}\right)^2 - 8 \cdot \frac{1}{5}'],
                        ]
                    ],
                    // Задание 4 - Десятичные дроби сложные
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{2{,}1}{6{,}6 - 2{,}4}'],
                            ['id' => 7, 'expression' => '\frac{9{,}5 + 8{,}9}{2{,}3}'],
                            ['id' => 13, 'expression' => '\frac{27}{3 \cdot 4{,}5}'],
                            ['id' => 19, 'expression' => '\frac{8{,}4 \cdot 1{,}3}{0{,}7}'],
                            ['id' => 2, 'expression' => '\frac{7{,}2}{8{,}3 - 8{,}6}'],
                            ['id' => 8, 'expression' => '\frac{6{,}8 - 4{,}7}{1{,}4}'],
                            ['id' => 14, 'expression' => '\frac{16}{3{,}2 \cdot 2}'],
                            ['id' => 20, 'expression' => '\frac{4{,}4 \cdot 0{,}3}{6{,}6}'],
                            ['id' => 3, 'expression' => '\frac{9{,}2}{0{,}5 - 2{,}8}'],
                            ['id' => 9, 'expression' => '\frac{7{,}5 + 3{,}5}{2{,}5}'],
                            ['id' => 15, 'expression' => '\frac{36}{4 \cdot 4{,}5}'],
                            ['id' => 21, 'expression' => '\frac{4{,}8 \cdot 0{,}4}{0{,}6}'],
                            ['id' => 4, 'expression' => '\frac{1{,}6}{2{,}5 + 0{,}7}'],
                            ['id' => 10, 'expression' => '\frac{6{,}9 - 4{,}1}{0{,}2}'],
                            ['id' => 16, 'expression' => '\frac{21}{17{,}5 \cdot 0{,}8}'],
                            ['id' => 22, 'expression' => '\frac{8{,}8 \cdot 0{,}8}{4{,}4}'],
                            ['id' => 5, 'expression' => '\frac{5{,}6}{1{,}9 + 2{,}1}'],
                            ['id' => 11, 'expression' => '\frac{1{,}7 + 3{,}8}{2{,}2}'],
                            ['id' => 17, 'expression' => '\frac{22}{4{,}4 \cdot 2{,}5}'],
                            ['id' => 23, 'expression' => '\frac{0{,}3 \cdot 7{,}5}{0{,}5}'],
                            ['id' => 6, 'expression' => '\frac{9{,}4}{4{,}1 + 5{,}3}'],
                            ['id' => 12, 'expression' => '\frac{7{,}2 - 6{,}1}{2{,}2}'],
                            ['id' => 18, 'expression' => '\frac{7}{12{,}5 \cdot 1{,}4}'],
                            ['id' => 24, 'expression' => '\frac{5{,}6 \cdot 0{,}3}{0{,}8}'],
                        ]
                    ],
                    // Задание 5 - Дроби с единицей
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{0{,}9}{1 + \frac{1}{5}}'],
                            ['id' => 2, 'expression' => '\frac{2{,}6}{1 - \frac{1}{14}}'],
                            ['id' => 3, 'expression' => '\frac{1{,}3}{1 + \frac{1}{12}}'],
                            ['id' => 4, 'expression' => '\frac{1{,}2}{1 - \frac{1}{3}}'],
                            ['id' => 5, 'expression' => '\frac{0{,}6}{1 + \frac{1}{2}}'],
                            ['id' => 6, 'expression' => '\frac{0{,}8}{1 - \frac{1}{9}}'],
                        ]
                    ],
                    // Задание 6 - Выражения с отрицательными числами
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-7 \cdot (-4{,}7) - 6{,}8'],
                            ['id' => 7, 'expression' => '-0{,}8 \cdot (-10)^2 - 95'],
                            ['id' => 13, 'expression' => '30 - 0{,}8 \cdot (-10)^2'],
                            ['id' => 2, 'expression' => '-13 \cdot (-9{,}3) - 7{,}8'],
                            ['id' => 8, 'expression' => '0{,}7 \cdot (-10)^3 - 20'],
                            ['id' => 14, 'expression' => '80 + 0{,}4 \cdot (-10)^3'],
                            ['id' => 3, 'expression' => '-12 \cdot (-8{,}6) - 9{,}4'],
                            ['id' => 9, 'expression' => '-0{,}2 \cdot (-10)^2 + 55'],
                            ['id' => 15, 'expression' => '55 + 0{,}2 \cdot (-10)^2'],
                            ['id' => 4, 'expression' => '7{,}6 - 8 \cdot (-5{,}2)'],
                            ['id' => 10, 'expression' => '0{,}9 \cdot (-10)^3 + 50'],
                            ['id' => 16, 'expression' => '-60 + 0{,}4 \cdot (-10)^2'],
                            ['id' => 5, 'expression' => '6{,}8 - 11 \cdot (-6{,}1)'],
                            ['id' => 11, 'expression' => '-0{,}7 \cdot (-10)^2 - 120'],
                            ['id' => 17, 'expression' => '-80 + 0{,}3 \cdot (-10)^3'],
                            ['id' => 6, 'expression' => '5{,}3 - 9 \cdot (-4{,}4)'],
                            ['id' => 12, 'expression' => '0{,}6 \cdot (-10)^3 + 50'],
                            ['id' => 18, 'expression' => '-45 + 0{,}5 \cdot (-10)^2'],
                        ]
                    ],
                    // Задание 7 - Степени 10
                    [
                        'number' => 7,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(2{,}6 \cdot 10^{-2}) \cdot (9 \cdot 10^{-3})'],
                            ['id' => 7, 'expression' => '(7 \cdot 10^3)^2 \cdot (16 \cdot 10^{-4})'],
                            ['id' => 2, 'expression' => '(1{,}6 \cdot 10^{-5}) \cdot (6 \cdot 10^{-2})'],
                            ['id' => 8, 'expression' => '(2 \cdot 10^2)^4 \cdot (19 \cdot 10^{-6})'],
                            ['id' => 3, 'expression' => '(1{,}7 \cdot 10^{-3}) \cdot (5 \cdot 10^{-4})'],
                            ['id' => 9, 'expression' => '(8 \cdot 10^2)^2 \cdot (3 \cdot 10^{-2})'],
                            ['id' => 4, 'expression' => '(2{,}1 \cdot 10^{-2}) \cdot (2 \cdot 10^{-2})'],
                            ['id' => 10, 'expression' => '(9 \cdot 10^{-2})^2 \cdot (11 \cdot 10^5)'],
                            ['id' => 5, 'expression' => '(2{,}2 \cdot 10^{-2}) \cdot (3 \cdot 10^{-4})'],
                            ['id' => 11, 'expression' => '(16 \cdot 10^{-2})^2 \cdot (13 \cdot 10^4)'],
                            ['id' => 6, 'expression' => '(1{,}2 \cdot 10^{-3}) \cdot (7 \cdot 10^{-2})'],
                            ['id' => 12, 'expression' => '(14 \cdot 10^{-2})^2 \cdot (12 \cdot 10^3)'],
                        ]
                    ],
                    // Задание 8 - Комбинированные степени
                    [
                        'number' => 8,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '0{,}7 \cdot (-10)^3 - 4 \cdot (-10)^2 - 63'],
                            ['id' => 4, 'expression' => '-0{,}7 \cdot (-10)^4 - 8 \cdot (-10)^2 - 26'],
                            ['id' => 2, 'expression' => '-0{,}4 \cdot (-10)^4 + 3 \cdot (-10)^2 - 98'],
                            ['id' => 5, 'expression' => '0{,}4 \cdot (-10)^3 + 7 \cdot (-10)^2 + 64'],
                            ['id' => 3, 'expression' => '0{,}8 \cdot (-10)^4 + 3 \cdot (-10)^3 + 78'],
                            ['id' => 6, 'expression' => '-0{,}3 \cdot (-10)^4 + 4 \cdot (-10)^2 - 59'],
                        ]
                    ],
                    // Задание 9 - Произведение десятичных
                    [
                        'number' => 9,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '0{,}0006 \cdot 6 \cdot 600000'],
                            ['id' => 4, 'expression' => '0{,}005 \cdot 0{,}5 \cdot 50'],
                            ['id' => 2, 'expression' => '0{,}007 \cdot 0{,}7 \cdot 70'],
                            ['id' => 5, 'expression' => '0{,}003 \cdot 0{,}0003 \cdot 300'],
                            ['id' => 3, 'expression' => '0{,}0008 \cdot 0{,}008 \cdot 80000'],
                            ['id' => 6, 'expression' => '0{,}004 \cdot 0{,}04 \cdot 40000'],
                        ]
                    ],
                    // Задание 10 - Степени с основаниями
                    [
                        'number' => 10,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-0{,}2 \cdot (-7)^4 - 1 \cdot (-7)^3 - 13'],
                            ['id' => 4, 'expression' => '0{,}5 \cdot (-6)^4 + 2 \cdot (-6)^2 - 30'],
                            ['id' => 2, 'expression' => '-0{,}9 \cdot (-2)^3 + 2{,}9 \cdot (-2)^2 - 22'],
                            ['id' => 5, 'expression' => '-1{,}1 \cdot (-3)^4 - 0{,}9 \cdot (-3)^3 - 15'],
                            ['id' => 3, 'expression' => '0{,}1 \cdot (-8)^3 + 0{,}2 \cdot (-8)^2 - 25'],
                            ['id' => 6, 'expression' => '0{,}2 \cdot (-4)^3 + 3 \cdot (-4)^2 - 17'],
                        ]
                    ],
                    // Задание 11 - Десятичные суммы
                    [
                        'number' => 11,
                        'instruction' => 'Запишите десятичную дробь, равную сумме',
                        'tasks' => [
                            ['id' => 1, 'expression' => '1 \cdot 10^{-1} + 7 \cdot 10^{-3} + 2 \cdot 10^{-4}'],
                            ['id' => 4, 'expression' => '8 \cdot 10^0 + 9 \cdot 10^{-2} + 3 \cdot 10^{-4}'],
                            ['id' => 2, 'expression' => '9 \cdot 10^1 + 3 \cdot 10^{-3} + 8 \cdot 10^{-4}'],
                            ['id' => 5, 'expression' => '6 \cdot 10^1 + 7 \cdot 10^{-2} + 5 \cdot 10^{-3}'],
                            ['id' => 3, 'expression' => '2 \cdot 10^0 + 6 \cdot 10^{-1} + 4 \cdot 10^{-3}'],
                            ['id' => 6, 'expression' => '5 \cdot 10^{-1} + 6 \cdot 10^{-2} + 4 \cdot 10^{-4}'],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    // Задание 1
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\left(\frac{1}{17} + 1\frac{1}{4}\right) : \frac{1}{34}'],
                            ['id' => 3, 'expression' => '\left(\frac{7}{9} + 1\frac{4}{5}\right) : \frac{1}{18}'],
                            ['id' => 5, 'expression' => '\left(\frac{13}{24} + 1\frac{1}{15}\right) : \frac{1}{24}'],
                            ['id' => 2, 'expression' => '\left(\frac{3}{4} - 2\frac{9}{10}\right) : \frac{1}{12}'],
                            ['id' => 4, 'expression' => '\left(\frac{4}{11} - 2\frac{1}{4}\right) : \frac{1}{22}'],
                            ['id' => 6, 'expression' => '\left(\frac{15}{26} - 2\frac{3}{4}\right) : \frac{1}{26}'],
                        ]
                    ],
                    // Задание 2
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{\frac{1}{20} + \frac{1}{12}}{\frac{1}{27}}'],
                            ['id' => 3, 'expression' => '\frac{\frac{1}{18} + \frac{1}{45}}{\frac{5}{27}}'],
                            ['id' => 5, 'expression' => '\frac{\frac{1}{28} + \frac{1}{42}}{\frac{1}{21}}'],
                            ['id' => 2, 'expression' => '\frac{\frac{1}{12} - \frac{1}{21}}{\frac{1}{70}}'],
                            ['id' => 4, 'expression' => '\frac{\frac{1}{72} - \frac{1}{88}}{\frac{5}{99}}'],
                            ['id' => 6, 'expression' => '\frac{\frac{1}{40} - \frac{1}{65}}{\frac{1}{78}}'],
                        ]
                    ],
                    // Задание 3
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '1{,}9 - 3{,}5 \cdot 7{,}2'],
                            ['id' => 3, 'expression' => '5{,}1 + 2{,}8 \cdot 2{,}5'],
                            ['id' => 2, 'expression' => '-9{,}2 - 0{,}4 \cdot 6{,}5'],
                            ['id' => 4, 'expression' => '-3{,}6 + 7{,}2 \cdot 1{,}5'],
                        ]
                    ],
                    // Задание 4
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{3}{16} : \left(-\frac{5}{56}\right) + 3{,}8'],
                            ['id' => 3, 'expression' => '-\frac{14}{23} : \frac{35}{46} + 2{,}9'],
                            ['id' => 2, 'expression' => '\frac{7}{18} : \left(-\frac{10}{27}\right) - 2{,}4'],
                            ['id' => 4, 'expression' => '-\frac{15}{58} : \frac{3}{29} - 5{,}63'],
                        ]
                    ],
                    // Задание 5 - Несократимые дроби
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения. Представьте результат в виде несократимой обыкновенной дроби. В ответ запишите числитель этой дроби.',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{2}{5} + \frac{3}{11}'],
                            ['id' => 4, 'expression' => '\frac{1}{45} + \frac{5}{9}'],
                            ['id' => 7, 'expression' => '3\frac{1}{12} - 2\frac{1}{4}'],
                            ['id' => 10, 'expression' => '9\frac{2}{15} - 8\frac{32}{33}'],
                            ['id' => 2, 'expression' => '\frac{7}{13} + \frac{1}{3}'],
                            ['id' => 5, 'expression' => '\frac{3}{20} + \frac{7}{36}'],
                            ['id' => 8, 'expression' => '5\frac{1}{15} - 4\frac{2}{5}'],
                            ['id' => 11, 'expression' => '2\frac{3}{28} - 1\frac{17}{36}'],
                            ['id' => 3, 'expression' => '\frac{5}{7} + \frac{4}{21}'],
                            ['id' => 6, 'expression' => '\frac{2}{45} + \frac{9}{35}'],
                            ['id' => 9, 'expression' => '7\frac{1}{18} - 6\frac{13}{14}'],
                            ['id' => 12, 'expression' => '6\frac{2}{21} - 5\frac{31}{33}'],
                        ]
                    ],
                    // Задание 6 - Ещё несократимые дроби
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения. Представьте результат в виде несократимой обыкновенной дроби. В ответ запишите числитель этой дроби.',
                        'tasks' => [
                            ['id' => 1, 'expression' => '1\frac{19}{29} \cdot \frac{7}{48}'],
                            ['id' => 5, 'expression' => '\frac{7}{12} : 2\frac{1}{4}'],
                            ['id' => 9, 'expression' => '\frac{1}{15} + 4\frac{4}{5} \cdot \frac{2}{21}'],
                            ['id' => 13, 'expression' => '4\frac{25}{27} - \frac{3}{38} \cdot \frac{5}{22}'],
                            ['id' => 2, 'expression' => '1\frac{13}{58} \cdot \frac{9}{71}'],
                            ['id' => 6, 'expression' => '\frac{9}{14} : 1\frac{4}{7}'],
                            ['id' => 10, 'expression' => '\frac{3}{20} + 3\frac{3}{4} \cdot \frac{1}{27}'],
                            ['id' => 14, 'expression' => '6\frac{24}{35} - \frac{1}{9} \cdot \frac{4}{15}'],
                            ['id' => 3, 'expression' => '1\frac{15}{34} \cdot \frac{17}{49}'],
                            ['id' => 7, 'expression' => '\frac{8}{11} : 2\frac{2}{5}'],
                            ['id' => 11, 'expression' => '\frac{1}{14} + 2\frac{1}{12} \cdot \frac{2}{15}'],
                            ['id' => 15, 'expression' => '2\frac{39}{40} - \frac{2}{7} \cdot \frac{3}{28}'],
                            ['id' => 4, 'expression' => '1\frac{11}{45} \cdot \frac{25}{56}'],
                            ['id' => 8, 'expression' => '\frac{6}{13} : 1\frac{1}{8}'],
                            ['id' => 12, 'expression' => '\frac{10}{21} + 2\frac{2}{15} \cdot \frac{3}{14}'],
                            ['id' => 16, 'expression' => '5\frac{25}{28} - \frac{4}{45} \cdot \frac{5}{39}'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 07
     */
    public function topic07()
    {
        $blocks = $this->getAllBlocksData07();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic07', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 07 - Числа, координатная прямая
     */
    protected function getAllBlocksData07(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // Задание 1 - Утверждения для числа a
                    [
                        'number' => 1,
                        'instruction' => 'На координатной прямой отмечено число a. Какое из утверждений для этого числа является верным?',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-000.png', 'options' => ['$a - 6 < 0$', '$6 - a > 0$', '$a - 7 > 0$', '$8 - a < 0$']],
                            ['id' => 2, 'image' => 'img-001.png', 'options' => ['$5 - a < 0$', '$a - 6 > 0$', '$a - 5 < 0$', '$4 - a > 0$']],
                            ['id' => 3, 'image' => 'img-002.png', 'options' => ['$a - 4 < 0$', '$a - 6 > 0$', '$6 - a > 0$', '$7 - a < 0$']],
                            ['id' => 4, 'image' => 'img-003.png', 'options' => ['$8 - a > 0$', '$8 - a < 0$', '$a - 7 < 0$', '$a - 9 > 0$']],
                            ['id' => 5, 'image' => 'img-004.png', 'options' => ['$4 - a > 0$', '$a - 7 < 0$', '$a - 8 > 0$', '$8 - a < 0$']],
                            ['id' => 6, 'image' => 'img-005.png', 'options' => ['$4 - a > 0$', '$a - 4 < 0$', '$a - 3 < 0$', '$6 - a > 0$']],
                        ]
                    ],
                    // Задание 2 - Два числа x и y на прямой
                    [
                        'number' => 2,
                        'instruction' => 'На координатной прямой отмечены числа. Какое из приведённых утверждений для этих чисел верно?',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-006.png', 'options' => ['$x + y < 0$', '$xy < 0$', '$y - x > 0$', '$x^2 y > 0$']],
                            ['id' => 2, 'image' => 'img-007.png', 'options' => ['$a + b > 0$', '$a^2 b < 0$', '$ab > 0$', '$a - b < 0$']],
                            ['id' => 3, 'image' => 'img-008.png', 'options' => ['$xy > 0$', '$x^2 y < 0$', '$x + y > 0$', '$x - y < 0$']],
                            ['id' => 4, 'image' => 'img-009.png', 'options' => ['$a + b < 0$', '$a - b > 0$', '$ab^2 > 0$', '$ab < 0$']],
                            ['id' => 5, 'image' => 'img-010.png', 'options' => ['$xy^2 > 0$', '$x - y < 0$', '$x + y > 0$', '$xy > 0$']],
                            ['id' => 6, 'image' => 'img-011.png', 'options' => ['$ab^2 > 0$', '$a - b < 0$', '$ab > 0$', '$a + b > 0$']],
                        ]
                    ],
                    // Задание 3 - Разности q-p, q-r, r-p положительна
                    [
                        'number' => 3,
                        'instruction' => 'На координатной прямой отмечены числа p, q и r. Какая из разностей q − p, q − r, r − p положительна?',
                        'type' => 'simple_choice',
                        'image' => 'img-012.png',
                        'options' => ['$q - p$', '$q - r$', '$r - p$', 'невозможно определить'],
                    ],
                    // Задание 4 - Разности z-x, y-z, x-y отрицательна
                    [
                        'number' => 4,
                        'instruction' => 'На координатной прямой отмечены числа x, y и z. Какая из разностей z − x, y − z, x − y отрицательна?',
                        'type' => 'simple_choice',
                        'image' => 'img-013.png',
                        'options' => ['$z - x$', '$y - z$', '$x - y$', 'невозможно определить'],
                    ],
                    // Задание 5 - Разности a-b, a-c, c-b положительна
                    [
                        'number' => 5,
                        'instruction' => 'На координатной прямой отмечены числа a, b и c. Какая из разностей a − b, a − c, c − b положительна?',
                        'type' => 'simple_choice',
                        'image' => 'img-014.png',
                        'options' => ['$a - b$', '$a - c$', '$c - b$', 'невозможно определить'],
                    ],
                    // Задание 6 - Разности q-p, q-r, r-p отрицательна
                    [
                        'number' => 6,
                        'instruction' => 'На координатной прямой отмечены числа p, q и r. Какая из разностей q − p, q − r, r − p отрицательна?',
                        'type' => 'simple_choice',
                        'image' => 'img-015.png',
                        'options' => ['$q - p$', '$q - r$', '$r - p$', 'невозможно определить'],
                    ],
                    // Задание 7 - Разности z-x, y-z, x-y положительна
                    [
                        'number' => 7,
                        'instruction' => 'На координатной прямой отмечены числа x, y и z. Какая из разностей z − x, y − z, x − y положительна?',
                        'type' => 'simple_choice',
                        'image' => 'img-016.png',
                        'options' => ['$z - x$', '$y - z$', '$x - y$', 'невозможно определить'],
                    ],
                    // Задание 8 - Разности a-b, a-c, c-b отрицательна
                    [
                        'number' => 8,
                        'instruction' => 'На координатной прямой отмечены числа a, b и c. Какая из разностей a − b, a − c, c − b отрицательна?',
                        'type' => 'simple_choice',
                        'image' => 'img-017.png',
                        'options' => ['$a - b$', '$a - c$', '$c - b$', 'невозможно определить'],
                    ],
                    // Задание 9 - Точка соответствует числу
                    [
                        'number' => 9,
                        'instruction' => 'На координатной прямой отмечены точки A, B, C и D. Одна из них соответствует данному числу. Какая это точка?',
                        'type' => 'fraction_choice',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-018.png', 'expression' => '\frac{63}{11}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'image' => 'img-019.png', 'expression' => '\frac{116}{15}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'image' => 'img-020.png', 'expression' => '\frac{107}{13}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'image' => 'img-021.png', 'expression' => '\frac{100}{19}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 5, 'image' => 'img-022.png', 'expression' => '\frac{132}{17}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 6, 'image' => 'img-023.png', 'expression' => '\frac{92}{9}', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 10 - Между какими целыми числами
                    [
                        'number' => 10,
                        'instruction' => 'Между какими целыми числами заключено число...',
                        'type' => 'interval_choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{130}{11}', 'options' => ['10 и 11', '11 и 12', '12 и 13', '13 и 14']],
                            ['id' => 2, 'expression' => '\frac{124}{15}', 'options' => ['8 и 9', '9 и 10', '10 и 11', '11 и 12']],
                            ['id' => 3, 'expression' => '\frac{230}{19}', 'options' => ['11 и 12', '12 и 13', '13 и 14', '14 и 15']],
                            ['id' => 4, 'expression' => '\frac{140}{17}', 'options' => ['5 и 6', '6 и 7', '7 и 8', '8 и 9']],
                            ['id' => 5, 'expression' => '\frac{110}{13}', 'options' => ['8 и 9', '9 и 10', '10 и 11', '11 и 12']],
                            ['id' => 6, 'expression' => '\frac{131}{12}', 'options' => ['10 и 11', '11 и 12', '12 и 13', '13 и 14']],
                        ]
                    ],
                    // Задание 11 - Промежуток принадлежности дроби
                    [
                        'number' => 11,
                        'instruction' => 'Какому из данных промежутков принадлежит число...',
                        'type' => 'interval_choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{2}{9}', 'options' => ['[0,1; 0,2]', '[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]']],
                            ['id' => 2, 'expression' => '\frac{7}{11}', 'options' => ['[0,4; 0,5]', '[0,5; 0,6]', '[0,6; 0,7]', '[0,7; 0,8]']],
                            ['id' => 3, 'expression' => '\frac{5}{13}', 'options' => ['[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]', '[0,5; 0,6]']],
                            ['id' => 4, 'expression' => '\frac{3}{7}', 'options' => ['[0,1; 0,2]', '[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]']],
                            ['id' => 5, 'expression' => '\frac{5}{11}', 'options' => ['[0,2; 0,3]', '[0,3; 0,4]', '[0,4; 0,5]', '[0,5; 0,6]']],
                            ['id' => 6, 'expression' => '\frac{9}{13}', 'options' => ['[0,5; 0,6]', '[0,6; 0,7]', '[0,7; 0,8]', '[0,8; 0,9]']],
                        ]
                    ],
                    // Задание 12 - Число между двумя дробями
                    [
                        'number' => 12,
                        'instruction' => 'Какое из следующих чисел заключено между числами...',
                        'type' => 'between_fractions',
                        'tasks' => [
                            ['id' => 1, 'left' => '\frac{8}{3}', 'right' => '\frac{11}{4}', 'options' => ['2,7', '2,8', '2,9', '3']],
                            ['id' => 2, 'left' => '\frac{8}{13}', 'right' => '\frac{12}{17}', 'options' => ['0,6', '0,7', '0,8', '0,9']],
                            ['id' => 3, 'left' => '\frac{15}{11}', 'right' => '\frac{13}{9}', 'options' => ['1,4', '1,5', '1,6', '1,7']],
                            ['id' => 4, 'left' => '\frac{17}{15}', 'right' => '\frac{16}{13}', 'options' => ['1,2', '1,3', '1,4', '1,5']],
                            ['id' => 5, 'left' => '\frac{19}{8}', 'right' => '\frac{17}{7}', 'options' => ['2,3', '2,4', '2,5', '2,6']],
                            ['id' => 6, 'left' => '\frac{18}{17}', 'right' => '\frac{17}{15}', 'options' => ['1,0', '1,1', '1,2', '1,3']],
                        ]
                    ],
                    // Задание 13 - Принадлежность отрезку
                    [
                        'number' => 13,
                        'instruction' => 'Какое из данных чисел принадлежит отрезку...',
                        'type' => 'segment_choice',
                        'tasks' => [
                            ['id' => 1, 'segment' => '[3; 4]', 'options' => ['\frac{47}{14}', '\frac{57}{14}', '\frac{61}{14}', '\frac{65}{14}']],
                            ['id' => 2, 'segment' => '[4; 5]', 'options' => ['\frac{58}{17}', '\frac{72}{17}', '\frac{87}{17}', '\frac{91}{17}']],
                            ['id' => 3, 'segment' => '[7; 8]', 'options' => ['\frac{57}{9}', '\frac{62}{9}', '\frac{70}{9}', '\frac{79}{9}']],
                            ['id' => 4, 'segment' => '[6; 7]', 'options' => ['\frac{67}{12}', '\frac{71}{12}', '\frac{83}{12}', '\frac{91}{12}']],
                            ['id' => 5, 'segment' => '[5; 6]', 'options' => ['\frac{68}{13}', '\frac{79}{13}', '\frac{82}{13}', '\frac{89}{13}']],
                            ['id' => 6, 'segment' => '[4; 5]', 'options' => ['\frac{49}{15}', '\frac{52}{15}', '\frac{58}{15}', '\frac{71}{15}']],
                        ]
                    ],
                    // Задание 14 - Точка A соответствует числу (дроби)
                    [
                        'number' => 14,
                        'instruction' => 'Одно из чисел отмечено на прямой точкой А. Какое это число?',
                        'type' => 'fraction_options',
                        'tasks' => [
                            ['id' => 1, 'options' => ['\frac{3}{11}', '\frac{7}{11}', '\frac{8}{11}', '\frac{13}{11}']],
                            ['id' => 2, 'options' => ['\frac{10}{17}', '\frac{11}{17}', '\frac{13}{17}', '\frac{14}{17}']],
                            ['id' => 3, 'options' => ['\frac{3}{13}', '\frac{9}{13}', '\frac{10}{13}', '\frac{12}{13}']],
                            ['id' => 4, 'options' => ['\frac{10}{23}', '\frac{11}{23}', '\frac{13}{23}', '\frac{14}{23}']],
                            ['id' => 5, 'options' => ['\frac{2}{7}', '\frac{4}{7}', '\frac{10}{7}', '\frac{11}{7}']],
                            ['id' => 6, 'options' => ['\frac{6}{23}', '\frac{7}{23}', '\frac{11}{23}', '\frac{12}{23}']],
                        ]
                    ],
                    // Задание 15 - Точка A соответствует числу (большие дроби)
                    [
                        'number' => 15,
                        'instruction' => 'Одно из чисел отмечено на прямой точкой A. Какое это число?',
                        'type' => 'fraction_options',
                        'tasks' => [
                            ['id' => 1, 'options' => ['\frac{55}{19}', '\frac{64}{19}', '\frac{72}{19}', '\frac{79}{19}']],
                            ['id' => 2, 'options' => ['\frac{71}{15}', '\frac{79}{15}', '\frac{86}{15}', '\frac{92}{15}']],
                            ['id' => 3, 'options' => ['\frac{73}{22}', '\frac{83}{22}', '\frac{93}{22}', '\frac{113}{22}']],
                            ['id' => 4, 'options' => ['\frac{58}{13}', '\frac{69}{13}', '\frac{76}{13}', '\frac{83}{13}']],
                            ['id' => 5, 'options' => ['\frac{75}{23}', '\frac{85}{23}', '\frac{97}{23}', '\frac{110}{23}']],
                            ['id' => 6, 'options' => ['\frac{31}{11}', '\frac{37}{11}', '\frac{41}{11}', '\frac{47}{11}']],
                        ]
                    ],
                    // Задание 16 - Десятичные числа на прямой
                    [
                        'number' => 16,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам. Какой точке соответствует указанное число?',
                        'type' => 'decimal_choice',
                        'tasks' => [
                            ['id' => 1, 'numbers' => '0,0137; 0,103; 0,03; 0,021', 'target' => '0,03', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'numbers' => '−0,502; 0,25; 0,205; 0,52', 'target' => '0,205', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'numbers' => '0,508; 0,85; −0,05; 0,058', 'target' => '0,058', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'numbers' => '−0,39; −0,09; −0,93; 0,03', 'target' => '−0,09', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 5, 'numbers' => '0,271; −0,112; 0,041; −0,267', 'target' => '0,271', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 6, 'numbers' => '−0,201; −0,012; −0,304; 0,021', 'target' => '−0,304', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 17 - Корни на прямой
                    [
                        'number' => 17,
                        'instruction' => 'На координатной прямой отмечены точки A, B, C, D. Одна из них соответствует данному числу. Какая это точка?',
                        'type' => 'sqrt_choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{86}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'expression' => '\sqrt{46}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'expression' => '\sqrt{68}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'expression' => '\sqrt{85}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 5, 'expression' => '\sqrt{39}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 6, 'expression' => '\sqrt{76}', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 18 - Корень между целыми
                    [
                        'number' => 18,
                        'instruction' => 'Между какими целыми числами заключено число...',
                        'type' => 'sqrt_interval',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{89}', 'options' => ['4 и 5', '29 и 31', '9 и 10', '88 и 90']],
                            ['id' => 2, 'expression' => '\sqrt{27}', 'options' => ['2 и 3', '5 и 6', '12 и 14', '26 и 28']],
                            ['id' => 3, 'expression' => '\sqrt{58}', 'options' => ['19 и 21', '57 и 59', '3 и 4', '7 и 8']],
                            ['id' => 4, 'expression' => '\sqrt{73}', 'options' => ['8 и 9', '72 и 74', '24 и 26', '4 и 5']],
                            ['id' => 5, 'expression' => '\sqrt{30}', 'options' => ['11 и 13', '5 и 6', '2 и 3', '29 и 31']],
                            ['id' => 6, 'expression' => '\sqrt{56}', 'options' => ['55 и 57', '3 и 4', '19 и 21', '7 и 8']],
                        ]
                    ],
                    // Задание 19 - Корень принадлежит промежутку
                    [
                        'number' => 19,
                        'instruction' => 'Какое из данных чисел принадлежит промежутку...',
                        'type' => 'sqrt_segment',
                        'tasks' => [
                            ['id' => 1, 'segment' => '[5; 6]', 'options' => ['\sqrt{5}', '\sqrt{6}', '\sqrt{24}', '\sqrt{32}']],
                            ['id' => 2, 'segment' => '[6; 7]', 'options' => ['\sqrt{6}', '\sqrt{7}', '\sqrt{38}', '\sqrt{50}']],
                            ['id' => 3, 'segment' => '[7; 8]', 'options' => ['\sqrt{7}', '\sqrt{8}', '\sqrt{62}', '\sqrt{72}']],
                            ['id' => 4, 'segment' => '[6; 7]', 'options' => ['\sqrt{6}', '\sqrt{7}', '\sqrt{40}', '\sqrt{51}']],
                            ['id' => 5, 'segment' => '[5; 6]', 'options' => ['\sqrt{5}', '\sqrt{6}', '\sqrt{28}', '\sqrt{41}']],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // Задание 1 - Сравнение x и y
                    [
                        'number' => 1,
                        'instruction' => 'На координатной прямой отмечены числа. Какое из следующих утверждений верно?',
                        'type' => 'comparison',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$x < y$ и $|x| < |y|$', '$x < y$ и $|x| > |y|$', '$x > y$ и $|x| > |y|$', '$x > y$ и $|x| < |y|$']],
                            ['id' => 2, 'options' => ['$a < b$ и $|a| < |b|$', '$a < b$ и $|a| > |b|$', '$a > b$ и $|a| > |b|$', '$a > b$ и $|a| < |b|$']],
                        ]
                    ],
                    // Задание 2 - Наименьшее среди степеней
                    [
                        'number' => 2,
                        'instruction' => 'На координатной прямой отмечены числа. Какое из перечисленных чисел наименьшее?',
                        'type' => 'power_choice',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$a$', '$a^2$', '$a^3$', 'нет данных']],
                            ['id' => 2, 'options' => ['$a^2$', '$a^3$', '$a^4$', 'нет данных']],
                            ['id' => 3, 'options' => ['$a^2$', '$a^3$', '$a^4$', 'нет данных']],
                            ['id' => 4, 'options' => ['$a$', '$a^2$', '$a^3$', 'нет данных']],
                        ]
                    ],
                    // Задание 3 - Сравнение дробей
                    [
                        'number' => 3,
                        'instruction' => 'Сравните числа, если a, b — положительные числа и...',
                        'type' => 'compare_fractions',
                        'tasks' => [
                            ['id' => 1, 'condition' => '$a < b$', 'question' => '\frac{2}{a} \text{ и } \frac{2}{b}', 'options' => ['$\frac{2}{a} > \frac{2}{b}$', '$\frac{2}{a} < \frac{2}{b}$', '$\frac{2}{a} = \frac{2}{b}$', 'невозможно']],
                            ['id' => 2, 'condition' => '$a > b$', 'question' => '\frac{1}{a} \text{ и } \frac{1}{b}', 'options' => ['$\frac{1}{a} > \frac{1}{b}$', '$\frac{1}{a} < \frac{1}{b}$', '$\frac{1}{a} = \frac{1}{b}$', 'невозможно']],
                        ]
                    ],
                    // Задание 4 - Неверные утверждения
                    [
                        'number' => 4,
                        'instruction' => 'Какие из данных утверждений неверны, если a < c?',
                        'type' => 'false_statements',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$a - 49 < c - 49$', '$a + 23 < c + 23$', '$-\frac{a}{26} < -\frac{c}{26}$', '$\frac{a}{5} < \frac{c}{5}$']],
                            ['id' => 2, 'options' => ['$a - 24 < c - 24$', '$a + 33 < c + 33$', '$-\frac{a}{5} < -\frac{c}{5}$', '$\frac{a}{17} < \frac{c}{17}$']],
                        ]
                    ],
                    // Задание 5 - Расположите в порядке возрастания
                    [
                        'number' => 5,
                        'instruction' => 'Расположите в порядке возрастания числа.',
                        'type' => 'ordering',
                        'tasks' => [
                            ['id' => 1, 'options' => ['$\frac{1}{a}, 1, \frac{1}{b}$', '$1, \frac{1}{b}, \frac{1}{a}$', '$\frac{1}{a}, \frac{1}{b}, 1$', '$\frac{1}{b}, \frac{1}{a}, 1$']],
                            ['id' => 2, 'options' => ['$\frac{1}{b}, 1, \frac{1}{a}$', '$\frac{1}{a}, 1, \frac{1}{b}$', '$\frac{1}{a}, \frac{1}{b}, 1$', '$\frac{1}{b}, \frac{1}{a}, 1$']],
                            ['id' => 3, 'options' => ['$1, \frac{1}{a}, \frac{1}{c}$', '$\frac{1}{c}, \frac{1}{a}, 1$', '$\frac{1}{a}, \frac{1}{c}, 1$', '$1, \frac{1}{c}, \frac{1}{a}$']],
                            ['id' => 4, 'options' => ['$\frac{1}{x}, 1, \frac{1}{y}$', '$\frac{1}{y}, 1, \frac{1}{x}$', '$\frac{1}{x}, \frac{1}{y}, 1$', '$1, \frac{1}{x}, \frac{1}{y}$']],
                        ]
                    ],
                    // Задание 6 - Какому числу соответствует точка
                    [
                        'number' => 6,
                        'instruction' => 'На координатной прямой точками отмечены числа. Какому числу соответствует точка?',
                        'type' => 'point_value',
                        'tasks' => [
                            ['id' => 1, 'point' => 'C', 'options' => ['\frac{4}{7}', '\frac{11}{5}', '2,6', '0,3']],
                            ['id' => 2, 'point' => 'D', 'options' => ['\frac{11}{7}', '\frac{3}{2}', '1,55', '1,7']],
                            ['id' => 3, 'point' => 'C', 'options' => ['\frac{8}{3}', '\frac{9}{4}', '2,55', '2,4']],
                            ['id' => 4, 'point' => 'D', 'options' => ['\frac{4}{13}', '\frac{5}{14}', '0,29', '0,3']],
                        ]
                    ],
                    // Задание 7 - Какая точка соответствует числу (простые дроби)
                    [
                        'number' => 7,
                        'instruction' => 'Одна из точек, отмеченных на координатной прямой, соответствует данному числу. Какая это точка?',
                        'type' => 'fraction_point',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{7}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 2, 'expression' => '\frac{8}{11}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 3, 'expression' => '\frac{2}{9}', 'options' => ['A', 'B', 'C', 'D']],
                            ['id' => 4, 'expression' => '\frac{10}{13}', 'options' => ['A', 'B', 'C', 'D']],
                        ]
                    ],
                    // Задание 8 - Промежуток для корня
                    [
                        'number' => 8,
                        'instruction' => 'Какому из данных промежутков принадлежит число...',
                        'type' => 'sqrt_interval',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{58}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 2, 'expression' => '\sqrt{27}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 3, 'expression' => '\sqrt{19}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 4, 'expression' => '\sqrt{63}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 5, 'expression' => '\sqrt{42}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                            ['id' => 6, 'expression' => '\sqrt{31}', 'options' => ['[4; 5]', '[5; 6]', '[6; 7]', '[7; 8]']],
                        ]
                    ],
                    // Задание 9 - Корень отмечен точкой A
                    [
                        'number' => 9,
                        'instruction' => 'Одно из чисел отмечено на прямой точкой A. Какое это число?',
                        'type' => 'sqrt_options',
                        'tasks' => [
                            ['id' => 1, 'options' => ['\sqrt{41}', '\sqrt{48}', '\sqrt{53}', '\sqrt{63}']],
                            ['id' => 2, 'options' => ['\sqrt{28}', '\sqrt{33}', '\sqrt{38}', '\sqrt{47}']],
                            ['id' => 3, 'options' => ['\sqrt{17}', '\sqrt{22}', '\sqrt{28}', '\sqrt{32}']],
                            ['id' => 4, 'options' => ['\sqrt{29}', '\sqrt{33}', '\sqrt{39}', '\sqrt{44}']],
                            ['id' => 5, 'options' => ['\sqrt{18}', '\sqrt{24}', '\sqrt{26}', '\sqrt{32}']],
                            ['id' => 6, 'options' => ['\sqrt{40}', '\sqrt{46}', '\sqrt{53}', '\sqrt{58}']],
                        ]
                    ],
                    // Задание 10 - Сколько целых чисел между
                    [
                        'number' => 10,
                        'instruction' => 'Сколько целых чисел расположено между...',
                        'type' => 'count_integers',
                        'tasks' => [
                            ['id' => 1, 'left' => '\sqrt{5}', 'right' => '\sqrt{95}'],
                            ['id' => 2, 'left' => '\sqrt{19}', 'right' => '\sqrt{133}'],
                            ['id' => 3, 'left' => '\sqrt{18}', 'right' => '\sqrt{78}'],
                            ['id' => 4, 'left' => '\sqrt{17}', 'right' => '\sqrt{114}'],
                            ['id' => 5, 'left' => '6^7', 'right' => '7^6'],
                            ['id' => 6, 'left' => '3^{14}', 'right' => '7^3'],
                            ['id' => 7, 'left' => '2^{10}', 'right' => '10^2'],
                            ['id' => 8, 'left' => '4^{11}', 'right' => '11^2'],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    // Задание 1 - Принадлежность отрезку (отрицательные)
                    [
                        'number' => 1,
                        'instruction' => 'Какое из данных чисел принадлежит отрезку...',
                        'type' => 'negative_segment',
                        'tasks' => [
                            ['id' => 1, 'segment' => '[−4; −3]', 'options' => ['-\frac{45}{19}', '-\frac{52}{19}', '-\frac{68}{19}', '-\frac{77}{19}']],
                            ['id' => 2, 'segment' => '[−7; −6]', 'options' => ['-\frac{68}{13}', '-\frac{82}{13}', '-\frac{92}{13}', '-\frac{101}{13}']],
                            ['id' => 3, 'segment' => '[−8; −7]', 'options' => ['-\frac{69}{11}', '-\frac{80}{11}', '-\frac{90}{11}', '-\frac{92}{11}']],
                            ['id' => 4, 'segment' => '[−9; −8]', 'options' => ['-\frac{46}{7}', '-\frac{53}{7}', '-\frac{55}{7}', '-\frac{61}{7}']],
                        ]
                    ],
                    // Задание 2 - Точка для дроби 3/10
                    [
                        'number' => 2,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $-\frac{3}{8}$; $\frac{3}{10}$; $-\frac{3}{7}$; $\frac{3}{14}$. Какой точке соответствует число $\frac{3}{10}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 3 - Точка для дроби 5/12
                    [
                        'number' => 3,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $-\frac{5}{6}$; $\frac{5}{12}$; $\frac{5}{6}$; $\frac{5}{10}$. Какой точке соответствует число $\frac{5}{12}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 4 - Точка для дроби -4/7
                    [
                        'number' => 4,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $-\frac{4}{5}$; $-\frac{4}{9}$; $\frac{4}{7}$; $-\frac{4}{7}$. Какой точке соответствует число $-\frac{4}{7}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 5 - Точка для дроби -2/9
                    [
                        'number' => 5,
                        'instruction' => 'На координатной прямой точки A, B, C и D соответствуют числам $\frac{2}{7}$; $\frac{2}{11}$; $-\frac{2}{11}$; $-\frac{2}{9}$. Какой точке соответствует число $-\frac{2}{9}$?',
                        'type' => 'simple_choice',
                        'options' => ['A', 'B', 'C', 'D'],
                    ],
                    // Задание 6 - Между какими целыми (отрицательные дроби)
                    [
                        'number' => 6,
                        'instruction' => 'Между какими целыми числами заключено число...',
                        'type' => 'negative_interval',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-\frac{134}{11}', 'options' => ['–11 и –10', '–12 и –11', '–13 и –12', '–14 и –13']],
                            ['id' => 2, 'expression' => '-\frac{104}{9}', 'options' => ['–12 и –11', '–13 и –12', '–14 и –13', '–15 и –14']],
                            ['id' => 3, 'expression' => '-\frac{111}{17}', 'options' => ['–6 и –5', '–7 и –6', '–8 и –7', '–9 и –8']],
                            ['id' => 4, 'expression' => '-\frac{152}{15}', 'options' => ['–8 и –7', '–9 и –8', '–10 и –9', '–11 и –10']],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Parse PDF file and return JSON
     */
    public function parsePdf(Request $request)
    {
        $pdfPath = storage_path('app/pdf/task_06.pdf');

        if (!file_exists($pdfPath)) {
            return response()->json([
                'error' => 'PDF file not found',
                'path' => $pdfPath,
            ], 404);
        }

        try {
            $parser = new PdfTaskParser();
            $blocks = $parser->parseTask06($pdfPath);

            return response()->json([
                'status' => 'ok',
                'blocks' => $blocks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display parsed tasks from PDF for topic 08
     */
    public function topic08()
    {
        $blocks = $this->getAllBlocksData08();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic08', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 08 - Квадратные корни и степени
     */
    protected function getAllBlocksData08(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // I) Иррациональные числа и выражения
                    // Задание 1 - Корни с параметрами
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{\frac{16a^{14}}{a^8}} \text{ при } a = 3'],
                            ['id' => 2, 'expression' => '\sqrt{\frac{36a^{21}}{a^{15}}} \text{ при } a = 2'],
                            ['id' => 3, 'expression' => '\sqrt{\frac{25a^{19}}{a^{11}}} \text{ при } a = 2'],
                            ['id' => 4, 'expression' => '\sqrt{\frac{64a^{17}}{a^{15}}} \text{ при } a = 7'],
                            ['id' => 5, 'expression' => '\sqrt{\frac{9a^{14}}{a^8}} \text{ при } a = 2'],
                            ['id' => 6, 'expression' => '\sqrt{\frac{16a^{12}}{a^{10}}} \text{ при } a = 5'],
                            ['id' => 7, 'expression' => '\sqrt{\frac{9a^{19}}{a^9}} \text{ при } a = 2'],
                            ['id' => 8, 'expression' => '\sqrt{\frac{4a^{16}}{a^{12}}} \text{ при } a = 5'],
                            ['id' => 9, 'expression' => '\sqrt{\frac{36x^4}{y^2}} \text{ при } x = 6, y = 9'],
                            ['id' => 10, 'expression' => '\sqrt{\frac{25x^2}{y^4}} \text{ при } x = 10, y = 5'],
                            ['id' => 11, 'expression' => '\sqrt{\frac{4x^2}{y^6}} \text{ при } x = 8, y = 2'],
                            ['id' => 12, 'expression' => '\sqrt{\frac{16x^4}{y^6}} \text{ при } x = 4, y = 2'],
                            ['id' => 13, 'expression' => '\sqrt{\frac{25x^4}{y^6}} \text{ при } x = 10, y = 5'],
                            ['id' => 14, 'expression' => '\sqrt{\frac{36x^2}{y^4}} \text{ при } x = 6, y = 2'],
                            ['id' => 15, 'expression' => '\sqrt{\frac{16x^8}{y^6}} \text{ при } x = 2, y = 4'],
                            ['id' => 16, 'expression' => '\sqrt{\frac{9x^4}{y^6}} \text{ при } x = 9, y = 3'],
                        ]
                    ],
                    // Задание 2 - Корни со степенями xy
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{\frac{1}{16}x^6y^4} \text{ при } x = 2, y = 5'],
                            ['id' => 2, 'expression' => '\sqrt{\frac{1}{25}x^8y^2} \text{ при } x = 3, y = 5'],
                            ['id' => 3, 'expression' => '\sqrt{\frac{1}{4}x^2y^8} \text{ при } x = 5, y = 2'],
                            ['id' => 4, 'expression' => '\sqrt{\frac{1}{9}x^4y^{10}} \text{ при } x = 3, y = 2'],
                            ['id' => 5, 'expression' => '\sqrt{\frac{1}{4}x^8y^4} \text{ при } x = 2, y = 3'],
                            ['id' => 6, 'expression' => '\sqrt{\frac{1}{25}x^4y^8} \text{ при } x = 5, y = 2'],
                            ['id' => 7, 'expression' => '\sqrt{\frac{1}{9}x^2y^6} \text{ при } x = 7, y = 3'],
                            ['id' => 8, 'expression' => '\sqrt{\frac{1}{16}x^{10}y^2} \text{ при } x = 2, y = 3'],
                        ]
                    ],
                    // Задание 3 - Корни с отрицательными числами
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{a^2 \cdot (-a)^2} \text{ при } a = 4'],
                            ['id' => 2, 'expression' => '\sqrt{a^6 \cdot (-a)^4} \text{ при } a = 2'],
                            ['id' => 3, 'expression' => '\sqrt{a^6 \cdot (-a)^2} \text{ при } a = 3'],
                            ['id' => 4, 'expression' => '\sqrt{a^2 \cdot (-a)^4} \text{ при } a = 4'],
                            ['id' => 5, 'expression' => '\sqrt{(-a)^4 \cdot a^2} \text{ при } a = 5'],
                            ['id' => 6, 'expression' => '\sqrt{(-a)^8 \cdot a^2} \text{ при } a = 2'],
                            ['id' => 7, 'expression' => '\sqrt{(-a)^2 \cdot a^4} \text{ при } a = 3'],
                            ['id' => 8, 'expression' => '\sqrt{(-a)^2 \cdot a^2} \text{ при } a = 5'],
                        ]
                    ],
                    // Задание 4 - Полные квадраты
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{a^2 + 8ab + 16b^2} \text{ при } a = 3\frac{3}{7}, b = \frac{1}{7}'],
                            ['id' => 2, 'expression' => '\sqrt{a^2 + 12ab + 36b^2} \text{ при } a = 7\frac{2}{5}, b = \frac{3}{5}'],
                            ['id' => 3, 'expression' => '\sqrt{a^2 + 10ab + 25b^2} \text{ при } a = 1\frac{6}{13}, b = \frac{4}{13}'],
                            ['id' => 4, 'expression' => '\sqrt{a^2 + 8ab + 16b^2} \text{ при } a = 3\frac{2}{3}, b = \frac{1}{3}'],
                            ['id' => 5, 'expression' => '\sqrt{9a^2 + 6ab + b^2} \text{ при } a = \frac{5}{13}, b = 6\frac{11}{13}'],
                            ['id' => 6, 'expression' => '\sqrt{16a^2 + 8ab + b^2} \text{ при } a = \frac{3}{11}, b = 5\frac{10}{11}'],
                            ['id' => 7, 'expression' => '\sqrt{25a^2 + 10ab + b^2} \text{ при } a = \frac{4}{9}, b = 3\frac{7}{9}'],
                            ['id' => 8, 'expression' => '\sqrt{36a^2 + 12ab + b^2} \text{ при } a = \frac{4}{5}, b = 8\frac{1}{5}'],
                            ['id' => 9, 'expression' => '\sqrt{a^2 - 6ab + 9b^2} \text{ при } a = 3, b = 6'],
                            ['id' => 10, 'expression' => '\sqrt{a^2 - 12ab + 36b^2} \text{ при } a = 8, b = 3'],
                            ['id' => 11, 'expression' => '\sqrt{a^2 - 8ab + 16b^2} \text{ при } a = 4, b = 3'],
                            ['id' => 12, 'expression' => '\sqrt{a^2 - 10ab + 25b^2} \text{ при } a = 7, b = 2'],
                            ['id' => 13, 'expression' => '\sqrt{a^2 + 10ab + 25b^2} \text{ при } a = 8, b = -2'],
                            ['id' => 14, 'expression' => '\sqrt{a^2 + 6ab + 9b^2} \text{ при } a = 5, b = -4'],
                            ['id' => 15, 'expression' => '\sqrt{a^2 + 12ab + 36b^2} \text{ при } a = 7, b = -3'],
                            ['id' => 16, 'expression' => '\sqrt{a^2 + 4ab + 4b^2} \text{ при } a = 2, b = -4'],
                        ]
                    ],
                    // Задание 5 - Упрощение радикалов
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(\sqrt{20} - \sqrt{5}) \cdot \sqrt{5}'],
                            ['id' => 2, 'expression' => '(\sqrt{18} - \sqrt{2}) \cdot \sqrt{2}'],
                            ['id' => 3, 'expression' => '(\sqrt{48} - \sqrt{3}) \cdot \sqrt{3}'],
                            ['id' => 4, 'expression' => '(\sqrt{50} + \sqrt{2}) \cdot \sqrt{2}'],
                            ['id' => 5, 'expression' => '(\sqrt{45} + \sqrt{5}) \cdot \sqrt{5}'],
                            ['id' => 6, 'expression' => '(\sqrt{27} + \sqrt{3}) \cdot \sqrt{3}'],
                            ['id' => 7, 'expression' => '\sqrt{5} \cdot \sqrt{18} \cdot \sqrt{10}'],
                            ['id' => 8, 'expression' => '\sqrt{7} \cdot \sqrt{12} \cdot \sqrt{21}'],
                            ['id' => 9, 'expression' => '\sqrt{2} \cdot \sqrt{45} \cdot \sqrt{10}'],
                            ['id' => 10, 'expression' => '\sqrt{7} \cdot \sqrt{45} \cdot \sqrt{35}'],
                            ['id' => 11, 'expression' => '\sqrt{11} \cdot \sqrt{32} \cdot \sqrt{22}'],
                            ['id' => 12, 'expression' => '\sqrt{13} \cdot \sqrt{18} \cdot \sqrt{26}'],
                        ]
                    ],
                    // Задание 6 - Дроби с радикалами
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{\sqrt{21} \cdot \sqrt{14}}{\sqrt{6}}'],
                            ['id' => 2, 'expression' => '\frac{\sqrt{35} \cdot \sqrt{21}}{\sqrt{15}}'],
                            ['id' => 3, 'expression' => '\frac{\sqrt{22} \cdot \sqrt{33}}{\sqrt{6}}'],
                            ['id' => 4, 'expression' => '\frac{\sqrt{65} \cdot \sqrt{13}}{\sqrt{5}}'],
                            ['id' => 5, 'expression' => '\frac{\sqrt{8} \cdot \sqrt{192}}{\sqrt{24}}'],
                            ['id' => 6, 'expression' => '\frac{\sqrt{75} \cdot \sqrt{10}}{\sqrt{30}}'],
                        ]
                    ],
                    // Задание 7 - Сложные радикальные выражения
                    [
                        'number' => 7,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '5\sqrt{11} \cdot 2\sqrt{2} \cdot \sqrt{22}'],
                            ['id' => 2, 'expression' => '7\sqrt{15} \cdot 2\sqrt{2} \cdot \sqrt{30}'],
                            ['id' => 3, 'expression' => '4\sqrt{17} \cdot 5\sqrt{2} \cdot \sqrt{34}'],
                            ['id' => 4, 'expression' => '4\sqrt{5} \cdot 3\sqrt{3} \cdot \sqrt{15}'],
                            ['id' => 5, 'expression' => '10\sqrt{7} \cdot 2\sqrt{6} \cdot \sqrt{42}'],
                            ['id' => 6, 'expression' => '5\sqrt{13} \cdot 2\sqrt{3} \cdot \sqrt{39}'],
                        ]
                    ],
                    // Задание 8 - Степени и корни
                    [
                        'number' => 8,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{6^4}'],
                            ['id' => 2, 'expression' => '\sqrt{5^6}'],
                            ['id' => 3, 'expression' => '\sqrt{4^5}'],
                            ['id' => 4, 'expression' => '\sqrt{9^3}'],
                            ['id' => 5, 'expression' => '\sqrt{8^4}'],
                            ['id' => 6, 'expression' => '\sqrt{3^6}'],
                            ['id' => 7, 'expression' => '\frac{(2\sqrt{10})^2}{160}'],
                            ['id' => 8, 'expression' => '\frac{(3\sqrt{5})^2}{30}'],
                            ['id' => 9, 'expression' => '\frac{(4\sqrt{2})^2}{64}'],
                            ['id' => 10, 'expression' => '\frac{72}{(2\sqrt{3})^2}'],
                            ['id' => 11, 'expression' => '\frac{160}{(2\sqrt{5})^2}'],
                            ['id' => 12, 'expression' => '\frac{200}{(5\sqrt{2})^2}'],
                        ]
                    ],
                    // Задание 9 - Сопряжённые выражения
                    [
                        'number' => 9,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(\sqrt{17} - 3)(\sqrt{17} + 3)'],
                            ['id' => 2, 'expression' => '(\sqrt{23} - 2)(\sqrt{23} + 2)'],
                            ['id' => 3, 'expression' => '(\sqrt{47} - 5)(\sqrt{47} + 5)'],
                            ['id' => 4, 'expression' => '(\sqrt{29} - 4)(\sqrt{29} + 4)'],
                            ['id' => 5, 'expression' => '(\sqrt{41} - 3)(\sqrt{41} + 3)'],
                            ['id' => 6, 'expression' => '(\sqrt{13} - 2)(\sqrt{13} + 2)'],
                            ['id' => 7, 'expression' => '(\sqrt{7} - 3)(\sqrt{7} + 3)'],
                            ['id' => 8, 'expression' => '(\sqrt{13} - 2)(\sqrt{13} + 2)'],
                            ['id' => 9, 'expression' => '(\sqrt{17} - 5)(\sqrt{17} + 5)'],
                            ['id' => 10, 'expression' => '(\sqrt{19} - 2)(\sqrt{19} + 2)'],
                            ['id' => 11, 'expression' => '(\sqrt{5} - 3)(\sqrt{5} + 3)'],
                            ['id' => 12, 'expression' => '(\sqrt{7} - 5)(\sqrt{7} + 5)'],
                            ['id' => 13, 'expression' => '(\sqrt{19} - 7)^2 + 14\sqrt{19}'],
                            ['id' => 14, 'expression' => '(\sqrt{13} - 3)^2 + 6\sqrt{13}'],
                            ['id' => 15, 'expression' => '(\sqrt{11} - 7)^2 + 14\sqrt{11}'],
                            ['id' => 16, 'expression' => '(\sqrt{5} + 9)^2 - 18\sqrt{5}'],
                            ['id' => 17, 'expression' => '(\sqrt{17} + 2)^2 - 4\sqrt{17}'],
                            ['id' => 18, 'expression' => '(\sqrt{3} + 8)^2 - 16\sqrt{3}'],
                        ]
                    ],
                    // Задание 10 - Суммы дробей с радикалами
                    [
                        'number' => 10,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{2 + \sqrt{3}} + \frac{1}{2 - \sqrt{3}}'],
                            ['id' => 2, 'expression' => '\frac{1}{5 + \sqrt{23}} + \frac{1}{5 - \sqrt{23}}'],
                            ['id' => 3, 'expression' => '\frac{1}{6 + \sqrt{35}} + \frac{1}{6 - \sqrt{35}}'],
                            ['id' => 4, 'expression' => '\frac{1}{4 + \sqrt{15}} + \frac{1}{4 - \sqrt{15}}'],
                            ['id' => 5, 'expression' => '\frac{1}{7 + \sqrt{47}} + \frac{1}{7 - \sqrt{47}}'],
                            ['id' => 6, 'expression' => '\frac{1}{3 + \sqrt{7}} + \frac{1}{3 - \sqrt{7}}'],
                        ]
                    ],
                    // Задание 11 - Разности дробей с радикалами
                    [
                        'number' => 11,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{1}{\sqrt{5} - 2} - \frac{1}{\sqrt{5} + 2}'],
                            ['id' => 2, 'expression' => '\frac{1}{\sqrt{10} - 3} - \frac{1}{\sqrt{10} + 3}'],
                            ['id' => 3, 'expression' => '\frac{1}{\sqrt{37} - 6} - \frac{1}{\sqrt{37} + 6}'],
                            ['id' => 4, 'expression' => '\frac{1}{\sqrt{17} - 4} - \frac{1}{\sqrt{17} + 4}'],
                            ['id' => 5, 'expression' => '\frac{1}{\sqrt{13} - 3} - \frac{1}{\sqrt{13} + 3}'],
                            ['id' => 6, 'expression' => '\frac{1}{\sqrt{27} - 5} - \frac{1}{\sqrt{27} + 5}'],
                        ]
                    ],
                    // II) Степенные выражения
                    // Задание 12 - Степени с параметрами
                    [
                        'number' => 12,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{a^9 \cdot a^{12}}{a^{18}} \text{ при } a = 4'],
                            ['id' => 2, 'expression' => '\frac{a^{12} \cdot a^6}{a^{14}} \text{ при } a = 3'],
                            ['id' => 3, 'expression' => '\frac{a^{11} \cdot a^9}{a^{18}} \text{ при } a = 7'],
                            ['id' => 4, 'expression' => '\frac{a^9 \cdot a^8}{a^{12}} \text{ при } a = 2'],
                            ['id' => 5, 'expression' => '\frac{a^{16} \cdot a^{-7}}{a^8} \text{ при } a = 3'],
                            ['id' => 6, 'expression' => '\frac{a^{18} \cdot a^{-6}}{a^{10}} \text{ при } a = 5'],
                            ['id' => 7, 'expression' => '\frac{a^{17} \cdot a^{-6}}{a^9} \text{ при } a = 4'],
                            ['id' => 8, 'expression' => '\frac{a^{19} \cdot a^{-11}}{a^5} \text{ при } a = 5'],
                            ['id' => 9, 'expression' => '\frac{(a^4)^5}{a^{18}} \text{ при } a = 6'],
                            ['id' => 10, 'expression' => '\frac{(a^8)^2}{a^{11}} \text{ при } a = 2'],
                            ['id' => 11, 'expression' => '\frac{(a^8)^2}{a^{13}} \text{ при } a = 5'],
                            ['id' => 12, 'expression' => '\frac{(a^3)^5}{a^{11}} \text{ при } a = 3'],
                        ]
                    ],
                    // Задание 13 - Степени с делением
                    [
                        'number' => 13,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'a^6 \cdot a^{18} : a^{20} \text{ при } a = 2'],
                            ['id' => 2, 'expression' => 'a^{14} \cdot a^{-5} : a^7 \text{ при } a = 3'],
                            ['id' => 3, 'expression' => 'a^{15} \cdot a^{-8} : a^4 \text{ при } a = 4'],
                            ['id' => 4, 'expression' => 'a^{20} \cdot a^{-9} : a^8 \text{ при } a = 2'],
                            ['id' => 5, 'expression' => 'a^{21} \cdot a^{-8} : a^{11} \text{ при } a = 5'],
                            ['id' => 6, 'expression' => 'a^{17} \cdot a^{-4} : a^{10} \text{ при } a = 6'],
                        ]
                    ],
                    // Задание 14 - Степени со скобками
                    [
                        'number' => 14,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(a^5)^3 \cdot a^{-8} \text{ при } a = 2'],
                            ['id' => 2, 'expression' => '(a^4)^5 \cdot a^{-12} \text{ при } a = 3'],
                            ['id' => 3, 'expression' => '(a^3)^7 \cdot a^{-15} \text{ при } a = 2'],
                            ['id' => 4, 'expression' => '(a^6)^3 \cdot a^{-11} \text{ при } a = 4'],
                            ['id' => 5, 'expression' => '(a^2)^9 \cdot a^{-14} \text{ при } a = 5'],
                            ['id' => 6, 'expression' => '(a^4)^4 \cdot a^{-9} \text{ при } a = 3'],
                        ]
                    ],
                    // Задание 15 - Сложные степени
                    [
                        'number' => 15,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{(a^3)^4}{a^5 \cdot a^4} \text{ при } a = 7'],
                            ['id' => 2, 'expression' => '\frac{(a^5)^3}{a^8 \cdot a^4} \text{ при } a = 5'],
                            ['id' => 3, 'expression' => '\frac{(a^4)^4}{a^9 \cdot a^5} \text{ при } a = 3'],
                            ['id' => 4, 'expression' => '\frac{(a^2)^7}{a^6 \cdot a^5} \text{ при } a = 6'],
                            ['id' => 5, 'expression' => '\frac{(a^6)^2}{a^3 \cdot a^6} \text{ при } a = 8'],
                            ['id' => 6, 'expression' => '\frac{(a^3)^5}{a^7 \cdot a^4} \text{ при } a = 4'],
                        ]
                    ],
                    // Задание 16 - Степени с xy
                    [
                        'number' => 16,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{x^5 \cdot y^4}{(xy)^3} \text{ при } x = 3, y = 4'],
                            ['id' => 2, 'expression' => '\frac{x^6 \cdot y^5}{(xy)^4} \text{ при } x = 2, y = 5'],
                            ['id' => 3, 'expression' => '\frac{x^7 \cdot y^3}{(xy)^4} \text{ при } x = 4, y = 3'],
                            ['id' => 4, 'expression' => '\frac{x^4 \cdot y^6}{(xy)^3} \text{ при } x = 5, y = 2'],
                            ['id' => 5, 'expression' => '\frac{x^8 \cdot y^4}{(xy)^5} \text{ при } x = 3, y = 6'],
                            ['id' => 6, 'expression' => '\frac{x^5 \cdot y^7}{(xy)^4} \text{ при } x = 6, y = 3'],
                        ]
                    ],
                    // Задание 17 - Степенные дроби
                    [
                        'number' => 17,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{6^4 \cdot 9^5}{54^4}'],
                            ['id' => 2, 'expression' => '\frac{4^5 \cdot 16^3}{64^3}'],
                            ['id' => 3, 'expression' => '\frac{8^3 \cdot 27^2}{72^2}'],
                            ['id' => 4, 'expression' => '\frac{9^4 \cdot 4^6}{6^8}'],
                            ['id' => 5, 'expression' => '\frac{25^3 \cdot 8^4}{20^4}'],
                            ['id' => 6, 'expression' => '\frac{16^3 \cdot 27^2}{36^3}'],
                        ]
                    ],
                    // Задание 18 - Степени с произведениями
                    [
                        'number' => 18,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{4^8 \cdot 11^{10}}{44^8}'],
                            ['id' => 2, 'expression' => '\frac{7^8 \cdot 10^6}{70^6}'],
                            ['id' => 3, 'expression' => '\frac{3^8 \cdot 10^5}{30^5}'],
                            ['id' => 4, 'expression' => '\frac{5^4 \cdot 7^5}{35^4}'],
                            ['id' => 5, 'expression' => '\frac{4^4 \cdot 7^5}{28^4}'],
                            ['id' => 6, 'expression' => '\frac{2^6 \cdot 3^5}{6^5}'],
                            ['id' => 7, 'expression' => '\frac{2^9 \cdot 12^{11}}{24^9}'],
                            ['id' => 8, 'expression' => '\frac{3^{13} \cdot 7^{10}}{21^{10}}'],
                            ['id' => 9, 'expression' => '\frac{5^9 \cdot 8^{11}}{40^9}'],
                            ['id' => 10, 'expression' => '\frac{6^9 \cdot 11^7}{66^7}'],
                            ['id' => 11, 'expression' => '\frac{8^7 \cdot 9^5}{72^5}'],
                            ['id' => 12, 'expression' => '\frac{7^6 \cdot 8^4}{56^4}'],
                            ['id' => 13, 'expression' => '\frac{(4 \cdot 5)^8}{4^6 \cdot 5^8}'],
                            ['id' => 14, 'expression' => '\frac{(2 \cdot 6)^7}{2^5 \cdot 6^6}'],
                            ['id' => 15, 'expression' => '\frac{(3 \cdot 10)^8}{3^6 \cdot 10^7}'],
                            ['id' => 16, 'expression' => '\frac{(5 \cdot 7)^6}{5^4 \cdot 7^6}'],
                            ['id' => 17, 'expression' => '\frac{(6 \cdot 7)^5}{6^5 \cdot 7^3}'],
                            ['id' => 18, 'expression' => '\frac{(4 \cdot 9)^6}{4^4 \cdot 9^6}'],
                            ['id' => 19, 'expression' => '\frac{(3 \cdot 8)^7}{3^7 \cdot 8^5}'],
                            ['id' => 20, 'expression' => '\frac{(2 \cdot 10)^5}{2^2 \cdot 10^4}'],
                        ]
                    ],
                    // Задание 19 - Отрицательные степени
                    [
                        'number' => 19,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '5^{-7} \cdot (5^5)^2'],
                            ['id' => 2, 'expression' => '2^{-7} \cdot (2^4)^3'],
                            ['id' => 3, 'expression' => '9^{-6} \cdot (9^2)^4'],
                            ['id' => 4, 'expression' => '3^{-8} \cdot (3^6)^2'],
                            ['id' => 5, 'expression' => '2^{-9} \cdot (2^7)^2'],
                            ['id' => 6, 'expression' => '11^{-5} \cdot (11^3)^2'],
                            ['id' => 7, 'expression' => '\frac{(8^3)^{-7}}{8^{-23}}'],
                            ['id' => 8, 'expression' => '\frac{(3^7)^{-2}}{3^{-16}}'],
                            ['id' => 9, 'expression' => '\frac{(2^9)^{-3}}{2^{-29}}'],
                            ['id' => 10, 'expression' => '\frac{(5^2)^{-8}}{5^{-18}}'],
                            ['id' => 11, 'expression' => '\frac{(7^7)^{-3}}{7^{-23}}'],
                            ['id' => 12, 'expression' => '\frac{(6^2)^{-9}}{6^{-20}}'],
                            ['id' => 13, 'expression' => '\frac{1}{5^{-8} \cdot 5^6}'],
                            ['id' => 14, 'expression' => '\frac{1}{7^{-14}} \cdot \frac{1}{7^{13}}'],
                            ['id' => 15, 'expression' => '\frac{1}{2^{-19}} \cdot \frac{1}{2^{16}}'],
                            ['id' => 16, 'expression' => '\frac{1}{8^{-7} \cdot 8^6}'],
                            ['id' => 17, 'expression' => '\frac{1}{3^{-10}} \cdot \frac{1}{3^8}'],
                            ['id' => 18, 'expression' => '\frac{1}{4^{-10}} \cdot \frac{1}{4^9}'],
                        ]
                    ],
                    // Задание 20 - Степенные выражения
                    [
                        'number' => 20,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{7^{-3} \cdot 7^{13}}{7^8}'],
                            ['id' => 2, 'expression' => '\frac{9^{-6} \cdot 9^{15}}{9^7}'],
                            ['id' => 3, 'expression' => '\frac{3^{-5} \cdot 3^{15}}{3^7}'],
                            ['id' => 4, 'expression' => '\frac{2^{-3} \cdot 2^{19}}{2^{13}}'],
                            ['id' => 5, 'expression' => '\frac{11^{-3} \cdot 11^{12}}{11^8}'],
                            ['id' => 6, 'expression' => '\frac{13^{-4} \cdot 13^{16}}{13^{11}}'],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // Задание 1 - Упрощение радикалов
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{\sqrt{54}}{\sqrt{6}}'],
                            ['id' => 2, 'expression' => '\frac{\sqrt{28}}{\sqrt{7}}'],
                            ['id' => 3, 'expression' => '\frac{\sqrt{48}}{\sqrt{3}}'],
                            ['id' => 4, 'expression' => '\frac{\sqrt{75}}{\sqrt{3}}'],
                            ['id' => 5, 'expression' => '\frac{\sqrt{72}}{\sqrt{2}}'],
                            ['id' => 6, 'expression' => '\frac{\sqrt{60}}{\sqrt{15}}'],
                            ['id' => 7, 'expression' => '\sqrt{45} \cdot \sqrt{60} \cdot \sqrt{12}'],
                            ['id' => 8, 'expression' => '\sqrt{24} \cdot \sqrt{75} \cdot \sqrt{8}'],
                            ['id' => 9, 'expression' => '\sqrt{66} \cdot \sqrt{110} \cdot \sqrt{15}'],
                            ['id' => 10, 'expression' => '\sqrt{42} \cdot \sqrt{75} \cdot \sqrt{14}'],
                            ['id' => 11, 'expression' => '\sqrt{63} \cdot \sqrt{80} \cdot \sqrt{35}'],
                            ['id' => 12, 'expression' => '\sqrt{54} \cdot \sqrt{90} \cdot \sqrt{15}'],
                        ]
                    ],
                    // Задание 2 - Радикалы с числами
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{9} \cdot \sqrt{54}'],
                            ['id' => 2, 'expression' => '\sqrt{25} \cdot \sqrt{72}'],
                            ['id' => 3, 'expression' => '\sqrt{9} \cdot \sqrt{82}'],
                            ['id' => 4, 'expression' => '\sqrt{4} \cdot \sqrt{36}'],
                            ['id' => 5, 'expression' => '\sqrt{16} \cdot \sqrt{34}'],
                            ['id' => 6, 'expression' => '\sqrt{25} \cdot \sqrt{26}'],
                            ['id' => 7, 'expression' => '\sqrt{3} \cdot \sqrt{72} - \sqrt{3} \cdot \sqrt{24}'],
                            ['id' => 8, 'expression' => '\sqrt{7} \cdot \sqrt{34} - \sqrt{7} \cdot \sqrt{22}'],
                            ['id' => 9, 'expression' => '\sqrt{11} \cdot \sqrt{36} - \sqrt{11} \cdot \sqrt{22}'],
                            ['id' => 10, 'expression' => '\sqrt{2} \cdot \sqrt{492} - \sqrt{2} \cdot \sqrt{54}'],
                            ['id' => 11, 'expression' => '\sqrt{17} \cdot \sqrt{54} - \sqrt{17} \cdot \sqrt{22}'],
                            ['id' => 12, 'expression' => '\sqrt{13} \cdot \sqrt{54} - \sqrt{13} \cdot \sqrt{62}'],
                            ['id' => 13, 'expression' => '\sqrt{26} \cdot \sqrt{72} - \sqrt{102}'],
                            ['id' => 14, 'expression' => '\sqrt{54} \cdot \sqrt{62} - \sqrt{132}'],
                            ['id' => 15, 'expression' => '\sqrt{22} \cdot \sqrt{54} - \sqrt{492}'],
                            ['id' => 16, 'expression' => '\sqrt{26} \cdot \sqrt{32} - \sqrt{52}'],
                            ['id' => 17, 'expression' => '\sqrt{34} \cdot \sqrt{42} - \sqrt{22}'],
                            ['id' => 18, 'expression' => '\sqrt{54} \cdot \sqrt{82} - \sqrt{212}'],
                        ]
                    ],
                    // Задание 3 - Корни с параметрами xy
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{36x^4y^{10}} \text{ при } x = 3, y = 2'],
                            ['id' => 2, 'expression' => '\sqrt{4x^6y^4} \text{ при } x = 3, y = 5'],
                            ['id' => 3, 'expression' => '\sqrt{25x^6y^4} \text{ при } x = 2, y = 6'],
                            ['id' => 4, 'expression' => '\sqrt{16x^4y^6} \text{ при } x = 6, y = 2'],
                            ['id' => 5, 'expression' => '\sqrt{9x^8y^6} \text{ при } x = 2, y = 3'],
                            ['id' => 6, 'expression' => '\sqrt{25x^4y^4} \text{ при } x = 3, y = 7'],
                            ['id' => 7, 'expression' => '\sqrt{9x^4y^6} \text{ при } x = 5, y = 3'],
                            ['id' => 8, 'expression' => '\sqrt{49x^8y^4} \text{ при } x = 2, y = 3'],
                        ]
                    ],
                    // Задание 4 - Сложные корни с дробями
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{\frac{25a^9 \cdot 16b^8}{a^5b^8}} \text{ при } a = 4, b = 7'],
                            ['id' => 2, 'expression' => '\sqrt{\frac{16a^9 \cdot 4b^3}{a^5b^3}} \text{ при } a = 9, b = 11'],
                            ['id' => 3, 'expression' => '\sqrt{\frac{4a^{11} \cdot 9b^4}{a^7b^4}} \text{ при } a = 7, b = 9'],
                            ['id' => 4, 'expression' => '\sqrt{\frac{25a^5 \cdot 36b^6}{a^5b^4}} \text{ при } a = 4, b = 9'],
                            ['id' => 5, 'expression' => '\sqrt{\frac{16a^5 \cdot 36b}{ab}} \text{ при } a = 7, b = 5'],
                            ['id' => 6, 'expression' => '\sqrt{\frac{4a^6 \cdot 25b^7}{a^2b^7}} \text{ при } a = 9, b = 7'],
                            ['id' => 7, 'expression' => '\sqrt{\frac{36a \cdot 9b^5}{ab}} \text{ при } a = 9, b = 4'],
                            ['id' => 8, 'expression' => '\sqrt{\frac{25a^8 \cdot 9b^5}{a^4b^5}} \text{ при } a = 7, b = 10'],
                        ]
                    ],
                    // Задание 5 - Квадраты сумм и разностей
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(5 + \sqrt{2})^2 + (5 - \sqrt{2})^2'],
                            ['id' => 2, 'expression' => '(4 + \sqrt{7})^2 + (4 - \sqrt{7})^2'],
                            ['id' => 3, 'expression' => '(3 + \sqrt{2})^2 + (3 - \sqrt{2})^2'],
                            ['id' => 4, 'expression' => '(4 + \sqrt{5})^2 + (4 - \sqrt{5})^2'],
                            ['id' => 5, 'expression' => '(5 + \sqrt{7})^2 + (5 - \sqrt{7})^2'],
                            ['id' => 6, 'expression' => '(3 + \sqrt{5})^2 + (3 - \sqrt{5})^2'],
                            ['id' => 7, 'expression' => '\sqrt{(-17)^2}'],
                            ['id' => 8, 'expression' => '\sqrt{(-11)^2}'],
                            ['id' => 9, 'expression' => '\sqrt{(-19)^2}'],
                            ['id' => 10, 'expression' => '\sqrt{(-23)^2}'],
                            ['id' => 11, 'expression' => '\sqrt{(-5)^2}'],
                            ['id' => 12, 'expression' => '\sqrt{(-29)^2}'],
                            ['id' => 13, 'expression' => '(3\sqrt{2} - 5)^2 + 3\sqrt{2}'],
                            ['id' => 14, 'expression' => '(5\sqrt{2} - 8)^2 + 5\sqrt{2}'],
                            ['id' => 15, 'expression' => '(4\sqrt{2} - 7)^2 + 4\sqrt{2}'],
                            ['id' => 16, 'expression' => '(6\sqrt{3} - 11)^2 + 6\sqrt{3}'],
                            ['id' => 17, 'expression' => '(2\sqrt{3} - 5)^2 + 2\sqrt{3}'],
                            ['id' => 18, 'expression' => '(5\sqrt{3} - 9)^2 + 5\sqrt{3}'],
                        ]
                    ],
                    // Задание 6 - Степенные выражения
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{(2^2 \cdot 2^4)^7}{(2 \cdot 2^6)^6}'],
                            ['id' => 2, 'expression' => '\frac{(3^3 \cdot 3^5)^6}{(3 \cdot 3^8)^5}'],
                            ['id' => 3, 'expression' => '\frac{(5^2 \cdot 5^3)^4}{(5 \cdot 5^5)^3}'],
                            ['id' => 4, 'expression' => '\frac{(7^2 \cdot 7^4)^5}{(7 \cdot 7^6)^4}'],
                            ['id' => 5, 'expression' => '\frac{(2^2 \cdot 2^6)^5}{(2 \cdot 2^8)^4}'],
                            ['id' => 6, 'expression' => '\frac{(3^2 \cdot 3^7)^9}{(3 \cdot 3^9)^8}'],
                            ['id' => 7, 'expression' => '\frac{16^4}{8^6}'],
                            ['id' => 8, 'expression' => '\frac{8^{15}}{27^6}'],
                            ['id' => 9, 'expression' => '\frac{125^3}{25^5}'],
                            ['id' => 10, 'expression' => '\frac{64^2}{16^3}'],
                            ['id' => 11, 'expression' => '\frac{27^3}{9^4}'],
                            ['id' => 12, 'expression' => '\frac{8^3}{4^5}'],
                            ['id' => 13, 'expression' => '2^{-7} \cdot 2^{-8} : 2^{-16}'],
                            ['id' => 14, 'expression' => '9^{-5} \cdot 9^{-8} : 9^{-15}'],
                            ['id' => 15, 'expression' => '3^{-4} \cdot 3^{-8} : 3^{-14}'],
                            ['id' => 16, 'expression' => '7^{-3} \cdot 7^{-8} : 7^{-13}'],
                            ['id' => 17, 'expression' => '11^{-5} \cdot 11^{-13} : 11^{-19}'],
                            ['id' => 18, 'expression' => '5^{-3} \cdot 5^{-9} : 5^{-14}'],
                        ]
                    ],
                ]
            ],

            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    // I) Иррациональные числа и выражения
                    // Задание 1 - Разные выражения
                    [
                        'number' => 1,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(\sqrt{2} \cdot \sqrt{3})^2 - 1'],
                            ['id' => 2, 'expression' => '(\sqrt{3} \cdot \sqrt{5})^2 - 9'],
                            ['id' => 3, 'expression' => '(\sqrt{7} \cdot \sqrt{2})^2 - 10'],
                            ['id' => 4, 'expression' => '(\sqrt{3} \cdot \sqrt{6})^2 - 8'],
                            ['id' => 5, 'expression' => '(\sqrt{5} \cdot \sqrt{2})^2 - 7'],
                            ['id' => 6, 'expression' => '(\sqrt{6} \cdot \sqrt{5})^2 - 18'],
                            ['id' => 7, 'expression' => '\sqrt{64} + (\sqrt{6{,}4})^2'],
                            ['id' => 8, 'expression' => '\sqrt{25} + (\sqrt{2{,}5})^2'],
                            ['id' => 9, 'expression' => '\sqrt{16} + (\sqrt{1{,}6})^2'],
                            ['id' => 10, 'expression' => '\sqrt{49} + (\sqrt{4{,}9})^2'],
                            ['id' => 11, 'expression' => '\sqrt{36} + (\sqrt{3{,}6})^2'],
                            ['id' => 12, 'expression' => '\sqrt{81} + (\sqrt{8{,}1})^2'],
                            ['id' => 13, 'expression' => '\sqrt{0{,}9 \cdot 40}'],
                            ['id' => 14, 'expression' => '\sqrt{0{,}7 \cdot 70}'],
                            ['id' => 15, 'expression' => '\sqrt{4{,}5 \cdot 50}'],
                            ['id' => 16, 'expression' => '\sqrt{3{,}2 \cdot 20}'],
                            ['id' => 17, 'expression' => '\sqrt{1{,}8 \cdot 80}'],
                            ['id' => 18, 'expression' => '\sqrt{2{,}7 \cdot 30}'],
                            ['id' => 19, 'expression' => '\frac{5}{6}\sqrt{48} \cdot \sqrt{3}'],
                            ['id' => 20, 'expression' => '\frac{4}{7}\sqrt{28} \cdot \sqrt{7}'],
                            ['id' => 21, 'expression' => '\frac{5}{8}\sqrt{32} \cdot \sqrt{2}'],
                            ['id' => 22, 'expression' => '\frac{7}{9}\sqrt{27} \cdot \sqrt{3}'],
                            ['id' => 23, 'expression' => '\frac{3}{4}\sqrt{32} \cdot \sqrt{8}'],
                            ['id' => 24, 'expression' => '\frac{2}{5}\sqrt{45} \cdot \sqrt{5}'],
                        ]
                    ],
                    // Задание 2 - Корни с параметрами
                    [
                        'number' => 2,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{0{,}04a^4b^6} \text{ при } a = 10, b = 3'],
                            ['id' => 2, 'expression' => '\sqrt{0{,}25p^4q^4} \text{ при } p = 8, q = 3'],
                            ['id' => 3, 'expression' => '\sqrt{0{,}01c^8d^4} \text{ при } c = 3, d = 2'],
                            ['id' => 4, 'expression' => '\sqrt{0{,}09a^6b^2} \text{ при } a = 2, b = 12'],
                            ['id' => 5, 'expression' => '\sqrt{0{,}16x^2y^6} \text{ при } x = 4, y = 5'],
                            ['id' => 6, 'expression' => '\sqrt{0{,}36p^8q^2} \text{ при } p = 2, q = 15'],
                            ['id' => 7, 'expression' => '\sqrt{a^6 \cdot (-a)^2} \text{ при } a = 10'],
                            ['id' => 8, 'expression' => '\sqrt{(-a)^3 \cdot (-a)} \text{ при } a = 7'],
                            ['id' => 9, 'expression' => '\sqrt{(-a)^2 \cdot a^4} \text{ при } a = 5'],
                            ['id' => 10, 'expression' => '\sqrt{(-a)^7 \cdot (-a)^5} \text{ при } a = 2'],
                            ['id' => 11, 'expression' => '\sqrt{a^2 \cdot (-a)^2} \text{ при } a = 12'],
                            ['id' => 12, 'expression' => '\sqrt{(-a)^5 \cdot (-a)^3} \text{ при } a = 3'],
                        ]
                    ],
                    // Задание 3 - Корни дробей с параметрами
                    [
                        'number' => 3,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{9\sqrt{a^8b}}{12\sqrt{ab}} \text{ при } a = 11, b = 8'],
                            ['id' => 2, 'expression' => '\frac{15\sqrt{x^{16}y}}{10\sqrt{xy}} \text{ при } x = 13, y = 3'],
                            ['id' => 3, 'expression' => '\frac{25\sqrt{a^{12}b}}{15\sqrt{ab}} \text{ при } a = 6, b = 7'],
                            ['id' => 4, 'expression' => '\frac{6\sqrt{x \cdot 21y}}{14\sqrt{xy}} \text{ при } x = 3, y = 10'],
                            ['id' => 5, 'expression' => '\frac{49\sqrt{a \cdot 9b}}{21\sqrt{ab}} \text{ при } a = 4, b = 15'],
                            ['id' => 6, 'expression' => '\frac{12\sqrt{x \cdot 25y}}{20\sqrt{xy}} \text{ при } x = 6, y = 12'],
                        ]
                    ],
                    // Задание 4 - Корни с дробями степеней
                    [
                        'number' => 4,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\sqrt{\frac{16a^{18}}{a^{14}}} \text{ при } a = 3'],
                            ['id' => 2, 'expression' => '\sqrt{\frac{b^{21}}{100b^{15}}} \text{ при } b = 4'],
                            ['id' => 3, 'expression' => '\sqrt{\frac{81x^{18}}{x^{20}}} \text{ при } x = 18'],
                            ['id' => 4, 'expression' => '\sqrt{\frac{y^{22}}{25y^{14}}} \text{ при } y = 2'],
                            ['id' => 5, 'expression' => '\sqrt{\frac{144p^{20}}{p^{16}}} \text{ при } p = 2'],
                            ['id' => 6, 'expression' => '\sqrt{\frac{q^{19}}{64q^{15}}} \text{ при } q = 6'],
                        ]
                    ],
                    // II) Степенные выражения
                    // Задание 5 - Отрицательные степени
                    [
                        'number' => 5,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{8^{-6} \cdot 8^{-7}}{8^{-15}}'],
                            ['id' => 2, 'expression' => '\frac{5^{-3} \cdot 5^{-9}}{5^{-11}}'],
                            ['id' => 3, 'expression' => '\frac{9^{-5} \cdot 9^{-4}}{9^{-10}}'],
                            ['id' => 4, 'expression' => '\frac{4^{-2} \cdot 4^{-7}}{4^{-9}}'],
                            ['id' => 5, 'expression' => '\frac{2^{-7} \cdot 2^{-6}}{2^{-12}}'],
                            ['id' => 6, 'expression' => '\frac{3^{-7} \cdot 3^{-6}}{3^{-16}}'],
                        ]
                    ],
                    // Задание 6 - Степени с параметрами
                    [
                        'number' => 6,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'a^{-11} \cdot (a^5)^2 \text{ при } a = 5'],
                            ['id' => 2, 'expression' => 'b^{16} \cdot (b^3)^{-5} \text{ при } b = 7'],
                            ['id' => 3, 'expression' => 'c^{-4} \cdot (c^3)^2 \text{ при } c = 9'],
                            ['id' => 4, 'expression' => 'd^4 \cdot (d^2)^{-3} \text{ при } d = 2'],
                            ['id' => 5, 'expression' => 'm^{-10} \cdot (m^4)^2 \text{ при } m = 10'],
                            ['id' => 6, 'expression' => 'n^{10} \cdot (n^{-4})^2 \text{ при } n = 6'],
                            ['id' => 7, 'expression' => '\frac{(x^4)^{-6}}{x^{-28}} \text{ при } x = 3'],
                            ['id' => 8, 'expression' => '\frac{(y^4)^{-5}}{y^{-19}} \text{ при } y = 10'],
                            ['id' => 9, 'expression' => '\frac{(a^{-2})^{-3}}{a^{-1}} \text{ при } a = 2'],
                            ['id' => 10, 'expression' => '\frac{(b^3)^{-4}}{b^{-11}} \text{ при } b = 5'],
                            ['id' => 11, 'expression' => '\frac{(p^{-2})^{-1}}{p^{-3}} \text{ при } p = 2'],
                            ['id' => 12, 'expression' => '\frac{(q^{-4})^5}{q^{-22}} \text{ при } q = 8'],
                        ]
                    ],
                    // Задание 7 - Степени с умножением
                    [
                        'number' => 7,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{a^{12} \cdot a^{13}}{a^{20}} \text{ при } a = 2'],
                            ['id' => 2, 'expression' => '\frac{x^9 \cdot x^{18}}{x^{28}} \text{ при } x = 20'],
                            ['id' => 3, 'expression' => '\frac{y^{17} \cdot y^4}{y^{19}} \text{ при } y = 13'],
                            ['id' => 4, 'expression' => '\frac{b^{14} \cdot b^8}{b^{21}} \text{ при } b = 17'],
                            ['id' => 5, 'expression' => '\frac{d^{16} \cdot d^{10}}{d^{28}} \text{ при } d = 10'],
                            ['id' => 6, 'expression' => '\frac{c^8 \cdot c^{12}}{c^{16}} \text{ при } c = 3'],
                        ]
                    ],
                    // Задание 8 - Сложные степенные выражения
                    [
                        'number' => 8,
                        'instruction' => 'Найдите значение выражения',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\frac{(x^2)^4 \cdot x^5}{x^7} \text{ при } x = 2'],
                            ['id' => 2, 'expression' => '\frac{(y^5)^7 \cdot y^2}{y^{36}} \text{ при } y = 7'],
                            ['id' => 3, 'expression' => '\frac{(a^4)^5 \cdot a^7}{a^{29}} \text{ при } a = 5'],
                            ['id' => 4, 'expression' => '\frac{(b^2)^7 \cdot b^3}{b^{18}} \text{ при } b = 4'],
                            ['id' => 5, 'expression' => '\frac{(c^6)^3 \cdot c^4}{c^{20}} \text{ при } c = 9'],
                            ['id' => 6, 'expression' => '\frac{(z^2)^6 \cdot z^4}{z^{19}} \text{ при } z = 10'],
                            ['id' => 7, 'expression' => '\frac{m^{15} \cdot (n^6)^3}{(mn)^{17}} \text{ при } m = 5, n = 15'],
                            ['id' => 8, 'expression' => '\frac{x^{14} \cdot (y^3)^5}{(xy)^{13}} \text{ при } x = 10, y = 7'],
                            ['id' => 9, 'expression' => '\frac{a^{16} \cdot (b^7)^3}{(ab)^{18}} \text{ при } a = 2, b = 6'],
                            ['id' => 10, 'expression' => '\frac{(p^3)^9 \cdot q^{24}}{(pq)^{23}} \text{ при } p = 2, q = 4'],
                            ['id' => 11, 'expression' => '\frac{(c^5)^4 \cdot d^{16}}{(cd)^{19}} \text{ при } c = 20, d = 10'],
                            ['id' => 12, 'expression' => '\frac{(z^4)^7 \cdot t^{29}}{(zt)^{26}} \text{ при } z = 10, t = 3'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 09
     */
    public function topic09()
    {
        $blocks = $this->getAllBlocksData09();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic09', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 09 - Уравнения
     */
    protected function getAllBlocksData09(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // I) Линейные уравнения
                    [
                        'number' => 1,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x + 3 = -9x'],
                            ['id' => 2, 'expression' => '-3x - 9 = 2x'],
                            ['id' => 3, 'expression' => '6x + 1 = -4x'],
                            ['id' => 4, 'expression' => '-2x - 4 = 3x'],
                            ['id' => 5, 'expression' => '3x + 3 = 5x'],
                            ['id' => 6, 'expression' => '-8x - 3 = -6x'],
                            ['id' => 7, 'expression' => '7 + 8x = -2x - 5'],
                            ['id' => 8, 'expression' => '-5 + 9x = 10x + 4'],
                            ['id' => 9, 'expression' => '1 - 10x = -5x + 10'],
                            ['id' => 10, 'expression' => '-4 - 6x = 4x - 3'],
                            ['id' => 11, 'expression' => '2 + 3x = -7x - 5'],
                            ['id' => 12, 'expression' => '-1 - 3x = 2x + 1'],
                            ['id' => 13, 'expression' => '4(x - 8) = -5'],
                            ['id' => 14, 'expression' => '10(x - 9) = 7'],
                            ['id' => 15, 'expression' => '5(x + 9) = -8'],
                            ['id' => 16, 'expression' => '4(x + 1) = 9'],
                            ['id' => 17, 'expression' => '10(x + 2) = -7'],
                            ['id' => 18, 'expression' => '5(x - 6) = 2'],
                        ]
                    ],
                    // II) Квадратные уравнения
                    [
                        'number' => 2,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 9 = 0'],
                            ['id' => 2, 'expression' => 'x^2 - 64 = 0'],
                            ['id' => 3, 'expression' => 'x^2 - 144 = 0'],
                            ['id' => 4, 'expression' => 'x^2 - 81 = 0'],
                            ['id' => 5, 'expression' => 'x^2 - 169 = 0'],
                            ['id' => 6, 'expression' => 'x^2 - 16 = 0'],
                            ['id' => 7, 'expression' => '4x^2 = 8x'],
                            ['id' => 8, 'expression' => '7x^2 = 42x'],
                            ['id' => 9, 'expression' => '10x^2 = 80x'],
                            ['id' => 10, 'expression' => '5x^2 = 35x'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите больший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 36 = 0'],
                            ['id' => 2, 'expression' => 'x^2 - 25 = 0'],
                            ['id' => 3, 'expression' => 'x^2 - 49 = 0'],
                            ['id' => 4, 'expression' => 'x^2 - 121 = 0'],
                            ['id' => 5, 'expression' => 'x^2 - 4 = 0'],
                            ['id' => 6, 'expression' => 'x^2 - 100 = 0'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 6x + 5 = 0'],
                            ['id' => 2, 'expression' => 'x^2 - 9x + 18 = 0'],
                            ['id' => 3, 'expression' => 'x^2 - 10x + 24 = 0'],
                            ['id' => 4, 'expression' => 'x^2 + x - 12 = 0'],
                            ['id' => 5, 'expression' => 'x^2 - 11x + 30 = 0'],
                            ['id' => 6, 'expression' => 'x^2 - 7x + 10 = 0'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите больший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 8x + 12 = 0'],
                            ['id' => 2, 'expression' => 'x^2 - 10x + 21 = 0'],
                            ['id' => 3, 'expression' => 'x^2 - 11x + 18 = 0'],
                            ['id' => 4, 'expression' => 'x^2 - 12x + 20 = 0'],
                            ['id' => 5, 'expression' => 'x^2 - 9x + 8 = 0'],
                            ['id' => 6, 'expression' => 'x^2 - 13x + 22 = 0'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '2x^2 - 3x + 1 = 0'],
                            ['id' => 2, 'expression' => '5x^2 - 9x + 4 = 0'],
                            ['id' => 3, 'expression' => '8x^2 - 10x + 2 = 0'],
                            ['id' => 4, 'expression' => '6x^2 - 9x + 3 = 0'],
                            ['id' => 5, 'expression' => '8x^2 - 12x + 4 = 0'],
                            ['id' => 6, 'expression' => '2x^2 + 5x - 7 = 0'],
                        ]
                    ],
                    [
                        'number' => 7,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите больший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '5x^2 + 9x + 4 = 0'],
                            ['id' => 2, 'expression' => '5x^2 + 4x - 1 = 0'],
                            ['id' => 3, 'expression' => '5x^2 - 12x + 7 = 0'],
                            ['id' => 4, 'expression' => '5x^2 + 8x + 3 = 0'],
                            ['id' => 5, 'expression' => '5x^2 - 11x + 6 = 0'],
                            ['id' => 6, 'expression' => '5x^2 + 7x - 12 = 0'],
                        ]
                    ],
                ]
            ],
            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // I) Линейные уравнения
                    [
                        'number' => 1,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '2 + 3x = -7x - 5'],
                            ['id' => 2, 'expression' => '7 + 8x = -2x - 5'],
                            ['id' => 3, 'expression' => '-7 - 2x = -6x + 10'],
                            ['id' => 4, 'expression' => '-1 - 3x = 2x + 1'],
                            ['id' => 5, 'expression' => '8 - 5(2x - 3) = 13 - 6x'],
                            ['id' => 6, 'expression' => '1 - 7(4 + 2x) = -9 - 4x'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '3x + 5 + (x + 5) = (1 - x) + 4'],
                            ['id' => 2, 'expression' => 'x - 3 - 4(x + 1) = 5(4 - x) - 1'],
                            ['id' => 3, 'expression' => '4x + 4 - 3(x + 1) = 5(-2 - x) + 5'],
                            ['id' => 4, 'expression' => '2x + 2 + 3(x + 4) = -4(1 - x) + 3'],
                            ['id' => 5, 'expression' => '-3x + 1 + (x - 5) = 5(3 - x) + 5'],
                            ['id' => 6, 'expression' => '-x - 4 + 5(x + 3) = 5(-1 - x) - 2'],
                            ['id' => 7, 'expression' => '-3x + 1 - 3(x + 3) = -2(1 - x) + 2'],
                            ['id' => 8, 'expression' => '-5x - 2 + 4(x + 1) = 4(-3 - x) - 1'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(x - 5)^2 = (x - 8)^2'],
                            ['id' => 2, 'expression' => '(x + 9)^2 = (x + 6)^2'],
                            ['id' => 3, 'expression' => '(x + 10)^2 = (5 - x)^2'],
                            ['id' => 4, 'expression' => '(x - 3)^2 = (x + 10)^2'],
                            ['id' => 5, 'expression' => '(x + 6)^2 = (15 - x)^2'],
                            ['id' => 6, 'expression' => '(x - 2)^2 = (x - 9)^2'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(x + 1)^2 + (x - 6)^2 = 2x^2'],
                            ['id' => 2, 'expression' => '(x - 2)^2 + (x - 8)^2 = 2x^2'],
                            ['id' => 3, 'expression' => '(x - 6)^2 + (x + 8)^2 = 2x^2'],
                            ['id' => 4, 'expression' => '(x - 2)^2 + (x - 3)^2 = 2x^2'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 + x + 6 = -x^2 - 3x + (-2 + 2x^2)'],
                            ['id' => 2, 'expression' => '-3x^2 + 5x - 3 = -x^2 + 3x + (2 - 2x^2)'],
                            ['id' => 3, 'expression' => '3x^2 - 4x + 7 = x^2 - 5x + (-1 + 2x^2)'],
                            ['id' => 4, 'expression' => '-4x^2 + 2x + 6 = -2x^2 + 3x - (-3 + 2x^2)'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x + \\frac{x}{9} = -\\frac{10}{3}'],
                            ['id' => 2, 'expression' => 'x - \\frac{x}{7} = 6'],
                            ['id' => 3, 'expression' => 'x + \\frac{x}{5} = -\\frac{12}{5}'],
                            ['id' => 4, 'expression' => 'x - \\frac{x}{12} = \\frac{11}{3}'],
                            ['id' => 5, 'expression' => 'x + \\frac{x}{2} = -9'],
                            ['id' => 6, 'expression' => 'x - \\frac{x}{11} = \\frac{50}{11}'],
                            ['id' => 7, 'expression' => '6 + \\frac{x}{2} = \\frac{x + 3}{5}'],
                            ['id' => 8, 'expression' => '-4 + \\frac{x}{5} = \\frac{x + 4}{2}'],
                            ['id' => 9, 'expression' => '1 + \\frac{x}{5} = \\frac{x + 9}{7}'],
                        ]
                    ],
                    [
                        'number' => 7,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\\frac{4x + 7}{3} + 2 = \\frac{7x}{2}'],
                            ['id' => 2, 'expression' => '\\frac{6x + 8}{2} + 5 = \\frac{5x}{3}'],
                            ['id' => 3, 'expression' => '\\frac{9x + 6}{7} + 3 = \\frac{7x}{6}'],
                        ]
                    ],
                    [
                        'number' => 8,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\\frac{12}{x + 5} = -\\frac{12}{5}'],
                            ['id' => 2, 'expression' => '\\frac{6}{x + 8} = -\\frac{3}{4}'],
                            ['id' => 3, 'expression' => '\\frac{1}{x + 2} = -\\frac{1}{2}'],
                            ['id' => 4, 'expression' => '\\frac{10}{x + 7} = -\\frac{5}{8}'],
                            ['id' => 5, 'expression' => '\\frac{11}{x + 4} = -\\frac{11}{7}'],
                            ['id' => 6, 'expression' => '\\frac{8}{x + 9} = -\\frac{2}{9}'],
                            ['id' => 7, 'expression' => '\\frac{7}{x - 5} = 2'],
                            ['id' => 8, 'expression' => '\\frac{4}{x - 4} = -5'],
                            ['id' => 9, 'expression' => '\\frac{11}{x - 9} = -10'],
                            ['id' => 10, 'expression' => '\\frac{7}{x + 8} = -1'],
                            ['id' => 11, 'expression' => '\\frac{6}{x + 5} = -5'],
                            ['id' => 12, 'expression' => '\\frac{11}{x + 3} = 10'],
                            ['id' => 13, 'expression' => '\\frac{3}{x - 19} = \\frac{19}{x - 3}'],
                            ['id' => 14, 'expression' => '\\frac{13}{x - 5} = \\frac{5}{x - 13}'],
                            ['id' => 15, 'expression' => '\\frac{6}{x - 8} = \\frac{8}{x - 6}'],
                        ]
                    ],
                    // II) Квадратные уравнения
                    [
                        'number' => 9,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(5x - 2)(-x + 3) = 0'],
                            ['id' => 2, 'expression' => '(x - 6)(4x - 6) = 0'],
                            ['id' => 3, 'expression' => '(-2x + 1)(-2x - 7) = 0'],
                            ['id' => 4, 'expression' => '(x - 7)(-5x - 9) = 0'],
                            ['id' => 5, 'expression' => '(-5x + 3)(-x + 6) = 0'],
                            ['id' => 6, 'expression' => '(x - 2)(-2x - 3) = 0'],
                        ]
                    ],
                    [
                        'number' => 10,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '3x^2 + 12x = 0'],
                            ['id' => 2, 'expression' => '7x^2 + 21x = 0'],
                            ['id' => 3, 'expression' => '3x^2 + 18x = 0'],
                            ['id' => 4, 'expression' => '5x^2 + 25x = 0'],
                            ['id' => 5, 'expression' => '6x^2 + 24x = 0'],
                            ['id' => 6, 'expression' => '5x^2 + 10x = 0'],
                        ]
                    ],
                    [
                        'number' => 11,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите больший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(-x - 5)(2x + 4) = 0'],
                            ['id' => 2, 'expression' => '(6x - 3)(-x + 3) = 0'],
                            ['id' => 3, 'expression' => '(-x - 4)(3x + 3) = 0'],
                            ['id' => 4, 'expression' => '(5x + 2)(-x - 6) = 0'],
                            ['id' => 5, 'expression' => '(-x + 7)(x - 2) = 0'],
                            ['id' => 6, 'expression' => '(x + 10)(-x - 8) = 0'],
                            ['id' => 7, 'expression' => '3x^2 - 9x = 0'],
                            ['id' => 8, 'expression' => '5x^2 - 10x = 0'],
                            ['id' => 9, 'expression' => '4x^2 - 16x = 0'],
                            ['id' => 10, 'expression' => '7x^2 - 14x = 0'],
                            ['id' => 11, 'expression' => '4x^2 - 20x = 0'],
                            ['id' => 12, 'expression' => '2x^2 - 12x = 0'],
                            ['id' => 13, 'expression' => '9x^2 = 54x'],
                            ['id' => 14, 'expression' => '2x^2 = 8x'],
                            ['id' => 15, 'expression' => '3x^2 = 27x'],
                            ['id' => 16, 'expression' => '4x^2 = 20x'],
                        ]
                    ],
                    [
                        'number' => 12,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 15 = 2x'],
                            ['id' => 2, 'expression' => 'x^2 + 7 = 8x'],
                            ['id' => 3, 'expression' => 'x^2 - 16 = 6x'],
                            ['id' => 4, 'expression' => 'x^2 + 18 = 9x'],
                            ['id' => 5, 'expression' => 'x^2 - 35 = 2x'],
                            ['id' => 6, 'expression' => 'x^2 + 6 = 5x'],
                            ['id' => 7, 'expression' => 'x^2 + 4x = 5'],
                            ['id' => 8, 'expression' => 'x^2 - 6x = 16'],
                            ['id' => 9, 'expression' => 'x^2 + 2x = 15'],
                            ['id' => 10, 'expression' => 'x^2 - 7x = 8'],
                            ['id' => 11, 'expression' => 'x^2 + 4x = 21'],
                            ['id' => 12, 'expression' => 'x^2 - 5x = 14'],
                        ]
                    ],
                    [
                        'number' => 13,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите больший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 18 = 7x'],
                            ['id' => 2, 'expression' => 'x^2 + 6 = 5x'],
                            ['id' => 3, 'expression' => 'x^2 - 21 = 4x'],
                            ['id' => 4, 'expression' => 'x^2 + 10 = 7x'],
                            ['id' => 5, 'expression' => 'x^2 - 20 = x'],
                            ['id' => 6, 'expression' => 'x^2 + 4 = 5x'],
                            ['id' => 7, 'expression' => 'x^2 + 7x = 18'],
                            ['id' => 8, 'expression' => 'x^2 - x = 12'],
                            ['id' => 9, 'expression' => 'x^2 + 3x = 10'],
                            ['id' => 10, 'expression' => 'x^2 - 5x = 14'],
                            ['id' => 11, 'expression' => 'x^2 + 7x = 8'],
                            ['id' => 12, 'expression' => 'x^2 - 3x = 18'],
                        ]
                    ],
                ]
            ],
            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Найдите корень уравнения',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(x - 9)^2 - x^2 = 0'],
                            ['id' => 2, 'expression' => '(x - 7)^2 - x^2 = 0'],
                            ['id' => 3, 'expression' => '(2x - 3)^2 - 4x^2 = 0'],
                            ['id' => 4, 'expression' => '(2x - 5)^2 - 4x^2 = 0'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите меньший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\\frac{1}{5}x^2 - 5 = 0'],
                            ['id' => 2, 'expression' => '\\frac{1}{8}x^2 - 8 = 0'],
                            ['id' => 3, 'expression' => '\\frac{1}{6}x^2 - 24 = 0'],
                            ['id' => 4, 'expression' => '\\frac{1}{7}x^2 - 28 = 0'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Решите уравнение. Если уравнение имеет более одного корня, в ответ запишите больший из корней',
                        'type' => 'expression',
                        'tasks' => [
                            ['id' => 1, 'expression' => '-\\frac{2}{3}x^2 + 6 = 0'],
                            ['id' => 2, 'expression' => '-\\frac{3}{4}x^2 + 12 = 0'],
                            ['id' => 3, 'expression' => '-\\frac{5}{7}x^2 + 35 = 0'],
                            ['id' => 4, 'expression' => '-\\frac{4}{9}x^2 + 36 = 0'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 10
     */
    public function topic10()
    {
        $blocks = $this->getAllBlocksData10();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic10', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 10 - Теория вероятностей
     */
    protected function getAllBlocksData10(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // I) Классическое определение вероятности
                    [
                        'number' => 1,
                        'instruction' => 'Классическое определение вероятности',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'В фирме такси в данный момент свободно 20 машин: 3 чёрные, 3 жёлтые и 14 зелёных. По вызову выехала одна из машин, случайно оказавшаяся ближе всего к заказчику. Найдите вероятность того, что к нему приедет жёлтое такси.'],
                            ['id' => 2, 'text' => 'В фирме такси в данный момент свободно 15 машин: 3 чёрные, 6 жёлтых и 6 зелёных. По вызову выехала одна из машин, случайно оказавшаяся ближе всего к заказчику. Найдите вероятность того, что к нему приедет жёлтое такси.'],
                            ['id' => 3, 'text' => 'В фирме такси в данный момент свободно 30 машин: 6 чёрных, 3 жёлтых и 21 зелёная. По вызову выехала одна из машин, случайно оказавшаяся ближе всего к заказчику. Найдите вероятность того, что к нему приедет жёлтое такси.'],
                            ['id' => 4, 'text' => 'В фирме такси в данный момент свободно 12 машин: 3 чёрных, 3 жёлтых и 6 зелёных. По вызову выехала одна из машин, случайно оказавшаяся ближе всего к заказчику. Найдите вероятность того, что к нему приедет жёлтое такси.'],
                            ['id' => 5, 'text' => 'В фирме такси в данный момент свободно 10 машин: 5 чёрных, 2 жёлтых и 3 зелёных. По вызову выехала одна из машин, случайно оказавшаяся ближе всего к заказчику. Найдите вероятность того, что к нему приедет жёлтое такси.'],
                            ['id' => 6, 'text' => 'В фирме такси в данный момент свободно 30 машин: 1 чёрная, 9 жёлтых и 20 зелёных. По вызову выехала одна из машин, случайно оказавшаяся ближе всего к заказчику. Найдите вероятность того, что к нему приедет жёлтое такси.'],
                            ['id' => 7, 'text' => 'Родительский комитет закупил 10 пазлов для подарков детям в связи с окончанием учебного года, из них 2 с машинами и 8 с видами городов. Подарки распределяются случайным образом между 10 детьми, среди которых есть Андрюша. Найдите вероятность того, что Андрюше достанется пазл с машиной.'],
                            ['id' => 8, 'text' => 'Родительский комитет закупил 25 пазлов для подарков детям в связи с окончанием учебного года, из них 18 с машинами и 7 с видами городов. Подарки распределяются случайным образом между 25 детьми, среди которых есть Володя. Найдите вероятность того, что Володе достанется пазл с машиной.'],
                            ['id' => 9, 'text' => 'Родительский комитет закупил 20 пазлов для подарков детям в связи с окончанием учебного года, из них 6 с машинами и 14 с видами городов. Подарки распределяются случайным образом между 20 детьми, среди которых есть Володя. Найдите вероятность того, что Володе достанется пазл с машиной.'],
                            ['id' => 10, 'text' => 'Родительский комитет закупил 25 пазлов для подарков детям в связи с окончанием учебного года, из них 21 с машинами и 4 с видами городов. Подарки распределяются случайным образом между 25 детьми, среди которых есть Саша. Найдите вероятность того, что Саше достанется пазл с машиной.'],
                            ['id' => 11, 'text' => 'Родительский комитет закупил 15 пазлов для подарков детям в связи с окончанием учебного года, из них 12 с машинами и 3 с видами городов. Подарки распределяются случайным образом между 15 детьми, среди которых есть Миша. Найдите вероятность того, что Мише достанется пазл с машиной.'],
                            ['id' => 12, 'text' => 'Родительский комитет закупил 25 пазлов для подарков детям в связи с окончанием учебного года, из них 24 с машинами и 1 с видом города. Подарки распределяются случайным образом между 25 детьми, среди которых есть Андрюша. Найдите вероятность того, что Андрюше достанется пазл с машиной.'],
                            ['id' => 13, 'text' => 'В лыжных гонках участвуют 7 спортсменов из России, 1 спортсмен из Норвегии и 2 спортсмена из Швеции. Порядок, в котором спортсмены стартуют, определяется жребием. Найдите вероятность того, что первым будет стартовать спортсмен из Швеции.'],
                            ['id' => 14, 'text' => 'В лыжных гонках участвуют 13 спортсменов из России, 2 спортсмена из Норвегии и 5 спортсменов из Швеции. Порядок, в котором спортсмены стартуют, определяется жребием. Найдите вероятность того, что первым будет стартовать спортсмен из Норвегии или Швеции.'],
                            ['id' => 15, 'text' => 'В лыжных гонках участвуют 11 спортсменов из России, 6 спортсменов из Норвегии и 3 спортсмена из Швеции. Порядок, в котором спортсмены стартуют, определяется жребием. Найдите вероятность того, что первым будет стартовать спортсмен не из Норвегии.'],
                            ['id' => 16, 'text' => 'В лыжных гонках участвуют 13 спортсменов из России, 2 спортсмена из Норвегии и 5 спортсменов из Швеции. Порядок, в котором спортсмены стартуют, определяется жребием. Найдите вероятность того, что первым будет стартовать спортсмен из России.'],
                            ['id' => 17, 'text' => 'В лыжных гонках участвуют 7 спортсменов из России, 1 спортсмен из Норвегии и 2 спортсмена из Швеции. Порядок, в котором спортсмены стартуют, определяется жребием. Найдите вероятность того, что первым будет стартовать спортсмен из Норвегии.'],
                            ['id' => 18, 'text' => 'В лыжных гонках участвуют 13 спортсменов из России, 2 спортсмена из Норвегии и 5 спортсменов из Швеции. Порядок, в котором спортсмены стартуют, определяется жребием. Найдите вероятность того, что первым будет стартовать спортсмен не из России.'],
                            ['id' => 19, 'text' => 'У бабушки 20 чашек: 15 с красными цветами, остальные с синими. Бабушка наливает чай в случайно выбранную чашку. Найдите вероятность того, что это будет чашка с синими цветами.'],
                            ['id' => 20, 'text' => 'У бабушки 25 чашек: 7 с красными цветами, остальные с синими. Бабушка наливает чай в случайно выбранную чашку. Найдите вероятность того, что это будет чашка с синими цветами.'],
                            ['id' => 21, 'text' => 'У бабушки 10 чашек: 3 с красными цветами, остальные с синими. Бабушка наливает чай в случайно выбранную чашку. Найдите вероятность того, что это будет чашка с синими цветами.'],
                            ['id' => 22, 'text' => 'У бабушки 20 чашек: 9 с красными цветами, остальные с синими. Бабушка наливает чай в случайно выбранную чашку. Найдите вероятность того, что это будет чашка с синими цветами.'],
                            ['id' => 23, 'text' => 'У бабушки 15 чашек: 9 с красными цветами, остальные с синими. Бабушка наливает чай в случайно выбранную чашку. Найдите вероятность того, что это будет чашка с синими цветами.'],
                            ['id' => 24, 'text' => 'У бабушки 25 чашек: 5 с красными цветами, остальные с синими. Бабушка наливает чай в случайно выбранную чашку. Найдите вероятность того, что это будет чашка с синими цветами.'],
                            ['id' => 25, 'text' => 'На экзамене 30 билетов, Серёжа не выучил 9 из них. Найдите вероятность того, что ему попадётся выученный билет.'],
                            ['id' => 26, 'text' => 'На экзамене 50 билетов, Сеня не выучил 5 из них. Найдите вероятность того, что ему попадётся выученный билет.'],
                            ['id' => 27, 'text' => 'На экзамене 20 билетов, Андрей не выучил 1 из них. Найдите вероятность того, что ему попадётся выученный билет.'],
                            ['id' => 28, 'text' => 'На экзамене 25 билетов, Костя не выучил 4 из них. Найдите вероятность того, что ему попадётся выученный билет.'],
                            ['id' => 29, 'text' => 'На экзамене 35 билетов, Стас не выучил 7 из них. Найдите вероятность того, что ему попадётся выученный билет.'],
                            ['id' => 30, 'text' => 'На экзамене 40 билетов, Яша не выучил 3 из них. Найдите вероятность того, что ему попадётся выученный билет.'],
                            ['id' => 31, 'text' => 'В магазине канцтоваров продаётся 120 ручек: 32 красных, 32 зелёных, 46 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет красной или фиолетовой.'],
                            ['id' => 32, 'text' => 'В магазине канцтоваров продаётся 84 ручки, из них 22 красных, 9 зелёных, 41 фиолетовая, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет красной или фиолетовой.'],
                            ['id' => 33, 'text' => 'В магазине канцтоваров продаётся 144 ручки: 30 красных, 24 зелёных, 18 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет синей или чёрной.'],
                            ['id' => 34, 'text' => 'В магазине канцтоваров продаётся 165 ручек: 37 красных, 16 зелёных, 46 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет синей или чёрной.'],
                            ['id' => 35, 'text' => 'В магазине канцтоваров продаётся 255 ручек: 46 красных, 31 зелёная, 36 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет черной или зеленой.'],
                            ['id' => 36, 'text' => 'В магазине канцтоваров продаётся 112 ручек: 17 красных, 44 зелёных, 29 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет красной или чёрной.'],
                            ['id' => 37, 'text' => 'В магазине канцтоваров продаётся 206 ручек: 20 красных, 8 зелёных, 12 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет красной или синей.'],
                            ['id' => 38, 'text' => 'В магазине канцтоваров продаётся 272 ручки: 11 красных, 37 зелёных, 26 фиолетовых, остальные синие и чёрные, их поровну. Найдите вероятность того, что случайно выбранная в этом магазине ручка будет зелёной или синей.'],
                            ['id' => 39, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что сумма двух выпавших чисел равна 7 или 10.'],
                            ['id' => 40, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что сумма двух выпавших чисел равна 6 или 9.'],
                            ['id' => 41, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что сумма двух выпавших чисел равна 5 или 8.'],
                            ['id' => 42, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что сумма двух выпавших чисел чётна.'],
                            ['id' => 43, 'text' => '(Демо) Симметричный игральный кубик бросают два раза. Найдите вероятность события «сумма выпавших очков равна 9, 10 или 11».'],
                            ['id' => 44, 'text' => '(Демо) Симметричный игральный кубик бросают два раза. Найдите вероятность события «сумма выпавших очков равна 4, 5, 6 или 7».'],
                        ]
                    ],
                    // II) Статистическое определение вероятности
                    [
                        'number' => 2,
                        'instruction' => 'Статистическое определение вероятности',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 45, 'text' => 'В среднем из 150 карманных фонариков, поступивших в продажу, три неисправных. Найдите вероятность того, что выбранный наудачу в магазине фонарик окажется исправен.'],
                            ['id' => 46, 'text' => 'В среднем из 80 карманных фонариков, поступивших в продажу, шесть неисправных. Найдите вероятность того, что выбранный наудачу в магазине фонарик окажется исправен.'],
                            ['id' => 47, 'text' => 'В среднем из 75 карманных фонариков, поступивших в продажу, девять неисправных. Найдите вероятность того, что выбранный наудачу в магазине фонарик окажется исправен.'],
                            ['id' => 48, 'text' => 'В среднем из 100 карманных фонариков, поступивших в продажу, четыре неисправных. Найдите вероятность того, что выбранный наудачу в магазине фонарик окажется исправен.'],
                            ['id' => 49, 'text' => 'В среднем из 50 карманных фонариков, поступивших в продажу, шесть неисправных. Найдите вероятность того, что выбранный наудачу в магазине фонарик окажется исправен.'],
                            ['id' => 50, 'text' => 'В среднем из 200 карманных фонариков, поступивших в продажу, четыре неисправных. Найдите вероятность того, что выбранный наудачу в магазине фонарик окажется исправен.'],
                        ]
                    ],
                    // III) Формулы для вычисления вероятностей
                    [
                        'number' => 3,
                        'instruction' => 'Формулы для вычисления вероятностей',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 51, 'text' => 'Вероятность того, что новая шариковая ручка пишет плохо (или не пишет), равна 0,14. Покупатель в магазине выбирает одну такую ручку. Найдите вероятность того, что эта ручка пишет хорошо.'],
                            ['id' => 52, 'text' => 'Вероятность того, что новая шариковая ручка пишет плохо (или не пишет), равна 0,2. Покупатель в магазине выбирает одну такую ручку. Найдите вероятность того, что эта ручка пишет хорошо.'],
                            ['id' => 53, 'text' => 'Вероятность того, что новая шариковая ручка пишет плохо (или не пишет), равна 0,08. Покупатель в магазине выбирает одну такую ручку. Найдите вероятность того, что эта ручка пишет хорошо.'],
                            ['id' => 54, 'text' => 'Вероятность того, что новая шариковая ручка пишет плохо (или не пишет), равна 0,22. Покупатель в магазине выбирает одну такую ручку. Найдите вероятность того, что эта ручка пишет хорошо.'],
                            ['id' => 55, 'text' => 'Вероятность того, что новая шариковая ручка пишет плохо (или не пишет), равна 0,07. Покупатель в магазине выбирает одну такую ручку. Найдите вероятность того, что эта ручка пишет хорошо.'],
                            ['id' => 56, 'text' => 'Вероятность того, что новая шариковая ручка пишет плохо (или не пишет), равна 0,19. Покупатель в магазине выбирает одну такую ручку. Найдите вероятность того, что эта ручка пишет хорошо.'],
                        ]
                    ],
                ]
            ],
            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // I) Классическое определение вероятности
                    [
                        'number' => 1,
                        'instruction' => 'Классическое определение вероятности',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'На тарелке лежат одинаковые на вид пирожки: 4 с мясом, 5 с рисом и 21 с повидлом. Андрей наугад берёт один пирожок. Найдите вероятность того, что пирожок окажется с повидлом.'],
                            ['id' => 2, 'text' => 'На тарелке лежат одинаковые на вид пирожки: 13 с мясом, 11 с капустой и 6 с вишней. Антон наугад берёт один пирожок. Найдите вероятность того, что пирожок окажется с вишней.'],
                            ['id' => 3, 'text' => 'На тарелке лежат одинаковые на вид пирожки: 14 с рисом, 8 с мясом и 3 с капустой. Петя наугад берёт один пирожок. Найдите вероятность того, что пирожок окажется с капустой.'],
                            ['id' => 4, 'text' => 'На тарелке лежат одинаковые на вид пирожки: 1 с творогом, 12 с мясом и 3 с яблоками. Ваня наугад берёт один пирожок. Найдите вероятность того, что пирожок окажется с мясом.'],
                            ['id' => 5, 'text' => 'На тарелке лежат одинаковые на вид пирожки: 3 с мясом, 3 с капустой и 4 с вишней. Саша наугад берёт один пирожок. Найдите вероятность того, что пирожок окажется с вишней.'],
                            ['id' => 6, 'text' => 'На тарелке лежат одинаковые на вид пирожки: 2 с творогом, 13 с рисом и 5 с яблоками. Лёша наугад берёт один пирожок. Найдите вероятность того, что пирожок окажется с яблоками.'],
                            ['id' => 7, 'text' => 'Петя, Вика, Катя, Игорь, Антон, Полина бросили жребий – кому начинать игру. Найдите вероятность того, что начинать игру должен будет мальчик.'],
                            ['id' => 8, 'text' => 'Саша, Семён, Зоя и Лера бросили жребий – кому начинать игру. Найдите вероятность того, что начинать игру должен будет не Семён.'],
                            ['id' => 9, 'text' => 'Девятиклассники Петя, Катя, Ваня, Даша и Наташа бросили жребий, кому начинать игру. Найдите вероятность того, что начинать игру должна будет девочка.'],
                            ['id' => 10, 'text' => 'Девятиклассники Петя, Катя, Ваня, Даша и Наташа бросили жребий, кому начинать игру. Найдите вероятность того, что жребий начинать игру Кате не выпадет.'],
                            ['id' => 11, 'text' => 'Саша выбирает случайное трёхзначное число. Найдите вероятность того, что оно делится на 4.'],
                            ['id' => 12, 'text' => 'Андрей выбирает случайное трёхзначное число. Найдите вероятность того, что оно делится на 10.'],
                            ['id' => 13, 'text' => 'Валя выбирает случайное трёхзначное число. Найдите вероятность того, что оно делится на 51.'],
                            ['id' => 14, 'text' => 'Коля выбирает случайное трёхзначное число. Найдите вероятность того, что оно делится на 93.'],
                            ['id' => 15, 'text' => 'Женя выбирает случайное трёхзначное число. Найдите вероятность того, что оно делится на 100.'],
                            ['id' => 16, 'text' => 'В случайном эксперименте симметричную монету бросают дважды. Найдите вероятность того, что орел выпадет ровно 1 раз.'],
                            ['id' => 17, 'text' => 'В случайном эксперименте симметричную монету бросают дважды. Найдите вероятность того, что орел выпадет ровно 2 раза.'],
                            ['id' => 18, 'text' => 'В случайном эксперименте симметричную монету бросают трижды. Найдите вероятность того, что орел выпадет ровно 1 раз.'],
                            ['id' => 19, 'text' => 'В случайном эксперименте симметричную монету бросают трижды. Найдите вероятность того, что орел выпадет ровно 2 раза.'],
                            ['id' => 20, 'text' => 'В случайном эксперименте симметричную монету бросают трижды. Найдите вероятность того, что орел выпадет ровно 3 раза.'],
                            ['id' => 21, 'text' => 'В случайном эксперименте симметричную монету бросают четыре раза. Найдите вероятность того, что орел выпадет ровно 1 раз.'],
                            ['id' => 22, 'text' => 'В случайном эксперименте симметричную монету бросают четыре раза. Найдите вероятность того, что орел выпадет ровно 3 раза.'],
                            ['id' => 23, 'text' => 'Определите вероятность того, что при бросании кубика выпало число очков, не большее 3.'],
                            ['id' => 24, 'text' => 'Определите вероятность того, что при бросании кубика выпало число очков, не меньшее 1.'],
                            ['id' => 25, 'text' => 'Определите вероятность того, что при бросании игрального кубика выпадет более 3 очков.'],
                            ['id' => 26, 'text' => 'Определите вероятность того, что при бросании игрального кубика выпадет менее 4 очков.'],
                            ['id' => 27, 'text' => 'Определите вероятность того, что при бросании кубика выпало четное число очков.'],
                            ['id' => 28, 'text' => 'Определите вероятность того, что при бросании кубика выпало нечетное число очков.'],
                            ['id' => 29, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что наибольшее из двух выпавших чисел равно 5.'],
                            ['id' => 30, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что оба раза выпало число, большее 3.'],
                            ['id' => 31, 'text' => 'Игральную кость бросают дважды. Найдите вероятность того, что оба раза выпало число, меньшее 4.'],
                            ['id' => 32, 'text' => 'Игральную кость бросают 2 раза. Найдите вероятность того, что сумма двух выпавших чисел нечётна.'],
                            ['id' => 33, 'text' => 'Игральную кость бросают 2 раза. Найдите вероятность того, что хотя бы раз выпало число, большее 3.'],
                            ['id' => 34, 'text' => 'Игральную кость бросают 2 раза. Найдите вероятность того, что хотя бы раз выпало число, меньшее 4.'],
                        ]
                    ],
                    // II) Статистическое определение вероятности
                    [
                        'number' => 2,
                        'instruction' => 'Статистическое определение вероятности',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 35, 'text' => 'Из 1600 пакетов молока в среднем 80 протекают. Какова вероятность того, что случайно выбранный пакет молока не течёт?'],
                            ['id' => 36, 'text' => 'Из 600 клавиатур для компьютера в среднем 12 не исправны. Какова вероятность того, что случайно выбранная клавиатура исправна?'],
                            ['id' => 37, 'text' => 'Из 1200 чистых компакт-дисков в среднем 72 не пригодны для записи. Какова вероятность, что случайно выбранный диск пригоден для записи?'],
                            ['id' => 38, 'text' => 'Из каждых 1000 электрических лампочек 5 бракованных. Какова вероятность купить исправную лампочку?'],
                            ['id' => 39, 'text' => 'В среднем из каждых 80 поступивших в продажу аккумуляторов 76 аккумуляторов заряжены. Найдите вероятность того, что купленный аккумулятор не заряжен.'],
                            ['id' => 40, 'text' => 'В среднем из каждых 50 поступивших в продажу аккумуляторов 48 аккумуляторов заряжены. Найдите вероятность того, что купленный аккумулятор не заряжен.'],
                            ['id' => 41, 'text' => 'В среднем из каждых 75 поступивших в продажу аккумуляторов 63 аккумулятора заряжены. Найдите вероятность того, что купленный аккумулятор не заряжен.'],
                            ['id' => 42, 'text' => 'В каждой десятой банке кофе согласно условиям акции есть приз. Призы распределены по банкам случайно. Варя покупает банку кофе в надежде выиграть приз. Найдите вероятность того, что Варя не найдет приз в своей банке.'],
                            ['id' => 43, 'text' => 'В каждой двадцать пятой банке кофе согласно условиям акции есть приз. Призы распределены по банкам случайно. Коля покупает банку кофе в надежде выиграть приз. Найдите вероятность того, что Коля не найдёт приз в своей банке.'],
                            ['id' => 44, 'text' => 'В каждой четвертой банке кофе согласно условиям акции есть приз. Призы распределены по банкам случайно. Аля покупает банку кофе в надежде выиграть приз. Найдите вероятность того, что Аля не найдет приз в своей банке.'],
                            ['id' => 45, 'text' => 'Известно, что в некотором регионе вероятность того, что родившийся младенец окажется мальчиком, равна 0,512. В 2010 г. в этом регионе на 1000 родившихся младенцев в среднем пришлось 477 девочек. Насколько частота рождения девочек в 2010 г. в этом регионе отличается от вероятности этого события?'],
                            ['id' => 46, 'text' => 'Известно, что в некотором регионе вероятность того, что родившийся младенец окажется мальчиком, равна 0,486. В 2011 г. в этом регионе на 1000 родившихся младенцев в среднем пришлось 522 девочки. Насколько частота рождения девочки в 2011 г. в этом регионе отличается от вероятности этого события?'],
                            ['id' => 47, 'text' => 'Известно, что в некотором регионе вероятность того, что родившийся младенец окажется мальчиком, равна 0,52. В 2013 г. в этом регионе на 1000 родившихся младенцев в среднем пришлось 486 девочек. Насколько частота рождения девочки в 2013 г. в этом регионе отличается от вероятности этого события?'],
                            ['id' => 48, 'text' => 'Во время вероятностного эксперимента монету бросили 1000 раз, 532 раза выпал орел. На сколько частота выпадения решки в этом эксперименте отличается от вероятности этого события?'],
                            ['id' => 49, 'text' => 'Во время вероятностного эксперимента монету бросили 1000 раз, 449 раз выпала решка. На сколько частота выпадения орла в этом эксперименте отличается от вероятности этого события?'],
                        ]
                    ],
                    // III) Формулы для вычисления вероятностей
                    [
                        'number' => 3,
                        'instruction' => 'Формулы для вычисления вероятностей',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 50, 'text' => 'На экзамене по геометрии школьнику достаётся одна задача из сборника. Вероятность того, что эта задача по теме «Площадь», равна 0,15. Вероятность того, что это окажется задача по теме «Окружность», равна 0,3. В сборнике нет задач, которые одновременно относятся к этим двум темам. Найдите вероятность того, что на экзамене школьнику достанется задача по одной из этих двух тем.'],
                            ['id' => 51, 'text' => 'На экзамене по геометрии школьнику достаётся одна задача из сборника. Вероятность того, что эта задача по теме «Параллелограмм», равна 0,45. Вероятность того, что это окажется задача по теме «Треугольники», равна 0,15. В сборнике нет задач, которые одновременно относятся к этим двум темам. Найдите вероятность того, что на экзамене школьнику достанется задача по одной из этих двух тем.'],
                            ['id' => 52, 'text' => 'На экзамене по геометрии школьнику достаётся одна задача из сборника. Вероятность того, что эта задача по теме «Трапеция», равна 0,1. Вероятность того, что это окажется задача по теме «Площадь», равна 0,3. В сборнике нет задач, которые одновременно относятся к этим двум темам. Найдите вероятность того, что на экзамене школьнику достанется задача по одной из этих двух тем.'],
                            ['id' => 53, 'text' => 'Стрелок 3 раза стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,8. Найдите вероятность того, что стрелок первые 2 раза попал в мишени, а последний раз промахнулся.'],
                            ['id' => 54, 'text' => 'Стрелок 3 раза стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,6. Найдите вероятность того, что стрелок первый раз попал в мишени, а последние два раза промахнулся.'],
                            ['id' => 55, 'text' => 'Стрелок 4 раза стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,5. Найдите вероятность того, что стрелок первые 3 раза попал в мишени, а последний раз промахнулся.'],
                            ['id' => 56, 'text' => 'Стрелок 4 раза стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,5. Найдите вероятность того, что стрелок первые 2 раза попал в мишени, а последние 2 раза промахнулся.'],
                            ['id' => 57, 'text' => 'Стрелок 4 раза стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,7. Найдите вероятность того, что стрелок первый раз попал в мишени, а последние 3 раза промахнулся.'],
                            ['id' => 58, 'text' => 'Стрелок 5 раз стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,9. Найдите вероятность того, что стрелок первые 3 раза попал в мишени, а последние два раза промахнулся.'],
                            ['id' => 59, 'text' => 'Стрелок 5 раз стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,9. Найдите вероятность того, что стрелок первые 2 раза попал в мишени, а последние 3 раза промахнулся.'],
                            ['id' => 60, 'text' => 'Стрелок 5 раз стреляет по мишеням. Вероятность попадания в мишень при одном выстреле равна 0,8. Найдите вероятность того, что стрелок первый раз попал в мишени, а последние 4 раза промахнулся.'],
                        ]
                    ],
                ]
            ],
            // =====================
            // БЛОК 3. Типовые экзаменационные варианты
            // =====================
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    // I) Классическое определение вероятности
                    [
                        'number' => 1,
                        'instruction' => 'Классическое определение вероятности',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'В одиннадцатом физико-математическом классе учатся 10 мальчиков и 6 девочек. По жребию они выбирают одного дежурного по классу. Какова вероятность, что это будет мальчик?'],
                            ['id' => 2, 'text' => 'В одиннадцатом физико-математическом классе учатся 15 мальчиков и 5 девочек. По жребию они выбирают одного дежурного по классу. Какова вероятность, что это будет мальчик?'],
                            ['id' => 3, 'text' => 'В десятом физико-математическом классе учатся 19 мальчиков и 6 девочек. По жребию они выбирают одного дежурного по классу. Какова вероятность, что это будет мальчик?'],
                            ['id' => 4, 'text' => 'В девятом физико-математическом классе учатся 17 мальчиков и 3 девочки. По жребию они выбирают одного дежурного по классу. Какова вероятность, что это будет мальчик?'],
                            ['id' => 5, 'text' => 'В группе туристов 20 человек. С помощью жребия они выбирают трёх человек, которые должны идти в село в магазин за продуктами. Какова вероятность того, что турист К., входящий в состав группы, пойдёт в магазин?'],
                            ['id' => 6, 'text' => 'В группе туристов 8 человек. С помощью жребия они выбирают трёх человек, которые должны идти в село в магазин за продуктами. Какова вероятность того, что турист Д., входящий в состав группы, пойдёт в магазин?'],
                            ['id' => 7, 'text' => 'В сборнике билетов по физике всего 40 билетов, в 6 из них встречается вопрос по теме «Термодинамика». Найдите вероятность того, что в случайно выбранном на экзамене билете школьнику достанется вопрос по теме «Термодинамика».'],
                            ['id' => 8, 'text' => 'В сборнике билетов по физике всего 50 билетов, в 8 из них встречается вопрос по теме «Механика». Найдите вероятность того, что в случайно выбранном на экзамене билете школьнику достанется вопрос по теме «Механика».'],
                            ['id' => 9, 'text' => 'Научная конференция проводится в 3 дня. Всего запланировано 50 докладов: в первый день – 16 докладов, остальные распределены поровну между вторым и третьим днями. На конференции планируется доклад профессора Н. Порядок докладов определяется случайным образом. Какова вероятность того, что доклад профессора Н. окажется запланированным на последний день конференции?'],
                            ['id' => 10, 'text' => 'Научная конференция проводится в 4 дня. Всего запланировано 50 докладов: первые два дня – по 13 докладов, остальные распределены поровну между 3-им и 4-ым днями. На конференции планируется доклад профессора К. Порядок докладов определяется случайным образом. Какова вероятность, что доклад профессора К. окажется запланированным на последний день конференции?'],
                            ['id' => 11, 'text' => 'На олимпиаде по химии участников рассаживают по трём аудиториям. В первых двух по 110 человек, оставшихся проводят в запасную аудиторию в другом корпусе. При подсчёте выяснилось, что всего было 400 участников. Найдите вероятность того, что случайно выбранный участник писал олимпиаду в запасной аудитории.'],
                            ['id' => 12, 'text' => 'На олимпиаде по биологии участников рассаживают по трём аудиториям. В первых двух по 130 человек, оставшихся проводят в запасную аудиторию в другом корпусе. При подсчёте выяснилось, что всего было 400 участников. Найдите вероятность того, что случайно выбранный участник писал олимпиаду в запасной аудитории.'],
                            ['id' => 13, 'text' => 'В коробке вперемешку лежат чайные пакетики с чёрным и зелёным чаем, одинаковые на вид, причём пакетиков с чёрным чаем в 4 раза больше, чем пакетиков с зелёным. Найдите вероятность того, что случайно выбранный из этой коробки пакетик окажется пакетиком с черным чаем.'],
                            ['id' => 14, 'text' => 'В коробке вперемешку лежат чайные пакетики с чёрным и зелёным чаем, одинаковые на вид, причём пакетиков с зеленым чаем в 7 раза меньше, чем пакетиков с черным. Найдите вероятность того, что случайно выбранный из этой коробки пакетик окажется пакетиком с черным чаем.'],
                            ['id' => 15, 'text' => 'На птицеферме есть только куры и гуси, причём кур в 19 раз больше, чем гусей. Найдите вероятность того, что случайно выбранная на этой ферме птица окажется гусем.'],
                            ['id' => 16, 'text' => 'На птицеферме есть только куры и гуси, причём кур в 4 раза больше, чем гусей. Найдите вероятность того, что случайно выбранная на этой ферме птица окажется гусем.'],
                            ['id' => 17, 'text' => 'Перед началом первого тура чемпионата по шашкам участников разбивают на игровые пары случайным образом с помощью жребия. Всего в чемпионате участвует 71 спортсмен, среди которых 22 спортсменов из России, в том числе Т. Найдите вероятность того, что в первом туре Т. будет играть с каким-либо спортсменом из России.'],
                            ['id' => 18, 'text' => 'Перед началом первого тура чемпионата по теннису участников разбивают на игровые пары случайным образом с помощью жребия. Всего в чемпионате участвует 51 спортсмен, среди которых 14 спортсменов из России, в том числе Д. Найдите вероятность того, что в первом туре Д. будет играть с каким-либо спортсменом не из России.'],
                            ['id' => 19, 'text' => 'За круглый стол на 11 стульев в случайном порядке рассаживаются 9 мальчиков и 2 девочки. Найдите вероятность того, что девочки окажутся на соседних местах.'],
                            ['id' => 20, 'text' => 'За круглый стол на 9 стульев в случайном порядке рассаживаются 7 мальчиков и 2 девочки. Найдите вероятность того, что девочки окажутся на соседних местах.'],
                            ['id' => 21, 'text' => 'За круглый стол на 11 стул в случайном порядке рассаживаются 9 мальчиков и 2 девочки. Найдите вероятность того, что девочки не окажутся на соседних местах.'],
                            ['id' => 22, 'text' => 'За круглый стол на 21 стул в случайном порядке рассаживаются 19 мальчиков и 2 девочки. Найдите вероятность того, что девочки не окажутся на соседних местах.'],
                            ['id' => 23, 'text' => 'Правильную игральную кость бросают дважды. Известно, что сумма выпавших очков больше 8. Найдите вероятность события «при втором броске выпало 3 очка».'],
                            ['id' => 24, 'text' => 'Правильную игральную кость бросают дважды. Известно, что сумма выпавших очков больше 8. Найдите вероятность события «при втором броске выпало 4 очка».'],
                            ['id' => 25, 'text' => 'На фестивале выступают группы – по одной от каждой из заявленных стран, среди этих стран Россия, Великобритания и Франция. Порядок выступления определяется жребием. Какова вероятность того, что группа из Франции будет выступать после группы из Великобритании и после группы из России? Результат округлите до сотых.'],
                            ['id' => 26, 'text' => 'На фестивале выступают группы - по одной от каждой из заявленных стран, среди этих стран Испания, Португалия и Италия. Порядок выступления определяется жребием. Какова вероятность того, что группа из Испании будет выступать до группы из Португалии и до группы из Италии? Результат округлите до сотых.'],
                        ]
                    ],
                    // II) Статистическое определение вероятности
                    [
                        'number' => 2,
                        'instruction' => 'Статистическое определение вероятности',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 27, 'text' => 'Фабрика выпускает сумки. В среднем из 120 сумок 6 сумок имеют скрытый дефект. Найдите вероятность того, что случайно выбранная сумка окажется без дефектов.'],
                            ['id' => 28, 'text' => 'Фабрика выпускает сумки. В среднем из 150 сумок 3 сумки имеют скрытый дефект. Найдите вероятность того, что случайно выбранная сумка окажется без дефектов.'],
                        ]
                    ],
                    // III) Формулы для вычисления вероятностей
                    [
                        'number' => 3,
                        'instruction' => 'Формулы для вычисления вероятностей',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 29, 'text' => 'Вероятность того, что новый принтер прослужит больше года, равна 0,95. Вероятность того, что он прослужит два года или больше 0,88. Найдите вероятность того, что он прослужит меньше двух лет, но не менее года.'],
                            ['id' => 30, 'text' => 'Вероятность того, что новый принтер прослужит больше года, равна 0,96. Вероятность того, что он прослужит два года или больше 0,74. Найдите вероятность того, что он прослужит меньше двух лет, но не менее года.'],
                            ['id' => 31, 'text' => 'Вероятность того, что новый сканер прослужит больше года, равна 0,96. Вероятность того, что он прослужит два года или больше 0,87. Найдите вероятность того, что он прослужит меньше двух лет, но не менее года.'],
                            ['id' => 32, 'text' => 'Вероятность того, что новый сканер прослужит больше года, равна 0,95. Вероятность того, что он прослужит два года или больше 0,77. Найдите вероятность того, что он прослужит меньше двух лет, но не менее года.'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 11
     */
    public function topic11()
    {
        $blocks = $this->getAllBlocksData11();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic11', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 11 - Графики функций
     */
    protected function getAllBlocksData11(): array
    {
        return [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Установите соответствие между графиками функций и формулами, которые их задают',
                        'type' => 'matching',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-000.png', 'options' => ['y = x + 3', 'y = 3', 'y = 3x']],
                            ['id' => 2, 'image' => 'img-001.png', 'options' => ['y = -2x - 1', 'y = 2x + 1', 'y = -2x + 1']],
                            ['id' => 3, 'image' => 'img-002.png', 'options' => ['y = -x', 'y = -1', 'y = x - 1']],
                            ['id' => 4, 'image' => 'img-003.png', 'options' => ['y = 2x + 4', 'y = -2x + 4', 'y = -2x - 4']],
                            ['id' => 5, 'image' => 'img-004.png', 'options' => ['y = \\frac{2}{5}x + 2', 'y = \\frac{2}{5}x - 2', 'y = -\\frac{2}{5}x + 2']],
                            ['id' => 6, 'image' => 'img-005.png', 'options' => ['y = -\\frac{2}{3}x - 5', 'y = \\frac{2}{3}x + 5', 'y = \\frac{2}{3}x - 5']],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Установите соответствие между графиками функций и формулами',
                        'type' => 'matching',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-006.jpeg', 'options' => ['y = 2x + 6', 'y = -2x + 6', 'y = -2x - 6']],
                            ['id' => 2, 'image' => 'img-007.png', 'options' => ['y = -3x', 'y = -\\frac{1}{3}x', 'y = \\frac{1}{3}x']],
                            ['id' => 3, 'image' => 'img-008.jpeg', 'options' => ['y = -2x - 4', 'y = 2x - 4', 'y = -2x + 4']],
                            ['id' => 4, 'image' => 'img-009.jpeg', 'options' => ['y = 3x', 'y = -3x', 'y = \\frac{1}{3}x']],
                            ['id' => 5, 'image' => 'img-010.jpeg', 'options' => ['y = \\frac{1}{2}x - 2', 'y = -\\frac{1}{2}x + 2', 'y = -\\frac{1}{2}x - 2']],
                            ['id' => 6, 'image' => 'img-011.jpeg', 'options' => ['y = -\\frac{1}{2}x + 3', 'y = \\frac{1}{2}x + 3', 'y = \\frac{1}{2}x - 3']],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Установите соответствие между графиками y = kx + b и знаками коэффициентов',
                        'type' => 'matching_signs',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-012.png', 'options' => ['k > 0, b < 0', 'k < 0, b < 0', 'k > 0, b > 0']],
                            ['id' => 2, 'image' => 'img-013.png', 'options' => ['k < 0, b < 0', 'k < 0, b > 0', 'k > 0, b > 0']],
                            ['id' => 3, 'image' => 'img-014.png', 'options' => ['k < 0, b > 0', 'k < 0, b < 0', 'k > 0, b > 0']],
                            ['id' => 4, 'image' => 'img-015.png', 'options' => ['k < 0, b > 0', 'k < 0, b < 0', 'k > 0, b < 0']],
                            ['id' => 5, 'image' => 'img-016.png', 'options' => ['k > 0, b > 0', 'k < 0, b > 0', 'k > 0, b < 0']],
                            ['id' => 6, 'image' => 'img-017.png', 'options' => ['k < 0, b < 0', 'k > 0, b > 0', 'k > 0, b < 0']],
                            ['id' => 7, 'image' => 'img-018.png', 'options' => ['k < 0, b < 0', 'k < 0, b > 0', 'k > 0, b < 0']],
                            ['id' => 8, 'image' => 'img-019.png', 'options' => ['k < 0, b < 0', 'k < 0, b > 0', 'k > 0, b < 0']],
                            ['id' => 9, 'image' => 'img-020.png', 'options' => ['k < 0, b < 0', 'k > 0, b > 0', 'k < 0, b > 0']],
                            ['id' => 10, 'image' => 'img-021.png', 'options' => ['k < 0, b < 0', 'k > 0, b < 0', 'k > 0, b > 0']],
                            ['id' => 11, 'image' => 'img-022.png', 'options' => ['k < 0, b > 0', 'k > 0, b > 0', 'k > 0, b < 0']],
                            ['id' => 12, 'image' => 'img-023.png', 'options' => ['k < 0, b > 0', 'k > 0, b > 0', 'k > 0, b < 0']],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Установите соответствие между графиками y = ax² + bx + c и знаками коэффициентов',
                        'type' => 'matching_signs',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-024.png', 'options' => ['a < 0, c > 0', 'a > 0, c < 0', 'a > 0, c > 0']],
                            ['id' => 2, 'image' => 'img-025.png', 'options' => ['a > 0, c < 0', 'a > 0, c > 0', 'a < 0, c > 0']],
                            ['id' => 3, 'image' => 'img-026.png', 'options' => ['a < 0, c > 0', 'a > 0, c < 0', 'a < 0, c < 0']],
                            ['id' => 4, 'image' => 'img-027.png', 'options' => ['a < 0, c > 0', 'a > 0, c > 0', 'a > 0, c < 0']],
                            ['id' => 5, 'image' => 'img-028.png', 'options' => ['a < 0, c > 0', 'a > 0, c > 0', 'a > 0, c < 0']],
                            ['id' => 6, 'image' => 'img-029.png', 'options' => ['a < 0, c < 0', 'a > 0, c > 0', 'a < 0, c > 0']],
                            ['id' => 7, 'image' => 'img-030.png', 'options' => ['a < 0, c > 0', 'a > 0, c > 0', 'a > 0, c < 0']],
                            ['id' => 8, 'image' => 'img-031.png', 'options' => ['a > 0, c > 0', 'a < 0, c > 0', 'a > 0, c < 0']],
                            ['id' => 9, 'image' => 'img-032.png', 'options' => ['a < 0, c > 0', 'a > 0, c < 0', 'a > 0, c > 0']],
                            ['id' => 10, 'image' => 'img-033.png', 'options' => ['a < 0, c > 0', 'a > 0, c > 0', 'a > 0, c < 0']],
                            ['id' => 11, 'image' => 'img-034.png', 'options' => ['a < 0, c > 0', 'a > 0, c > 0', 'a > 0, c < 0']],
                            ['id' => 12, 'image' => 'img-035.png', 'options' => ['a < 0, c < 0', 'a > 0, c > 0', 'a < 0, c > 0']],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Установите соответствие между графиками и формулами (разные типы функций)',
                        'type' => 'matching',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-036.png', 'options' => ['y = -\\frac{1}{2}x', 'y = x', 'y = -x² - 2']],
                            ['id' => 2, 'image' => 'img-037.png', 'options' => ['y = -\\frac{2}{x}', 'y = 2x', 'y = x² - 2']],
                            ['id' => 3, 'image' => 'img-038.png', 'options' => ['y = \\frac{6}{x}', 'y = -2x + 4', 'y = -2x²']],
                            ['id' => 4, 'image' => 'img-039.png', 'options' => ['y = \\frac{1}{2}x', 'y = 2 - x²', 'y = x']],
                            ['id' => 5, 'image' => 'img-040.png', 'options' => ['y = -x² - 4', 'y = x', 'y = -2x - 4']],
                            ['id' => 6, 'image' => 'img-041.png', 'options' => ['y = -\\frac{1}{x}', 'y = 4 - x²', 'y = 2x + 4']],
                        ]
                    ],
                ]
            ],
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Какие утверждения о квадратичной функции y = f(x) верны?',
                        'type' => 'statements',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-042.png', 'statements' => ['f(-1) = f(5)', 'функция убывает на [2; +∞)', 'f(x) > 0 при x < -1 и x > 5']],
                            ['id' => 2, 'image' => 'img-043.png', 'statements' => ['наибольшее значение функции равно 3', 'функция возрастает на (-∞; 1]', 'f(x) ≤ 0 при x = -1']],
                            ['id' => 3, 'image' => 'img-044.png', 'statements' => ['наименьшее значение функции равно -9', 'f(-4) > f(1)', 'f(x) < 0 при x = -4']],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Какие утверждения о квадратичной функции y = f(x) неверны?',
                        'type' => 'statements',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-045.png', 'statements' => ['Функция возрастает на (-∞; -1]', 'Наибольшее значение равно 8', 'f(-4) ≤ f(2)']],
                            ['id' => 2, 'image' => 'img-046.png', 'statements' => ['Функция убывает на [1; +∞)', 'Наименьшее значение равно -4', 'f(-2) ≤ f(3)']],
                            ['id' => 3, 'image' => 'img-047.png', 'statements' => ['функция возрастает на [2; +∞)', 'f(x) > 0 при -1 < x < 5', 'f(0) < f(4)']],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Установите соответствие между графиками y = ax² + c и знаками коэффициентов',
                        'type' => 'matching_4',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-048.png', 'options' => ['a > 0, c < 0', 'a < 0, c > 0', 'a > 0, c > 0', 'a < 0, c < 0']],
                            ['id' => 2, 'image' => 'img-049.png', 'options' => ['a > 0, c > 0', 'a > 0, c < 0', 'a < 0, c < 0', 'a < 0, c > 0']],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Установите соответствие между графиками квадратичных функций и формулами',
                        'type' => 'matching',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-050.png', 'options' => ['y = 2x² - 10x + 8', 'y = -2x² + 10x - 8', 'y = -2x² - 10x - 8']],
                            ['id' => 2, 'image' => 'img-051.png', 'options' => ['y = x² - 7x + 14', 'y = x² + 7x + 14', 'y = -x² - 7x - 14']],
                            ['id' => 3, 'image' => 'img-052.png', 'options' => ['y = -3x² + 3x + 1', 'y = 3x² - 3x - 1', 'y = -3x² - 3x + 1']],
                            ['id' => 4, 'image' => 'img-053.png', 'options' => ['y = x² + 8x + 12', 'y = x² - 8x + 12', 'y = -x² + 8x - 12']],
                            ['id' => 5, 'image' => 'img-054.png', 'options' => ['y = x² - 7x + 9', 'y = -x² - 7x - 9', 'y = -x² + 7x - 9']],
                            ['id' => 6, 'image' => 'img-055.png', 'options' => ['y = -3x² + 24x - 42', 'y = 3x² - 24x + 42', 'y = -3x² - 24x - 42']],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Установите соответствие между графиками гипербол и формулами',
                        'type' => 'matching',
                        'tasks' => [
                            ['id' => 1, 'image' => 'img-056.png', 'options' => ['y = -\\frac{1}{2x}', 'y = -\\frac{2}{x}', 'y = \\frac{2}{x}']],
                            ['id' => 2, 'image' => 'img-057.png', 'options' => ['y = -\\frac{1}{3x}', 'y = \\frac{3}{x}', 'y = -\\frac{3}{x}']],
                            ['id' => 3, 'image' => 'img-058.png', 'options' => ['y = \\frac{6}{x}', 'y = \\frac{1}{6x}', 'y = -\\frac{6}{x}']],
                            ['id' => 4, 'image' => 'img-059.png', 'options' => ['y = \\frac{8}{x}', 'y = -\\frac{1}{8x}', 'y = -\\frac{8}{x}']],
                            ['id' => 5, 'image' => 'img-060.png', 'options' => ['y = \\frac{1}{9x}', 'y = \\frac{9}{x}', 'y = -\\frac{9}{x}']],
                            ['id' => 6, 'image' => 'img-061.png', 'options' => ['y = \\frac{12}{x}', 'y = -\\frac{12}{x}', 'y = -\\frac{1}{12x}']],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 12
     */
    public function topic12()
    {
        $blocks = $this->getAllBlocksData12();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic12', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 12 - Расчеты по формулам
     */
    protected function getAllBlocksData12(): array
    {
        return [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Экономика: стоимость колодца',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'В фирме «Родник» стоимость (в рублях) колодца из железобетонных колец рассчитывается по формуле С = 6000 + 4100n, где n – число колец. Рассчитайте стоимость колодца из 10 колец.'],
                            ['id' => 2, 'text' => 'В фирме «Родник» стоимость (в рублях) колодца из железобетонных колец рассчитывается по формуле С = 6000 + 4100n, где n – число колец. Рассчитайте стоимость колодца из 5 колец.'],
                            ['id' => 3, 'text' => 'В фирме «Родник» стоимость (в рублях) колодца из железобетонных колец рассчитывается по формуле С = 6000 + 4100n, где n – число колец. Рассчитайте стоимость колодца из 9 колец.'],
                            ['id' => 4, 'text' => 'В фирме «Чистая вода» стоимость (в рублях) колодца рассчитывается по формуле С = 6500 + 4000n, где n – число колец. Рассчитайте стоимость колодца из 14 колец.'],
                            ['id' => 5, 'text' => 'В фирме «Чистая вода» стоимость (в рублях) колодца рассчитывается по формуле С = 6500 + 4000n, где n – число колец. Рассчитайте стоимость колодца из 12 колец.'],
                            ['id' => 6, 'text' => 'В фирме «Чистая вода» стоимость (в рублях) колодца рассчитывается по формуле С = 6500 + 4000n, где n – число колец. Рассчитайте стоимость колодца из 13 колец.'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Физика: перевод температуры Цельсия в Фаренгейт',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 7, 'text' => 'Пользуясь формулой tF = 1,8tC + 32, найдите, скольким градусам по шкале Фаренгейта соответствует −23 градуса по шкале Цельсия.'],
                            ['id' => 8, 'text' => 'Пользуясь формулой tF = 1,8tC + 32, найдите, скольким градусам по шкале Фаренгейта соответствует 35 градусов по шкале Цельсия.'],
                            ['id' => 9, 'text' => 'Пользуясь формулой tF = 1,8tC + 32, найдите, скольким градусам по шкале Фаренгейта соответствует −85 градусов по шкале Цельсия.'],
                            ['id' => 10, 'text' => 'Пользуясь формулой tF = 1,8tC + 32, найдите, скольким градусам по шкале Фаренгейта соответствует 55 градусов по шкале Цельсия.'],
                            ['id' => 11, 'text' => 'Пользуясь формулой tF = 1,8tC + 32, найдите, скольким градусам по шкале Фаренгейта соответствует −70 градусов по шкале Цельсия.'],
                            ['id' => 12, 'text' => 'Пользуясь формулой tF = 1,8tC + 32, найдите, скольким градусам по шкале Фаренгейта соответствует 90 градусов по шкале Цельсия.'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Физика: перевод температуры Фаренгейта в Цельсий',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 13, 'text' => 'Пользуясь формулой tC = 5/9(tF − 32), найдите, скольким градусам по шкале Цельсия соответствует 149 градусов по шкале Фаренгейта.'],
                            ['id' => 14, 'text' => 'Пользуясь формулой tC = 5/9(tF − 32), найдите, скольким градусам по шкале Цельсия соответствует −112 градусов по шкале Фаренгейта.'],
                            ['id' => 15, 'text' => 'Пользуясь формулой tC = 5/9(tF − 32), найдите, скольким градусам по шкале Цельсия соответствует 185 градусов по шкале Фаренгейта.'],
                            ['id' => 16, 'text' => 'Пользуясь формулой tC = 5/9(tF − 32), найдите, скольким градусам по шкале Цельсия соответствует −58 градусов по шкале Фаренгейта.'],
                            ['id' => 17, 'text' => 'Пользуясь формулой tC = 5/9(tF − 32), найдите, скольким градусам по шкале Цельсия соответствует 23 градуса по шкале Фаренгейта.'],
                            ['id' => 18, 'text' => 'Пользуясь формулой tC = 5/9(tF − 32), найдите, скольким градусам по шкале Цельсия соответствует −103 градуса по шкале Фаренгейта.'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Физика: сила Архимеда',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 19, 'text' => 'Сила Архимеда вычисляется по формуле F = ρgV, где ρ = 1000 кг/м³, g = 9,8 м/с². Найдите силу, действующую на тело объёмом 0,5 м³. Ответ в ньютонах.'],
                            ['id' => 20, 'text' => 'Сила Архимеда вычисляется по формуле F = ρgV, где ρ = 1000 кг/м³, g = 9,8 м/с². Найдите силу, действующую на тело объёмом 0,7 м³. Ответ в ньютонах.'],
                            ['id' => 21, 'text' => 'Сила Архимеда вычисляется по формуле F = ρgV, где ρ = 1000 кг/м³, g = 9,8 м/с². Найдите силу, действующую на тело объёмом 0,4 м³. Ответ в ньютонах.'],
                            ['id' => 22, 'text' => 'Сила Архимеда вычисляется по формуле F = ρgV, где ρ = 1000 кг/м³, g = 9,8 м/с². Найдите силу, действующую на тело объёмом 0,08 м³. Ответ в ньютонах.'],
                            ['id' => 23, 'text' => 'Сила Архимеда вычисляется по формуле F = ρgV, где ρ = 1000 кг/м³, g = 9,8 м/с². Найдите силу, действующую на тело объёмом 0,06 м³. Ответ в ньютонах.'],
                            ['id' => 24, 'text' => 'Сила Архимеда вычисляется по формуле F = ρgV, где ρ = 1000 кг/м³, g = 9,8 м/с². Найдите силу, действующую на тело объёмом 0,09 м³. Ответ в ньютонах.'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Физика: потенциальная энергия',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 25, 'text' => 'Потенциальная энергия вычисляется по формуле P = mgh, g = 9,8 м/с². Найдите массу тела на высоте 8 м, если P = 784 Дж.'],
                            ['id' => 26, 'text' => 'Потенциальная энергия вычисляется по формуле P = mgh, g = 9,8 м/с². Найдите массу тела на высоте 3 м, если P = 588 Дж.'],
                            ['id' => 27, 'text' => 'Потенциальная энергия вычисляется по формуле P = mgh, g = 9,8 м/с². Найдите массу тела на высоте 6 м, если P = 1764 Дж.'],
                            ['id' => 28, 'text' => 'Потенциальная энергия вычисляется по формуле P = mgh, g = 9,8 м/с². Найдите массу тела на высоте 10 м, если P = 686 Дж.'],
                            ['id' => 29, 'text' => 'Потенциальная энергия вычисляется по формуле P = mgh, g = 9,8 м/с². Найдите массу тела на высоте 40 м, если P = 3528 Дж.'],
                            ['id' => 30, 'text' => 'Потенциальная энергия вычисляется по формуле P = mgh, g = 9,8 м/с². Найдите массу тела на высоте 20 м, если P = 1568 Дж.'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'Физика: энергия конденсатора',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 31, 'text' => 'Энергия конденсатора W = CU²/2. Найдите W, если C = 2·10⁻⁴ Ф, U = 14 В.'],
                            ['id' => 32, 'text' => 'Энергия конденсатора W = CU²/2. Найдите W, если C = 2·10⁻⁴ Ф, U = 13 В.'],
                            ['id' => 33, 'text' => 'Энергия конденсатора W = CU²/2. Найдите W, если C = 2·10⁻⁴ Ф, U = 17 В.'],
                            ['id' => 34, 'text' => 'Энергия конденсатора W = CU²/2. Найдите W, если C = 10⁻⁴ Ф, U = 30 В.'],
                            ['id' => 35, 'text' => 'Энергия конденсатора W = CU²/2. Найдите W, если C = 10⁻⁴ Ф, U = 50 В.'],
                            ['id' => 36, 'text' => 'Энергия конденсатора W = CU²/2. Найдите W, если C = 10⁻⁴ Ф, U = 20 В.'],
                        ]
                    ],
                    [
                        'number' => 7,
                        'instruction' => 'Физика: кинетическая энергия',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 37, 'text' => 'Кинетическая энергия E = mv²/2. Автомобиль массой 1500 кг имеет энергию 48000 Дж. Найдите скорость в м/с.'],
                            ['id' => 38, 'text' => 'Кинетическая энергия E = mv²/2. Автомобиль массой 2800 кг имеет энергию 315000 Дж. Найдите скорость в м/с.'],
                            ['id' => 39, 'text' => 'Кинетическая энергия E = mv²/2. Автомобиль массой 2000 кг имеет энергию 324000 Дж. Найдите скорость в м/с.'],
                            ['id' => 40, 'text' => 'Кинетическая энергия E = mv²/2. Автомобиль массой 2500 кг имеет энергию 180000 Дж. Найдите скорость в м/с.'],
                            ['id' => 41, 'text' => 'Кинетическая энергия E = mv²/2. Автомобиль массой 1400 кг имеет энергию 280000 Дж. Найдите скорость в м/с.'],
                            ['id' => 42, 'text' => 'Кинетическая энергия E = mv²/2. Автомобиль массой 1500 кг имеет энергию 147000 Дж. Найдите скорость в м/с.'],
                        ]
                    ],
                    [
                        'number' => 8,
                        'instruction' => 'Физика: мощность тока',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 43, 'text' => 'Мощность P = I²R. Найдите R, если P = 15,75 Вт, I = 1,5 А.'],
                            ['id' => 44, 'text' => 'Мощность P = I²R. Найдите R, если P = 283,5 Вт, I = 4,5 А.'],
                            ['id' => 45, 'text' => 'Мощность P = I²R. Найдите R, если P = 361,25 Вт, I = 8,5 А.'],
                            ['id' => 46, 'text' => 'Мощность P = I²R. Найдите R, если P = 29,25 Вт, I = 1,5 А.'],
                            ['id' => 47, 'text' => 'Мощность P = I²R. Найдите R, если P = 423,5 Вт, I = 5,5 А.'],
                            ['id' => 48, 'text' => 'Мощность P = I²R. Найдите R, если P = 541,5 Вт, I = 9,5 А.'],
                        ]
                    ],
                    [
                        'number' => 9,
                        'instruction' => 'Физика: центростремительное ускорение',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 49, 'text' => 'Центростремительное ускорение a = ω²R. Найдите R, если ω = 9 с⁻¹, a = 243 м/с².'],
                            ['id' => 50, 'text' => 'Центростремительное ускорение a = ω²R. Найдите R, если ω = 8 с⁻¹, a = 128 м/с².'],
                            ['id' => 51, 'text' => 'Центростремительное ускорение a = ω²R. Найдите R, если ω = 9,5 с⁻¹, a = 180,5 м/с².'],
                            ['id' => 52, 'text' => 'Центростремительное ускорение a = ω²R. Найдите R, если ω = 7,5 с⁻¹, a = 337,5 м/с².'],
                            ['id' => 53, 'text' => 'Центростремительное ускорение a = ω²R. Найдите R, если ω = 8,5 с⁻¹, a = 650,25 м/с².'],
                            ['id' => 54, 'text' => 'Центростремительное ускорение a = ω²R. Найдите R, если ω = 7,5 с⁻¹, a = 393,75 м/с².'],
                        ]
                    ],
                    [
                        'number' => 10,
                        'instruction' => 'Математика: площадь четырёхугольника',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 55, 'text' => 'Площадь S = d₁d₂sinα/2. Найдите d₁, если d₂ = 7, sinα = 2/7, S = 4.'],
                            ['id' => 56, 'text' => 'Площадь S = d₁d₂sinα/2. Найдите d₂, если d₁ = 6, sinα = 1/11, S = 3.'],
                            ['id' => 57, 'text' => 'Площадь S = d₁d₂sinα/2. Найдите d₁, если d₂ = 13, sinα = 3/13, S = 25,5.'],
                            ['id' => 58, 'text' => 'Площадь S = d₁d₂sinα/2. Найдите d₂, если d₁ = 14, sinα = 1/12, S = 8,75.'],
                            ['id' => 59, 'text' => 'Площадь S = d₁d₂sinα/2. Найдите d₁, если d₂ = 11, sinα = 7/12, S = 57,75.'],
                            ['id' => 60, 'text' => 'Площадь S = d₁d₂sinα/2. Найдите d₂, если d₁ = 9, sinα = 5/8, S = 56,25.'],
                        ]
                    ],
                ]
            ],
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Стоимость поездки на такси',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Стоимость поездки С = 150 + 11·(t − 5), где t – длительность в минутах. Рассчитайте стоимость 16-минутной поездки.'],
                            ['id' => 2, 'text' => 'Стоимость поездки С = 150 + 11·(t − 5), где t – длительность в минутах. Рассчитайте стоимость 14-минутной поездки.'],
                            ['id' => 3, 'text' => 'Стоимость поездки С = 150 + 11·(t − 5), где t – длительность в минутах. Рассчитайте стоимость 9-минутной поездки.'],
                            ['id' => 4, 'text' => 'Стоимость поездки С = 150 + 11·(t − 5), где t – длительность в минутах. Рассчитайте стоимость 12-минутной поездки.'],
                            ['id' => 5, 'text' => 'Стоимость поездки С = 150 + 11·(t − 5), где t – длительность в минутах. Рассчитайте стоимость 8-минутной поездки.'],
                            ['id' => 6, 'text' => 'Стоимость поездки С = 150 + 11·(t − 5), где t – длительность в минутах. Рассчитайте стоимость 13-минутной поездки.'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Расстояние по числу шагов',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 7, 'text' => 'Расстояние s = nl, где n – число шагов, l – длина шага. Найдите s в км, если l = 70 см, n = 1400.'],
                            ['id' => 8, 'text' => 'Расстояние s = nl, где n – число шагов, l – длина шага. Найдите s в км, если l = 50 см, n = 1200.'],
                            ['id' => 9, 'text' => 'Расстояние s = nl, где n – число шагов, l – длина шага. Найдите s в км, если l = 80 см, n = 1800.'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Период колебания маятника',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 10, 'text' => 'Период маятника T = 2√l. Найдите l, если T = 13 с.'],
                            ['id' => 11, 'text' => 'Период маятника T = 2√l. Найдите l, если T = 4 с.'],
                            ['id' => 12, 'text' => 'Период маятника T = 2√l. Найдите l, если T = 9 с.'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Закон Кулона',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 13, 'text' => 'F = kq₁q₂/r², k = 9·10⁹. Найдите q₁, если q₂ = 0,006 Кл, r = 300 м, F = 5,4 Н.'],
                            ['id' => 14, 'text' => 'F = kq₁q₂/r², k = 9·10⁹. Найдите q₁, если q₂ = 0,002 Кл, r = 2000 м, F = 0,00135 Н.'],
                            ['id' => 15, 'text' => 'F = kq₁q₂/r², k = 9·10⁹. Найдите q₁, если q₂ = 0,004 Кл, r = 3000 м, F = 0,016 Н.'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Закон всемирного тяготения',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 16, 'text' => 'F = γm₁m₂/r², γ = 6,67·10⁻¹¹. Найдите m₁, если F = 1000,5 Н, m₂ = 6·10⁹ кг, r = 4 м.'],
                            ['id' => 17, 'text' => 'F = γm₁m₂/r², γ = 6,67·10⁻¹¹. Найдите m₁, если F = 0,06003 Н, m₂ = 6·10⁸ кг, r = 2 м.'],
                            ['id' => 18, 'text' => 'F = γm₁m₂/r², γ = 6,67·10⁻¹¹. Найдите m₁, если F = 83,375 Н, m₂ = 4·10⁹ кг, r = 4 м.'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'Закон Менделеева-Клапейрона',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 19, 'text' => 'PV = νRT, R = 8,31. Найдите V, если T = 250 К, P = 23891,25 Па, ν = 48,3 моль.'],
                            ['id' => 20, 'text' => 'PV = νRT, R = 8,31. Найдите P, если T = 250 К, ν = 16,4 моль, V = 8,2 м³.'],
                            ['id' => 21, 'text' => 'PV = νRT, R = 8,31. Найдите T, если P = 77698,5 Па, ν = 28,9 моль, V = 1,7 м³.'],
                            ['id' => 22, 'text' => 'PV = νRT, R = 8,31. Найдите T, если ν = 68,2 моль, P = 37782,8 Па, V = 6 м³.'],
                            ['id' => 23, 'text' => 'PV = νRT, R = 8,31. Найдите ν, если T = 700 К, P = 20941,2 Па, V = 9,5 м³.'],
                            ['id' => 24, 'text' => 'PV = νRT, R = 8,31. Найдите ν, если T = 400 К, P = 13296 Па, V = 4,9 м³.'],
                        ]
                    ],
                ]
            ],
            [
                'number' => 3,
                'title' => 'Типовые экзаменационные варианты',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Высота стеллажа, закон Гука и другие формулы',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Высота стеллажа h = (a + b)n + a мм. Найдите h из 7 полок, если a = 21 мм, b = 290 мм.'],
                            ['id' => 2, 'text' => 'Высота стеллажа h = (a + b)n + a мм. Найдите h из 8 полок, если a = 24 мм, b = 300 мм.'],
                            ['id' => 3, 'text' => 'Закон Гука: F = kx. Найдите x в метрах, если F = 56 Н, k = 7 Н/м.'],
                            ['id' => 4, 'text' => 'Закон Гука: F = kx. Найдите x в метрах, если F = 105 Н, k = 21 Н/м.'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display parsed tasks from PDF for topic 13
     */
    public function topic13()
    {
        $blocks = $this->getAllBlocksData13();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic13', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 13 - Неравенства
     */
    protected function getAllBlocksData13(): array
    {
        return [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Линейные неравенства: укажите решение неравенства',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '3 - 2x \\geq 8x - 1', 'options' => ['[-0,2; +∞)', '(-∞; 0,4]', '[0,4; +∞)', '(-∞; -0,2]']],
                            ['id' => 2, 'expression' => '4x - 4 \\geq 9x + 6', 'options' => ['[-0,4; +∞)', '(-∞; -2]', '[-2; +∞)', '(-∞; -0,4]']],
                            ['id' => 3, 'expression' => '6 - 7x \\geq 3x - 7', 'options' => ['[0,1; +∞)', '(-∞; 1,3]', '[1,3; +∞)', '(-∞; 0,1]']],
                            ['id' => 4, 'expression' => '2x - 8 \\geq 4x + 6', 'options' => ['[-7; +∞)', '(-∞; -7]', '[1; +∞)', '(-∞; 1]']],
                            ['id' => 5, 'expression' => '-9 - 6x > 9x + 9', 'options' => ['(-∞; -1,2)', '(-1,2; +∞)', '(0; +∞)', '(-∞; 0)']],
                            ['id' => 6, 'expression' => '8x - 8 > 7x + 6', 'options' => ['(-∞; 14)', '(14; +∞)', '(-2; +∞)', '(-∞; -2)']],
                            ['id' => 7, 'expression' => '5x + 4 < x + 6', 'options' => ['(-∞; 0,5)', '(2,5; +∞)', '(-∞; 2,5)', '(0,5; +∞)']],
                            ['id' => 8, 'expression' => '-3 - x < 4x + 7', 'options' => ['(-∞; -0,8)', '(-2; +∞)', '(-∞; -2)', '(-0,8; +∞)']],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Системы линейных неравенств',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '\\begin{cases} x + 3,6 \\geq 0 \\\\ x + 2 \\geq -1 \\end{cases}', 'options' => ['(-∞; -3,6] ∪ [-3; +∞)', '(-∞; -3,6]', '[-3,6; -3]', '[-3,6; +∞)']],
                            ['id' => 2, 'expression' => '\\begin{cases} x + 0,6 \\geq 0 \\\\ x - 1 \\geq -4 \\end{cases}', 'options' => ['(-∞; -3]', '[-0,6; +∞)', '(-∞; -3] ∪ [-0,6; +∞)', '[-3; -0,6]']],
                            ['id' => 3, 'expression' => '\\begin{cases} x - 6,6 \\geq 0 \\\\ x + 1 \\geq 5 \\end{cases}', 'options' => ['[4; +∞)', '[6,6; +∞)', '[4; 6,6]', '(-∞; 4]']],
                            ['id' => 4, 'expression' => '\\begin{cases} x + 4 \\geq -3,4 \\\\ x + 5 \\geq 0 \\end{cases}', 'options' => ['[-7,4; -5]', '[-5; +∞)', '(-∞; -7,4]', '(-∞; -7,4] ∪ [-5; +∞)']],
                            ['id' => 5, 'expression' => '\\begin{cases} x - 5,2 \\geq 0 \\\\ x + 4 \\geq 10 \\end{cases}', 'options' => ['(-∞; 5,2] ∪ [6; +∞)', '[6; +∞)', '[5,2; +∞)', '[5,2; 6]']],
                            ['id' => 6, 'expression' => '\\begin{cases} x - 2,6 \\leq 0 \\\\ x - 1 \\leq 1 \\end{cases}', 'options' => ['[2; 2,6]', '(-∞; 2] ∪ [2,6; +∞)', '(-∞; 2,6]', '[2; +∞)']],
                            ['id' => 7, 'expression' => '\\begin{cases} x + 2,8 \\leq 0 \\\\ x + 0,3 \\leq -1,4 \\end{cases}', 'options' => ['(-∞; -2,8]', '(-∞; -2,8] ∪ [-1,7; +∞)', '[-2,8; -1,7]', '[-1,7; +∞)']],
                            ['id' => 8, 'expression' => '\\begin{cases} x - 3 \\leq 0 \\\\ x - 0,2 \\leq 2 \\end{cases}', 'options' => ['[2,2; +∞)', '[3; +∞)', '[2,2; 3]', '(-∞; 2,2] ∪ [3; +∞)']],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Квадратные неравенства: укажите решение',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '(x + 3)(x - 8) \\leq 0', 'options' => ['[-3; 8]', '(-∞; -3] ∪ [8; +∞)', '[8; +∞)', '[-3; +∞)']],
                            ['id' => 2, 'expression' => '(x + 2)(x - 7) \\leq 0', 'options' => ['[-2; 7]', '(-∞; -2] ∪ [7; +∞)', '(-∞; 7]', '(-∞; -2]']],
                            ['id' => 3, 'expression' => '(x + 4)(x - 8) \\leq 0', 'options' => ['(-∞; 8]', '(-∞; -4] ∪ [8; +∞)', '[-4; 8]', '(-∞; -4]']],
                            ['id' => 4, 'expression' => '(x + 5)(x - 9) > 0', 'options' => ['(-5; +∞)', '(-5; 9)', '(9; +∞)', '(-∞; -5) ∪ (9; +∞)']],
                            ['id' => 5, 'expression' => '(x + 6)(x - 1) < 0', 'options' => ['(-∞; 1)', '(-∞; -6)', '(-∞; -6) ∪ (1; +∞)', '(-6; 1)']],
                            ['id' => 6, 'expression' => '(x + 3)(x - 5) \\geq 0', 'options' => ['(-∞; -3]', '[-3; 5]', '(-∞; 5]', '(-∞; -3] ∪ [5; +∞)']],
                            ['id' => 7, 'expression' => '(x + 1)(x - 7) \\geq 0', 'options' => ['(-∞; -1] ∪ [7; +∞)', '[-1; +∞)', '[-1; 7]', '[7; +∞)']],
                            ['id' => 8, 'expression' => '(x + 9)(x - 4) < 0', 'options' => ['(-9; 4)', '(-∞; -9) ∪ (4; +∞)', '(-∞; -9)', '(-∞; 4)']],
                            ['id' => 9, 'expression' => '(x + 2)(x - 10) > 0', 'options' => ['(-2; 10)', '(-∞; -2) ∪ (10; +∞)', '(10; +∞)', '(-2; +∞)']],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Неравенства вида x² - a = 0',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 49 < 0', 'options' => ['нет решений', '(-∞; +∞)', '(-7; 7)', '(-∞; -7) ∪ (7; +∞)']],
                            ['id' => 2, 'expression' => 'x^2 - 64 \\leq 0', 'options' => ['[-8; 8]', '(-∞; -8] ∪ [8; +∞)', 'нет решений', '(-∞; +∞)']],
                            ['id' => 3, 'expression' => 'x^2 - 25 \\geq 0', 'options' => ['(-∞; +∞)', '(-∞; -5] ∪ [5; +∞)', '[-5; 5]', 'нет решений']],
                            ['id' => 4, 'expression' => 'x^2 - 36 > 0', 'options' => ['(-∞; -6) ∪ (6; +∞)', '(-6; 6)', 'нет решений', '(-∞; +∞)']],
                            ['id' => 5, 'expression' => 'x^2 - 16 < 0', 'options' => ['(-∞; +∞)', 'нет решений', '(-∞; -4) ∪ (4; +∞)', '(-4; 4)']],
                            ['id' => 6, 'expression' => 'x^2 - 81 \\leq 0', 'options' => ['[-9; 9]', '(-∞; -9] ∪ [9; +∞)', '(-∞; +∞)', 'нет решений']],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Неравенства вида ax - x² ≥ 0',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '7x - x^2 \\geq 0', 'options' => ['[0; +∞)', '[7; +∞)', '(-∞; 0] ∪ [7; +∞)', '[0; 7]']],
                            ['id' => 2, 'expression' => '4x - x^2 < 0', 'options' => ['(-∞; 0) ∪ (4; +∞)', '(0; +∞)', '(0; 4)', '(4; +∞)']],
                            ['id' => 3, 'expression' => '10x - x^2 \\geq 0', 'options' => ['[0; 10]', '(-∞; 0] ∪ [10; +∞)', '[10; +∞)', '[0; +∞)']],
                            ['id' => 4, 'expression' => '3x - x^2 > 0', 'options' => ['(3; +∞)', '(-∞; 0) ∪ (3; +∞)', '(0; +∞)', '(0; 3)']],
                            ['id' => 5, 'expression' => '8x - x^2 \\leq 0', 'options' => ['[8; +∞)', '[0; 8]', '(-∞; 0] ∪ [8; +∞)', '[0; +∞)']],
                            ['id' => 6, 'expression' => 'x - x^2 \\leq 0', 'options' => ['(0; 1)', '(-∞; 0)', '(-∞; 1)', '(-∞; 0) ∪ (1; +∞)']],
                            ['id' => 7, 'expression' => '6x - x^2 \\geq 0', 'options' => ['[0; +∞)', '(-∞; 0] ∪ [6; +∞)', '[0; 6]', '[6; +∞)']],
                            ['id' => 8, 'expression' => '5x - x^2 \\leq 0', 'options' => ['(-∞; 0) ∪ (5; +∞)', '(0; 5)', '(5; +∞)', '(0; +∞)']],
                            ['id' => 9, 'expression' => '2x - x^2 \\leq 0', 'options' => ['(-∞; 0] ∪ [2; +∞)', '[0; +∞)', '[2; +∞)', '[0; 2]']],
                        ]
                    ],
                ]
            ],
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Линейные неравенства (графическое решение)',
                        'type' => 'graphic',
                        'tasks' => [
                            ['id' => 1, 'expression' => '4x + 5 \\geq 6x - 2', 'image' => 'img-000.png'],
                            ['id' => 2, 'expression' => '-2x + 5 \\geq -3x - 3', 'image' => 'img-001.png'],
                            ['id' => 3, 'expression' => '3 - x \\geq 3x + 5', 'image' => 'img-002.png'],
                            ['id' => 4, 'expression' => 'x + 4 \\geq 4x - 5', 'image' => 'img-003.png'],
                            ['id' => 5, 'expression' => '2 + x \\geq 5x - 8', 'image' => 'img-004.png'],
                            ['id' => 6, 'expression' => '4x - 5 \\geq 2x - 4', 'image' => 'img-005.png'],
                            ['id' => 7, 'expression' => 'x - 1 \\geq 3x + 2', 'image' => 'img-006.png'],
                            ['id' => 8, 'expression' => '2x + 4 \\geq -4x + 1', 'image' => 'img-007.png'],
                            ['id' => 9, 'expression' => 'x - 2 \\geq 4x + 4', 'image' => 'img-008.png'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Неравенства со скобками',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => '5x - 3(5x - 8) < -7', 'options' => ['(-∞; 3,1)', '(-1,7; +∞)', '(-∞; -1,7)', '(3,1; +∞)']],
                            ['id' => 2, 'expression' => '6x - 3(4x + 1) > 6', 'options' => ['(-1,5; +∞)', '(-∞; -1,5)', '(-∞; -1,5)', '(-0,5; +∞)']],
                            ['id' => 3, 'expression' => '3x - 2(x - 2) > -4', 'options' => ['(0; +∞)', '(-8; +∞)', '(-∞; 0)', '(-∞; -8)']],
                            ['id' => 4, 'expression' => '5x - 2(2x - 8) < -5', 'options' => ['(-∞; 11)', '(11; +∞)', '(-∞; -21)', '(-21; +∞)']],
                            ['id' => 5, 'expression' => '3x - 2(x - 5) \\geq -6', 'options' => ['(-∞; -16]', '(-∞; 4]', '[4; +∞)', '[-16; +∞)']],
                            ['id' => 6, 'expression' => '2x - 3(x - 7) \\geq 3', 'options' => ['(-∞; -24]', '(-∞; 18]', '[18; +∞)', '[-24; +∞)']],
                            ['id' => 7, 'expression' => '9x - 4(x - 7) \\geq -3', 'options' => ['[5; +∞)', '(-∞; -6,2]', '(-∞; 5]', '[-6,2; +∞)']],
                            ['id' => 8, 'expression' => '8x - 3(3x + 8) \\leq 9', 'options' => ['[15; +∞)', '(-∞; -33]', '(-∞; 15]', '[-33; +∞)']],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Неравенство не имеет решений',
                        'type' => 'choice',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 + 70', 'options' => ['x² + 70 < 0', 'x² + 70 > 0', 'x² - 70 < 0', 'x² - 70 > 0']],
                            ['id' => 2, 'expression' => 'x^2 + 15', 'options' => ['x² + 15 ≤ 0', 'x² - 15 ≤ 0', 'x² - 15 ≥ 0', 'x² + 15 ≥ 0']],
                            ['id' => 3, 'expression' => 'x^2 + 33', 'options' => ['x² + 33 < 0', 'x² + 33 > 0', 'x² - 33 < 0', 'x² - 33 > 0']],
                            ['id' => 4, 'expression' => 'x^2 + 49', 'options' => ['x² + 49 ≤ 0', 'x² - 49 ≤ 0', 'x² - 49 ≥ 0', 'x² + 49 ≥ 0']],
                            ['id' => 5, 'expression' => 'x^2 + 64', 'options' => ['x² - 64 < 0', 'x² + 64 > 0', 'x² + 64 < 0', 'x² - 64 > 0']],
                            ['id' => 6, 'expression' => 'x^2 + 56', 'options' => ['x² - 56 ≥ 0', 'x² + 56 ≥ 0', 'x² - 56 ≤ 0', 'x² + 56 ≤ 0']],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Квадратные неравенства (графическое решение)',
                        'type' => 'graphic',
                        'tasks' => [
                            ['id' => 1, 'expression' => 'x^2 - 4x + 3 \\leq 0', 'image' => 'img-009.png'],
                            ['id' => 2, 'expression' => 'x^2 - 7x + 12 \\leq 0', 'image' => 'img-010.png'],
                            ['id' => 3, 'expression' => 'x^2 + 9x + 20 \\leq 0', 'image' => 'img-011.png'],
                            ['id' => 4, 'expression' => 'x^2 - 5x - 6 \\leq 0', 'image' => 'img-012.png'],
                            ['id' => 5, 'expression' => 'x^2 - 17x + 72 \\leq 0', 'image' => 'img-013.png'],
                            ['id' => 6, 'expression' => 'x^2 - 6x - 27 \\leq 0', 'image' => 'img-014.png'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display Topic 14 - Арифметические и геометрические прогрессии
     */
    public function topic14()
    {
        $blocks = $this->getAllBlocksData14();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic14', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 14 - Арифметические и геометрические прогрессии
     */
    protected function getAllBlocksData14(): array
    {
        return [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Задачи на арифметическую прогрессию (амфитеатр)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'В амфитеатре 13 рядов. В первом ряду 22 места, а в каждом следующем на 3 места больше, чем в предыдущем. Сколько мест в одиннадцатом ряду амфитеатра?'],
                            ['id' => 2, 'text' => 'В амфитеатре 14 рядов. В первом ряду 20 мест, а в каждом следующем на 3 места больше, чем в предыдущем. Сколько мест в десятом ряду амфитеатра?'],
                            ['id' => 3, 'text' => 'В амфитеатре 10 рядов. В первом ряду 25 мест, а в каждом следующем на 3 места больше, чем в предыдущем. Сколько мест в восьмом ряду амфитеатра?'],
                            ['id' => 4, 'text' => 'В амфитеатре 15 рядов. В первом ряду 20 мест, а в каждом следующем на 2 места больше, чем в предыдущем. Сколько мест в десятом ряду амфитеатра?'],
                            ['id' => 5, 'text' => 'В амфитеатре 16 рядов. В первом ряду 19 мест, а в каждом следующем на 2 места больше, чем в предыдущем. Сколько мест в тринадцатом ряду амфитеатра?'],
                            ['id' => 6, 'text' => 'В амфитеатре 12 рядов. В первом ряду 21 место, а в каждом следующем на 2 места больше, чем в предыдущем. Сколько мест в одиннадцатом ряду амфитеатра?'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Задачи на арифметическую прогрессию (охлаждение)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на 6°C. Найдите температуру вещества через 4 минуты после начала проведения опыта, если его начальная температура составляла −7°C.'],
                            ['id' => 2, 'text' => 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на 7°C. Найдите температуру вещества через 5 минут после начала проведения опыта, если его начальная температура составляла −7°C.'],
                            ['id' => 3, 'text' => 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на 5°C. Найдите температуру вещества через 9 минут после начала проведения опыта, если его начальная температура составляла −8°C.'],
                            ['id' => 4, 'text' => 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на 8°C. Найдите температуру вещества через 6 минут после начала проведения опыта, если его начальная температура составляла −6°C.'],
                            ['id' => 5, 'text' => 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на 9°C. Найдите температуру вещества через 4 минуты после начала проведения опыта, если его начальная температура составляла −5°C.'],
                            ['id' => 6, 'text' => 'При проведении опыта вещество равномерно охлаждали в течение 10 минут. При этом каждую минуту температура вещества уменьшалась на 6°C. Найдите температуру вещества через 7 минут после начала проведения опыта, если его начальная температура составляла −9°C.'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Сумма арифметической прогрессии (поезд)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Поезд начал движение от станции. За первую секунду состав сдвинулся на 0,6 м, а за каждую следующую секунду он проходил на 0,1 м больше, чем за предыдущую. Сколько метров состав прошёл за первые 7 секунд движения?'],
                            ['id' => 2, 'text' => 'Поезд начал движение от станции. За первую секунду состав сдвинулся на 0,3 м, а за каждую следующую секунду он проходил на 0,5 м больше, чем за предыдущую. Сколько метров состав прошёл за первые 9 секунд движения?'],
                            ['id' => 3, 'text' => 'Поезд начал движение от станции. За первую секунду состав сдвинулся на 0,5 м, а за каждую следующую секунду он проходил на 0,2 м больше, чем за предыдущую. Сколько метров состав прошёл за первые 10 секунд движения?'],
                            ['id' => 4, 'text' => 'Поезд начал движение от станции. За первую секунду состав сдвинулся на 0,8 м, а за каждую следующую секунду он проходил на 0,3 м больше, чем за предыдущую. Сколько метров состав прошёл за первые 6 секунд движения?'],
                            ['id' => 5, 'text' => 'Поезд начал движение от станции. За первую секунду состав сдвинулся на 0,2 м, а за каждую следующую секунду он проходил на 0,6 м больше, чем за предыдущую. Сколько метров состав прошёл за первые 7 секунд движения?'],
                            ['id' => 6, 'text' => 'Поезд начал движение от станции. За первую секунду состав сдвинулся на 1 м, а за каждую следующую секунду он проходил на 0,2 м больше, чем за предыдущую. Сколько метров состав прошёл за первые 8 секунд движения?'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Сумма арифметической прогрессии (торможение)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Водитель автомобиля начал торможение. За секунду после начала торможения автомобиль проехал 30 м, а за каждую следующую секунду он проезжал на 4 м меньше, чем за предыдущую. Сколько метров автомобиль прошёл за первые 5 секунд торможения?'],
                            ['id' => 2, 'text' => 'Водитель автомобиля начал торможение. За секунду после начала торможения автомобиль проехал 27 м, а за каждую следующую секунду он проезжал на 4 м меньше, чем за предыдущую. Сколько метров автомобиль прошёл за первые 6 секунд торможения?'],
                            ['id' => 3, 'text' => 'Водитель автомобиля начал торможение. За секунду после начала торможения автомобиль проехал 32 м, а за каждую следующую секунду он проезжал на 5 м меньше, чем за предыдущую. Сколько метров автомобиль прошёл за первые 4 секунды торможения?'],
                            ['id' => 4, 'text' => 'Водитель автомобиля начал торможение. За секунду после начала торможения автомобиль проехал 25 м, а за каждую следующую секунду он проезжал на 3 м меньше, чем за предыдущую. Сколько метров автомобиль прошёл за первые 7 секунд торможения?'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Задачи на геометрическую прогрессию (бактерии)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Бактерия делится на две в течение одной минуты. В начальный момент времени в сосуде было 2 бактерии. Сколько бактерий будет в сосуде через 8 минут?'],
                            ['id' => 2, 'text' => 'Бактерия делится на две в течение одной минуты. В начальный момент времени в сосуде было 3 бактерии. Сколько бактерий будет в сосуде через 6 минут?'],
                            ['id' => 3, 'text' => 'Бактерия делится на две в течение одной минуты. В начальный момент времени в сосуде было 5 бактерий. Сколько бактерий будет в сосуде через 5 минут?'],
                            ['id' => 4, 'text' => 'Бактерия делится на две в течение одной минуты. В начальный момент времени в сосуде было 4 бактерии. Сколько бактерий будет в сосуде через 7 минут?'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'Задачи на геометрическую прогрессию (отскок мяча)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Мяч бросили с высоты 125 м. После каждого удара о землю мяч подпрыгивает на высоту, в 5 раз меньшую, чем прежде. На какую высоту поднимется мяч после третьего удара?'],
                            ['id' => 2, 'text' => 'Мяч бросили с высоты 162 м. После каждого удара о землю мяч подпрыгивает на высоту, в 3 раза меньшую, чем прежде. На какую высоту поднимется мяч после четвёртого удара?'],
                            ['id' => 3, 'text' => 'Мяч бросили с высоты 128 м. После каждого удара о землю мяч подпрыгивает на высоту, в 2 раза меньшую, чем прежде. На какую высоту поднимется мяч после пятого удара?'],
                            ['id' => 4, 'text' => 'Мяч бросили с высоты 256 м. После каждого удара о землю мяч подпрыгивает на высоту, в 4 раза меньшую, чем прежде. На какую высоту поднимется мяч после третьего удара?'],
                        ]
                    ],
                ]
            ],
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    [
                        'number' => 7,
                        'instruction' => 'Сложные задачи на прогрессии',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Грузовик перевозит партию щебня массой 216 тонн, ежедневно увеличивая норму перевозки на одно и то же число тонн. Известно, что за первый день было перевезено 7 тонн щебня. Определите, сколько тонн щебня было перевезено на восьмой день, если вся работа была выполнена за 12 дней.'],
                            ['id' => 2, 'text' => 'Грузовик перевозит партию щебня массой 132 тонны, ежедневно увеличивая норму перевозки на одно и то же число тонн. Известно, что за первый день было перевезено 6 тонн щебня. Определите, сколько тонн щебня было перевезено на пятый день, если вся работа была выполнена за 8 дней.'],
                            ['id' => 3, 'text' => 'Грузовик перевозит партию щебня массой 170 тонн, ежедневно увеличивая норму перевозки на одно и то же число тонн. Известно, что за первый день было перевезено 8 тонн щебня. Определите, сколько тонн щебня было перевезено на седьмой день, если вся работа была выполнена за 10 дней.'],
                            ['id' => 4, 'text' => 'Грузовик перевозит партию щебня массой 144 тонны, ежедневно увеличивая норму перевозки на одно и то же число тонн. Известно, что за первый день было перевезено 4 тонны щебня. Определите, сколько тонн щебня было перевезено на шестой день, если вся работа была выполнена за 9 дней.'],
                        ]
                    ],
                    [
                        'number' => 8,
                        'instruction' => 'Задачи на стрельбу (штрафные очки)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'В соревновании по стрельбе за каждый промах в серии из 25 выстрелов стрелок получал штрафные очки: за первый промах – одно штрафное очко, за каждый последующий – на 0,5 очка больше, чем за предыдущий. Сколько раз попал в цель стрелок, получивший 13,5 штрафных очков?'],
                            ['id' => 2, 'text' => 'В соревновании по стрельбе за каждый промах в серии из 20 выстрелов стрелок получал штрафные очки: за первый промах – одно штрафное очко, за каждый последующий – на 0,5 очка больше, чем за предыдущий. Сколько раз попал в цель стрелок, получивший 22 штрафных очка?'],
                            ['id' => 3, 'text' => 'В соревновании по стрельбе за каждый промах в серии из 25 выстрелов стрелок получал штрафные очки: за первый промах – одно штрафное очко, за каждый последующий – на 0,5 очка больше, чем за предыдущий. Сколько раз попал в цель стрелок, получивший 7 штрафных очков?'],
                            ['id' => 4, 'text' => 'В соревновании по стрельбе за каждый промах в серии из 20 выстрелов стрелок получал штрафные очки: за первый промах – одно штрафное очко, за каждый последующий – на 0,5 очка больше, чем за предыдущий. Сколько раз попал в цель стрелок, получивший 17,5 штрафных очков?'],
                        ]
                    ],
                    [
                        'number' => 9,
                        'instruction' => 'Задачи на приём лекарств',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Врач прописал больному капли по следующей схеме: в первый день 10 капель, а в каждый следующий день – на 5 капель больше, чем в предыдущий, до тех пор, пока дневная доза не достигнет 40 капель. Такую дневную дозу (40 капель) больной ежедневно принимает пять дней, а затем уменьшает приём на 5 капель в день до последнего дня, когда больной принимает последние десять капель. Сколько пузырьков лекарства нужно купить на весь курс, если в каждом пузырьке 10 мл лекарства, то есть 200 капель?'],
                            ['id' => 2, 'text' => 'Врач прописал больному капли по следующей схеме: в первый день 5 капель, а в каждый следующий день – на 10 капель больше, чем в предыдущий, до тех пор, пока дневная доза не достигнет 45 капель. Такую дневную дозу (45 капель) больной ежедневно принимает три дня, а затем уменьшает приём на 10 капель в день. Сколько пузырьков лекарства нужно купить на весь курс, если в каждом пузырьке 10 мл лекарства, то есть 160 капель?'],
                            ['id' => 3, 'text' => 'Врач прописал больному капли по следующей схеме: в первый день 10 капель, а в каждый следующий день – на 10 капель больше, чем в предыдущий, до тех пор, пока дневная доза не достигнет 60 капель. Такую дневную дозу (60 капель) больной ежедневно принимает два дня, а затем уменьшает приём на 10 капель в день. Сколько пузырьков лекарства нужно купить на весь курс, если в каждом пузырьке 10 мл лекарства, то есть 140 капель?'],
                            ['id' => 4, 'text' => 'Врач прописал больному капли по следующей схеме: в первый день 5 капель, а в каждый следующий день – на 5 капель больше, чем в предыдущий, до тех пор, пока дневная доза не достигнет 35 капель. Такую дневную дозу (35 капель) больной ежедневно принимает четыре дня, а затем уменьшает приём на 5 капель в день. Сколько пузырьков лекарства нужно купить на весь курс, если в каждом пузырьке 10 мл лекарства, то есть 180 капель?'],
                        ]
                    ],
                    [
                        'number' => 10,
                        'instruction' => 'Геометрическая прогрессия (инфузории)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Каждое простейшее одноклеточное животное инфузория–туфелька размножается делением на 2 части. Сколько инфузорий было первоначально, если после пятикратного деления их стало 800?'],
                            ['id' => 2, 'text' => 'Каждое простейшее одноклеточное животное инфузория–туфелька размножается делением на 2 части. Сколько инфузорий было первоначально, если после шестикратного деления их стало 1920?'],
                            ['id' => 3, 'text' => 'Каждое простейшее одноклеточное животное инфузория–туфелька размножается делением на 2 части. Сколько инфузорий было первоначально, если после пятикратного деления их стало 640?'],
                            ['id' => 4, 'text' => 'Каждое простейшее одноклеточное животное инфузория–туфелька размножается делением на 2 части. Сколько инфузорий было первоначально, если после шестикратного деления их стало 960?'],
                        ]
                    ],
                    [
                        'number' => 11,
                        'instruction' => 'Геометрическая прогрессия (компьютерная игра)',
                        'type' => 'word_problem',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Митя играет в компьютерную игру. Он начинает с 0 очков, а для перехода на следующий уровень ему нужно набрать не менее 15 000 очков. После первой минуты игры добавляется 2 очка, после второй – 4 очка, после третьей – 8 очков и так далее. Через сколько минут Митя перейдет на следующий уровень?'],
                            ['id' => 2, 'text' => 'Митя играет в компьютерную игру. Он начинает с 0 очков, а для перехода на следующий уровень ему нужно набрать не менее 30 000 очков. После первой минуты игры добавляется 2 очка, после второй – 4 очка, после третьей – 8 очков и так далее. Через сколько минут Митя перейдет на следующий уровень?'],
                            ['id' => 3, 'text' => 'Митя играет в компьютерную игру. Он начинает с 0 очков, а для перехода на следующий уровень ему нужно набрать не менее 50 000 очков. После первой минуты игры добавляется 2 очка, после второй – 4 очка, после третьей – 8 очков и так далее. Через сколько минут Митя перейдет на следующий уровень?'],
                            ['id' => 4, 'text' => 'Митя играет в компьютерную игру. Он начинает с 0 очков, а для перехода на следующий уровень ему нужно набрать не менее 100 000 очков. После первой минуты игры добавляется 2 очка, после второй – 4 очка, после третьей – 8 очков и так далее. Через сколько минут Митя перейдет на следующий уровень?'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display Topic 15 - Треугольники
     */
    public function topic15()
    {
        $blocks = $this->getAllBlocksData15();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic15', compact('blocks', 'source'));
    }

    /**
     * Display Topic 15 Interactive - Треугольники с интерактивными изображениями
     */
    public function topic15Interactive()
    {
        $blocks = $this->getAllBlocksData15();
        $source = 'Interactive SVG';

        return view('test.topic15-interactive', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 15 - Треугольники
     */
    protected function getAllBlocksData15(): array
    {
        return [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Биссектриса треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 1, 'text' => 'В треугольнике ABC известно, что ∠BAC = 68°, AD – биссектриса. Найдите угол BAD.', 'image' => 'oge15_p1_img1.png'],
                            ['id' => 2, 'text' => 'В треугольнике ABC известно, что ∠BAC = 82°, AD – биссектриса. Найдите угол BAD.', 'image' => 'oge15_p1_img2.png'],
                            ['id' => 3, 'text' => 'В треугольнике ABC известно, что ∠BAC = 26°, AD – биссектриса. Найдите угол BAD.', 'image' => 'oge15_p1_img3.png'],
                            ['id' => 4, 'text' => 'В треугольнике ABC известно, что ∠BAC = 24°, AD – биссектриса. Найдите угол BAD.', 'image' => 'oge15_p1_img4.png'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Медиана треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 5, 'text' => 'В треугольнике ABC известно, что AC=14, BM – медиана, BM=10. Найдите АM.', 'image' => 'oge15_p1_img5.png'],
                            ['id' => 6, 'text' => 'В треугольнике ABC известно, что AC=16, BM – медиана, BM=12. Найдите АM.', 'image' => 'oge15_p2_img1.png'],
                            ['id' => 7, 'text' => 'В треугольнике ABC известно, что AC=38, BM – медиана, BM=17. Найдите АM.', 'image' => 'oge15_p2_img2.png'],
                            ['id' => 8, 'text' => 'В треугольнике ABC известно, что AC=54, BM – медиана, BM=43. Найдите АM.', 'image' => 'oge15_p2_img3.png'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Сумма углов треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 9, 'text' => 'В треугольнике два угла равны 72° и 42°. Найдите его третий угол.', 'image' => 'oge15_p2_img4.png'],
                            ['id' => 10, 'text' => 'В треугольнике два угла равны 43° и 88°. Найдите его третий угол.', 'image' => 'oge15_p2_img5.png'],
                            ['id' => 11, 'text' => 'В треугольнике два угла равны 38° и 89°. Найдите его третий угол.', 'image' => 'oge15_p3_img1.png'],
                            ['id' => 12, 'text' => 'В треугольнике два угла равны 54° и 58°. Найдите его третий угол.', 'image' => 'oge15_p3_img2.png'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Внешний угол треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 13, 'text' => 'В треугольнике ABC угол C равен 115°. Найдите внешний угол при вершине C.', 'image' => 'oge15_p3_img3.png'],
                            ['id' => 14, 'text' => 'В треугольнике ABC угол C равен 177°. Найдите внешний угол при вершине C.', 'image' => 'oge15_p3_img4.png'],
                            ['id' => 15, 'text' => 'В треугольнике ABC угол C равен 106°. Найдите внешний угол при вершине C.', 'image' => 'oge15_p3_img5.png'],
                            ['id' => 16, 'text' => 'В треугольнике ABC угол C равен 142°. Найдите внешний угол при вершине C.', 'image' => 'oge15_p4_img1.png'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Равнобедренный треугольник',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 17, 'text' => 'В треугольнике ABC известно, что AB=BC, ∠ABC = 106°. Найдите угол BCA.', 'image' => 'oge15_p4_img2.png'],
                            ['id' => 18, 'text' => 'В треугольнике ABC известно, что AB=BC, ∠ABC = 108°. Найдите угол BCA.', 'image' => 'oge15_p4_img3.png'],
                            ['id' => 19, 'text' => 'В треугольнике ABC известно, что AB=BC, ∠ABC = 132°. Найдите угол BCA.', 'image' => 'oge15_p4_img4.png'],
                            ['id' => 20, 'text' => 'В треугольнике ABC известно, что AB=BC, ∠ABC = 144°. Найдите угол BCA.', 'image' => 'oge15_p4_img5.png'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'Внешний угол равнобедренного треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 21, 'text' => 'В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 129°. Найдите величину угла ABC.', 'image' => 'oge15_p5_img1.png'],
                            ['id' => 22, 'text' => 'В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 124°. Найдите величину угла ABC.', 'image' => 'oge15_p5_img2.png'],
                            ['id' => 23, 'text' => 'В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 107°. Найдите величину угла ABC.', 'image' => 'oge15_p5_img3.png'],
                            ['id' => 24, 'text' => 'В равнобедренном треугольнике ABC с основанием AC внешний угол при вершине C равен 111°. Найдите величину угла ABC.', 'image' => 'oge15_p5_img4.png'],
                        ]
                    ],
                    [
                        'number' => 7,
                        'instruction' => 'Прямоугольный треугольник',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 25, 'text' => 'Один из острых углов прямоугольного треугольника равен 21°. Найдите его другой острый угол.', 'image' => 'oge15_p5_img5.png'],
                            ['id' => 26, 'text' => 'Один из острых углов прямоугольного треугольника равен 33°. Найдите его другой острый угол.', 'image' => 'oge15_p6_img1.png'],
                            ['id' => 27, 'text' => 'Один из острых углов прямоугольного треугольника равен 47°. Найдите его другой острый угол.', 'image' => 'oge15_p6_img2.png'],
                            ['id' => 28, 'text' => 'Один из острых углов прямоугольного треугольника равен 63°. Найдите его другой острый угол.', 'image' => 'oge15_p7_img1.png'],
                        ]
                    ],
                    [
                        'number' => 8,
                        'instruction' => 'Высота треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 29, 'text' => 'В остроугольном треугольнике ABC проведена высота BH, ∠BAC=37°. Найдите угол ABH.', 'image' => 'oge15_p7_img2.png'],
                            ['id' => 30, 'text' => 'В остроугольном треугольнике ABC проведена высота BH, ∠BAC=29°. Найдите угол ABH.', 'image' => 'oge15_p8_img1.png'],
                            ['id' => 31, 'text' => 'В остроугольном треугольнике ABC проведена высота BH, ∠BAC=46°. Найдите угол ABH.', 'image' => 'oge15_p8_img2.png'],
                            ['id' => 32, 'text' => 'В остроугольном треугольнике ABC проведена высота BH, ∠BAC=82°. Найдите угол ABH.', 'image' => 'oge15_p8_img3.png'],
                        ]
                    ],
                    [
                        'number' => 9,
                        'instruction' => 'Площадь прямоугольного треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 33, 'text' => 'Два катета прямоугольного треугольника равны 4 и 10. Найдите площадь этого треугольника.', 'image' => 'oge15_p8_img4.png'],
                            ['id' => 34, 'text' => 'Два катета прямоугольного треугольника равны 14 и 5. Найдите площадь этого треугольника.', 'image' => 'oge15_p9_img1.png'],
                            ['id' => 35, 'text' => 'Два катета прямоугольного треугольника равны 7 и 12. Найдите площадь этого треугольника.', 'image' => 'oge15_p9_img2.png'],
                            ['id' => 36, 'text' => 'Два катета прямоугольного треугольника равны 18 и 7. Найдите площадь этого треугольника.', 'image' => 'oge15_p9_img3.png'],
                        ]
                    ],
                    // Page 4 - Площадь треугольника (с высотой)
                    [
                        'number' => 10,
                        'instruction' => 'Площадь треугольника (с высотой)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 37, 'text' => 'Сторона треугольника равна 16, а высота, проведённая к этой стороне, равна 19. Найдите площадь этого треугольника.', 'image' => 'oge15_p10_img1.png'],
                            ['id' => 38, 'text' => 'В треугольнике одна из сторон равна 14, а опущенная на нее высота – 31. Найдите площадь треугольника.', 'image' => 'oge15_p10_img2.png'],
                            ['id' => 39, 'text' => 'Сторона треугольника равна 29, а высота, проведённая к этой стороне, равна 12. Найдите площадь этого треугольника.', 'image' => 'oge15_p10_img3.png'],
                            ['id' => 40, 'text' => 'В треугольнике одна из сторон равна 18, а опущенная на нее высота – 17. Найдите площадь треугольника.', 'image' => 'oge15_p10_img4.png'],
                        ]
                    ],
                    // Page 4 - Подобные треугольники (средняя линия)
                    [
                        'number' => 11,
                        'instruction' => 'Подобные треугольники (средняя линия)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 41, 'text' => 'Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 21, сторона BC равна 22, сторона AC равна 28. Найдите MN.', 'image' => 'oge15_p11_img1.png'],
                            ['id' => 42, 'text' => 'Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 66, сторона BC равна 37, сторона AC равна 74. Найдите MN.', 'image' => 'oge15_p11_img2.png'],
                            ['id' => 43, 'text' => 'Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 26, сторона BC равна 39, сторона AC равна 48. Найдите MN.', 'image' => 'oge15_p11_img3.png'],
                            ['id' => 44, 'text' => 'Точки M и N являются серединами сторон AB и BC треугольника ABC, сторона AB равна 42, сторона BC равна 44, сторона AC равна 62. Найдите MN.', 'image' => 'oge15_p11_img4.png'],
                        ]
                    ],
                    // Page 4 - Теорема Пифагора (гипотенуза)
                    [
                        'number' => 12,
                        'instruction' => 'Теорема Пифагора (найти гипотенузу)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 45, 'text' => 'Катеты прямоугольного треугольника равны 7 и 24. Найдите гипотенузу этого треугольника.', 'image' => 'oge15_p12_img1.png'],
                            ['id' => 46, 'text' => 'Катеты прямоугольного треугольника равны 8 и 15. Найдите гипотенузу этого треугольника.', 'image' => 'oge15_p12_img2.png'],
                            ['id' => 47, 'text' => 'Катеты прямоугольного треугольника равны 20 и 21. Найдите гипотенузу этого треугольника.', 'image' => 'oge15_p12_img3.png'],
                            ['id' => 48, 'text' => 'Катеты прямоугольного треугольника равны 9 и 12. Найдите гипотенузу этого треугольника.', 'image' => 'oge15_p12_img4.png'],
                        ]
                    ],
                    // Page 5 - Теорема Пифагора (катет)
                    [
                        'number' => 13,
                        'instruction' => 'Теорема Пифагора (найти катет)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 49, 'text' => 'В прямоугольном треугольнике катет и гипотенуза равны 7 и 25 соответственно. Найдите другой катет этого треугольника.', 'image' => 'oge15_p13_img1.png'],
                            ['id' => 50, 'text' => 'В прямоугольном треугольнике катет и гипотенуза равны 40 и 41 соответственно. Найдите другой катет этого треугольника.', 'image' => 'oge15_p13_img2.png'],
                            ['id' => 51, 'text' => 'В прямоугольном треугольнике катет и гипотенуза равны 8 и 17 соответственно. Найдите другой катет этого треугольника.', 'image' => 'oge15_p13_img3.png'],
                            ['id' => 52, 'text' => 'В прямоугольном треугольнике катет и гипотенуза равны 16 и 34 соответственно. Найдите другой катет этого треугольника.', 'image' => 'oge15_p13_img4.png'],
                        ]
                    ],
                    // Page 5 - Равносторонний треугольник (биссектриса → сторона)
                    [
                        'number' => 14,
                        'instruction' => 'Равносторонний треугольник (биссектриса → сторона)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 53, 'text' => 'Биссектриса равностороннего треугольника равна 12√3. Найдите сторону этого треугольника.', 'image' => 'oge15_p14_img1.png'],
                            ['id' => 54, 'text' => 'Биссектриса равностороннего треугольника равна 13√3. Найдите сторону этого треугольника.', 'image' => 'oge15_p14_img2.png'],
                        ]
                    ],
                    // Page 5 - Равносторонний треугольник (медиана → сторона)
                    [
                        'number' => 15,
                        'instruction' => 'Равносторонний треугольник (медиана → сторона)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 55, 'text' => 'Медиана равностороннего треугольника равна 11√3. Найдите сторону этого треугольника.', 'image' => 'oge15_p15_img1.png'],
                            ['id' => 56, 'text' => 'Медиана равностороннего треугольника равна 14√3. Найдите сторону этого треугольника.', 'image' => 'oge15_p15_img2.png'],
                        ]
                    ],
                    // Page 5 - Равносторонний треугольник (сторона → биссектриса)
                    [
                        'number' => 16,
                        'instruction' => 'Равносторонний треугольник (сторона → биссектриса)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 57, 'text' => 'Сторона равностороннего треугольника равна 16√3. Найдите биссектрису этого треугольника.', 'image' => 'oge15_p16_img1.png'],
                            ['id' => 58, 'text' => 'Сторона равностороннего треугольника равна 14√3. Найдите биссектрису этого треугольника.', 'image' => 'oge15_p16_img2.png'],
                        ]
                    ],
                    // Page 5 - Равносторонний треугольник (сторона → медиана)
                    [
                        'number' => 17,
                        'instruction' => 'Равносторонний треугольник (сторона → медиана)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 59, 'text' => 'Сторона равностороннего треугольника равна 10√3. Найдите медиану этого треугольника.', 'image' => 'oge15_p17_img1.png'],
                            ['id' => 60, 'text' => 'Сторона равностороннего треугольника равна 8√3. Найдите медиану этого треугольника.', 'image' => 'oge15_p17_img2.png'],
                        ]
                    ],
                    // Page 5 - Равносторонний треугольник (сторона → высота)
                    [
                        'number' => 18,
                        'instruction' => 'Равносторонний треугольник (сторона → высота)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 61, 'text' => 'Сторона равностороннего треугольника равна 18√3. Найдите высоту этого треугольника.', 'image' => 'oge15_p18_img1.png'],
                            ['id' => 62, 'text' => 'Сторона равностороннего треугольника равна 12√3. Найдите высоту этого треугольника.', 'image' => 'oge15_p18_img2.png'],
                        ]
                    ],
                    // Page 6 - Радиус описанной окружности
                    [
                        'number' => 19,
                        'instruction' => 'Радиус описанной окружности',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 63, 'text' => 'В треугольнике ABC известно, что AC=6, BC=8, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.', 'image' => 'oge15_p19_img1.png'],
                            ['id' => 64, 'text' => 'В треугольнике ABC известно, что AC=40, BC=30, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.', 'image' => 'oge15_p19_img2.png'],
                            ['id' => 65, 'text' => 'В треугольнике ABC известно, что AC=12, BC=5, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.', 'image' => 'oge15_p19_img3.png'],
                            ['id' => 66, 'text' => 'В треугольнике ABC известно, что AC=7, BC=24, угол C равен 90°. Найдите радиус описанной окружности этого треугольника.', 'image' => 'oge15_p19_img4.png'],
                        ]
                    ],
                    // Page 6 - Синус острого угла
                    [
                        'number' => 20,
                        'instruction' => 'Синус острого угла (найти sinB)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 67, 'text' => 'В треугольнике ABC угол C равен 90°, AC=11, AB=20. Найдите sinB.', 'image' => 'oge15_p20_img1.png'],
                            ['id' => 68, 'text' => 'В треугольнике ABC угол C равен 90°, AC=7, AB=25. Найдите sinB.', 'image' => 'oge15_p20_img2.png'],
                            ['id' => 69, 'text' => 'В треугольнике ABC угол C равен 90°, AC=4, AB=5. Найдите sinB.', 'image' => 'oge15_p20_img3.png'],
                            ['id' => 70, 'text' => 'В треугольнике ABC угол C равен 90°, AC=24, AB=25. Найдите sinB.', 'image' => 'oge15_p20_img4.png'],
                        ]
                    ],
                    // Page 6-7 - Косинус острого угла
                    [
                        'number' => 21,
                        'instruction' => 'Косинус острого угла (найти cosB)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 71, 'text' => 'В треугольнике ABC угол C равен 90°, BC=13, AB=20. Найдите cosB.', 'image' => 'oge15_p21_img1.png'],
                            ['id' => 72, 'text' => 'В треугольнике ABC угол C равен 90°, BC=72, AB=75. Найдите cosB.', 'image' => 'oge15_p21_img2.png'],
                            ['id' => 73, 'text' => 'В треугольнике ABC угол C равен 90°, BC=30, AB=50. Найдите cosB.', 'image' => 'oge15_p21_img3.png'],
                            ['id' => 74, 'text' => 'В треугольнике ABC угол C равен 90°, BC=14, AB=50. Найдите cosB.', 'image' => 'oge15_p21_img4.png'],
                        ]
                    ],
                    // Page 7 - Тангенс острого угла
                    [
                        'number' => 22,
                        'instruction' => 'Тангенс острого угла (найти tgB)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 75, 'text' => 'В треугольнике ABC угол C равен 90°, BC=10, AC=7. Найдите tgB.', 'image' => 'oge15_p22_img1.png'],
                            ['id' => 76, 'text' => 'В треугольнике ABC угол C равен 90°, BC=15, AC=3. Найдите tgB.', 'image' => 'oge15_p22_img2.png'],
                            ['id' => 77, 'text' => 'В треугольнике ABC угол C равен 90°, BC=9, AC=27. Найдите tgB.', 'image' => 'oge15_p22_img3.png'],
                            ['id' => 78, 'text' => 'В треугольнике ABC угол C равен 90°, BC=4, AC=28. Найдите tgB.', 'image' => 'oge15_p22_img4.png'],
                        ]
                    ],
                    // Page 7 - sinB дан, найти AC
                    [
                        'number' => 23,
                        'instruction' => 'Синус дан, найти сторону AC',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 79, 'text' => 'В треугольнике ABC угол C равен 90°, sinB = 4/9, AB=18. Найдите AC.', 'image' => 'oge15_p23_img1.png'],
                            ['id' => 80, 'text' => 'В треугольнике ABC угол C равен 90°, sinB = 5/17, AB=51. Найдите AC.', 'image' => 'oge15_p23_img2.png'],
                            ['id' => 81, 'text' => 'В треугольнике ABC угол C равен 90°, sinB = 4/11, AB=55. Найдите AC.', 'image' => 'oge15_p23_img3.png'],
                            ['id' => 82, 'text' => 'В треугольнике ABC угол C равен 90°, sinB = 7/12, AB=48. Найдите AC.', 'image' => 'oge15_p23_img4.png'],
                        ]
                    ],
                    // Page 7-8 - cosB дан, найти BC
                    [
                        'number' => 24,
                        'instruction' => 'Косинус дан, найти сторону BC',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 83, 'text' => 'В треугольнике ABC угол C равен 90°, cosB = 2/5, AB=10. Найдите BC.', 'image' => 'oge15_p24_img1.png'],
                            ['id' => 84, 'text' => 'В треугольнике ABC угол C равен 90°, cosB = 7/9, AB=54. Найдите BC.', 'image' => 'oge15_p24_img2.png'],
                            ['id' => 85, 'text' => 'В треугольнике ABC угол C равен 90°, cosB = 11/15, AB=75. Найдите BC.', 'image' => 'oge15_p24_img3.png'],
                            ['id' => 86, 'text' => 'В треугольнике ABC угол C равен 90°, cosB = 13/16, AB=96. Найдите BC.', 'image' => 'oge15_p24_img4.png'],
                        ]
                    ],
                    // Page 8 - tgB дан, найти AC
                    [
                        'number' => 25,
                        'instruction' => 'Тангенс дан, найти сторону AC',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 87, 'text' => 'В треугольнике ABC угол C равен 90°, tgB = 7/12, BC=48. Найдите AC.', 'image' => 'oge15_p25_img1.png'],
                            ['id' => 88, 'text' => 'В треугольнике ABC угол C равен 90°, tgB = 4/7, BC=35. Найдите AC.', 'image' => 'oge15_p25_img2.png'],
                            ['id' => 89, 'text' => 'В треугольнике ABC угол C равен 90°, tgB = 8/5, BC=20. Найдите AC.', 'image' => 'oge15_p25_img3.png'],
                            ['id' => 90, 'text' => 'В треугольнике ABC угол C равен 90°, tgB = 9/7, BC=42. Найдите AC.', 'image' => 'oge15_p25_img4.png'],
                        ]
                    ],
                    // Page 8 - Теорема о площади треугольника
                    [
                        'number' => 26,
                        'instruction' => 'Теорема о площади треугольника',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 91, 'text' => 'В треугольнике ABC известно, что AB=15, BC=8, sin∠ABC = 5/6. Найдите площадь треугольника ABC.', 'image' => 'oge15_p26_img1.png'],
                            ['id' => 92, 'text' => 'В треугольнике ABC известно, что AB=10, BC=12, sin∠ABC = 8/15. Найдите площадь треугольника ABC.', 'image' => 'oge15_p26_img2.png'],
                            ['id' => 93, 'text' => 'В треугольнике ABC известно, что AB=12, BC=15, sin∠ABC = 4/9. Найдите площадь треугольника ABC.', 'image' => 'oge15_p26_img3.png'],
                            ['id' => 94, 'text' => 'В треугольнике ABC известно, что AB=9, BC=16, sin∠ABC = 7/12. Найдите площадь треугольника ABC.', 'image' => 'oge15_p26_img4.png'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display Topic 16 - Окружность, круг и их элементы
     */
    public function topic16()
    {
        $blocks = $this->getAllBlocksData16();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic16', compact('blocks', 'source'));
    }

    /**
     * Get all blocks data for Topic 16 - Окружность, круг и их элементы
     */
    protected function getAllBlocksData16(): array
    {
        return [
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    [
                        'number' => 1,
                        'instruction' => 'Касательная к окружности (квадрат)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 1, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен 2√5. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p1_img1.png'],
                            ['id' => 2, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен 3√5. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p2_img1.png'],
                            ['id' => 3, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен √10. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p2_img2.png'],
                            ['id' => 4, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен √5/2. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p2_img3.png'],
                            ['id' => 5, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен 1. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p2_img4.png'],
                            ['id' => 6, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен 3. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p3_img1.png'],
                            ['id' => 7, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен 0,5. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p3_img2.png'],
                            ['id' => 8, 'text' => 'Точка O является серединой стороны CD квадрата ABCD. Радиус окружности с центром в точке O, проходящей через вершину A, равен 1,5. Найдите площадь квадрата ABCD.', 'image' => 'oge16_p3_img3.png'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'Касательные к окружности',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 9, 'text' => 'Касательные в точках A и B к окружности с центром O пересекаются под углом 56°. Найдите угол ABO.', 'image' => 'oge16_p3_img4.png'],
                            ['id' => 10, 'text' => 'Касательные в точках A и B к окружности с центром O пересекаются под углом 42°. Найдите угол ABO.', 'image' => 'oge16_p4_img1.png'],
                            ['id' => 11, 'text' => 'Касательные в точках A и B к окружности с центром O пересекаются под углом 86°. Найдите угол ABO.', 'image' => 'oge16_p4_img2.png'],
                            ['id' => 12, 'text' => 'Касательные в точках A и B к окружности с центром O пересекаются под углом 38°. Найдите угол ABO.', 'image' => 'oge16_p4_img3.png'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'Вписанный угол',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 13, 'text' => 'Треугольник ABC вписан в окружность с центром в точке O. Точки O и C лежат в одной полуплоскости относительно прямой AB. Найдите угол ACB, если угол AOB равен 59°.', 'image' => 'oge16_p4_img4.png'],
                            ['id' => 14, 'text' => 'Треугольник ABC вписан в окружность с центром в точке O. Точки O и C лежат в одной полуплоскости относительно прямой AB. Найдите угол ACB, если угол AOB равен 47°.', 'image' => 'oge16_p5_img1.png'],
                            ['id' => 15, 'text' => 'Треугольник ABC вписан в окружность с центром в точке O. Точки O и C лежат в одной полуплоскости относительно прямой AB. Найдите угол ACB, если угол AOB равен 113°.', 'image' => 'oge16_p5_img2.png'],
                            ['id' => 16, 'text' => 'Треугольник ABC вписан в окружность с центром в точке O. Точки O и C лежат в одной полуплоскости относительно прямой AB. Найдите угол ACB, если угол AOB равен 173°.', 'image' => 'oge16_p5_img3.png'],
                        ]
                    ],
                    [
                        'number' => 4,
                        'instruction' => 'Центральные и вписанные углы (диаметры)',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 17, 'text' => 'Отрезки AC и BD – диаметры окружности с центром O. Угол ACB равен 19°. Найдите угол AOD.', 'image' => 'oge16_p5_img4.png'],
                            ['id' => 18, 'text' => 'В окружности с центром O AC и BD – диаметры. Угол ACB равен 16°. Найдите угол AOD.', 'image' => 'oge16_p6_img1.png'],
                            ['id' => 19, 'text' => 'В окружности с центром в точке O отрезки AC и BD – диаметры. Угол AOD равен 146°. Найдите угол ACB.', 'image' => 'oge16_p6_img2.png'],
                            ['id' => 20, 'text' => 'В окружности с центром в точке O отрезки AC и BD – диаметры. Угол AOD равен 108°. Найдите угол ACB.', 'image' => 'oge16_p7_img1.png'],
                            ['id' => 21, 'text' => 'AC и BD – диаметры окружности с центром O. Угол ACB равен 54°. Найдите угол AOD.', 'image' => 'oge16_p7_img2.png'],
                            ['id' => 22, 'text' => 'AC и BD – диаметры окружности с центром O. Угол ACB равен 78°. Найдите угол AOD.', 'image' => 'oge16_p7_img3.png'],
                            ['id' => 23, 'text' => 'В окружности с центром в точке O отрезки AC и BD – диаметры. Угол AOD равен 42°. Найдите угол ACB.', 'image' => 'oge16_p7_img4.png'],
                            ['id' => 24, 'text' => 'В окружности с центром в точке O отрезки AC и BD – диаметры. Угол AOD равен 50°. Найдите угол ACB.', 'image' => 'oge16_p7_img5.png'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'Углы, опирающиеся на диаметр',
                        'type' => 'geometry',
                        'tasks' => [
                            ['id' => 25, 'text' => 'На окружности по разные стороны от диаметра AB взяты точки M и N. Известно, что ∠NBA = 32°. Найдите угол NMB.', 'image' => 'oge16_p8_img1.png'],
                            ['id' => 26, 'text' => 'На окружности по разные стороны от диаметра AB взяты точки M и N. Известно, что ∠NBA = 43°. Найдите угол NMB.', 'image' => 'oge16_p8_img2.png'],
                            ['id' => 27, 'text' => 'На окружности по разные стороны от диаметра AB взяты точки M и N. Известно, что ∠NBA = 71°. Найдите угол NMB.', 'image' => 'oge16_p8_img3.png'],
                            ['id' => 28, 'text' => 'На окружности по разные стороны от диаметра AB взяты точки M и N. Известно, что ∠NBA = 68°. Найдите угол NMB.', 'image' => 'oge16_p8_img4.png'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Display Topic 18 - Фигуры на квадратной решётке
     */
    public function topic18()
    {
        $blocks = $this->getAllBlocksData18();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic18', compact('blocks', 'source'));
    }

    /**
     * Display Topic 19 - Анализ геометрических высказываний
     */
    public function topic19()
    {
        $blocks = $this->getAllBlocksData19();
        $source = 'Manual (все блоки из PDF)';

        return view('test.topic19', compact('blocks', 'source'));
    }

    /**
     * Get random tasks from manual topic 18 data
     */
    protected function getRandomTasksFromManualData18(int $count): array
    {
        $blocks = $this->getAllBlocksData18();
        return $this->extractRandomTasks($blocks, '18', 'Фигуры на квадратной решётке', $count);
    }

    /**
     * Get random tasks from manual topic 19 data
     */
    protected function getRandomTasksFromManualData19(int $count): array
    {
        $blocks = $this->getAllBlocksData19();
        return $this->extractRandomTasks($blocks, '19', 'Анализ геометрических высказываний', $count);
    }

    /**
     * Get all blocks data for Topic 18 - Фигуры на квадратной решётке
     */
    protected function getAllBlocksData18(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // I) Длина
                    [
                        'number' => 1,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1×1 изображен прямоугольный треугольник. Найдите длину его большего катета.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p1_img1.png'],
                            ['id' => 2, 'image' => 'oge18_p1_img2.png'],
                            ['id' => 3, 'image' => 'oge18_p1_img3.png'],
                            ['id' => 4, 'image' => 'oge18_p1_img4.png'],
                            ['id' => 5, 'image' => 'oge18_p1_img5.png'],
                            ['id' => 6, 'image' => 'oge18_p1_img6.png'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1×1 изображён ромб. Найдите длину его большей диагонали.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p1_img7.png'],
                            ['id' => 2, 'image' => 'oge18_p1_img8.png'],
                            ['id' => 3, 'image' => 'oge18_p1_img9.png'],
                            ['id' => 4, 'image' => 'oge18_p1_img10.png'],
                            ['id' => 5, 'image' => 'oge18_p1_img11.png'],
                            ['id' => 6, 'image' => 'oge18_p1_img12.png'],
                        ]
                    ],
                    // II) Теорема Фалеса
                    [
                        'number' => 3,
                        'instruction' => 'На клетчатой бумаге изображён треугольник ABC.',
                        'type' => 'grid_image_with_question',
                        'tasks' => [
                            ['id' => 1, 'question' => 'Во сколько раз отрезок AM короче отрезка CM?', 'image' => 'oge18_p2_img1.png'],
                            ['id' => 2, 'question' => 'Во сколько раз отрезок AM длиннее отрезка CM?', 'image' => 'oge18_p2_img2.png'],
                            ['id' => 3, 'question' => 'Во сколько раз отрезок BM короче отрезка CM?', 'image' => 'oge18_p2_img3.png'],
                            ['id' => 4, 'question' => 'Во сколько раз отрезок BM длиннее отрезка CM?', 'image' => 'oge18_p2_img4.png'],
                            ['id' => 5, 'question' => 'Во сколько раз отрезок AM короче отрезка BM?', 'image' => 'oge18_p2_img5.png'],
                            ['id' => 6, 'question' => 'Во сколько раз отрезок AM длиннее отрезка BM?', 'image' => 'oge18_p2_img6.png'],
                        ]
                    ],
                    // III) Площадь
                    [
                        'number' => 4,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см изображена фигура. Найдите её площадь. Ответ дайте в квадратных сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p2_img7.png'],
                            ['id' => 2, 'image' => 'oge18_p2_img8.png'],
                            ['id' => 3, 'image' => 'oge18_p2_img9.png'],
                            ['id' => 4, 'image' => 'oge18_p2_img10.png'],
                            ['id' => 5, 'image' => 'oge18_p2_img11.png'],
                            ['id' => 6, 'image' => 'oge18_p2_img12.png'],
                            ['id' => 7, 'image' => 'oge18_p3_img1.png'],
                            ['id' => 8, 'image' => 'oge18_p3_img2.png'],
                            ['id' => 9, 'image' => 'oge18_p3_img3.png'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см изображена фигура. Найдите её площадь. Ответ дайте в квадратных сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p3_img4.png'],
                            ['id' => 2, 'image' => 'oge18_p3_img5.png'],
                            ['id' => 3, 'image' => 'oge18_p3_img6.png'],
                            ['id' => 4, 'image' => 'oge18_p3_img7.png'],
                            ['id' => 5, 'image' => 'oge18_p3_img8.png'],
                            ['id' => 6, 'image' => 'oge18_p3_img9.png'],
                            ['id' => 7, 'image' => 'oge18_p3_img10.png'],
                            ['id' => 8, 'image' => 'oge18_p3_img11.png'],
                            ['id' => 9, 'image' => 'oge18_p3_img12.png'],
                        ]
                    ],
                    [
                        'number' => 6,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см изображена фигура. Найдите её площадь. Ответ дайте в квадратных сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p3_img13.png'],
                            ['id' => 2, 'image' => 'oge18_p3_img14.png'],
                            ['id' => 3, 'image' => 'oge18_p3_img15.png'],
                            ['id' => 4, 'image' => 'oge18_p4_img1.png'],
                            ['id' => 5, 'image' => 'oge18_p4_img2.png'],
                            ['id' => 6, 'image' => 'oge18_p4_img3.png'],
                            ['id' => 7, 'image' => 'oge18_p4_img4.png'],
                            ['id' => 8, 'image' => 'oge18_p4_img5.png'],
                            ['id' => 9, 'image' => 'oge18_p4_img6.png'],
                        ]
                    ],
                    [
                        'number' => 7,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см изображена фигура. Найдите её площадь. Ответ дайте в квадратных сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p4_img7.png'],
                            ['id' => 2, 'image' => 'oge18_p4_img8.png'],
                            ['id' => 3, 'image' => 'oge18_p4_img9.png'],
                            ['id' => 4, 'image' => 'oge18_p4_img10.png'],
                            ['id' => 5, 'image' => 'oge18_p4_img11.png'],
                            ['id' => 6, 'image' => 'oge18_p4_img12.png'],
                            ['id' => 7, 'image' => 'oge18_p4_img13.png'],
                            ['id' => 8, 'image' => 'oge18_p4_img14.png'],
                            ['id' => 9, 'image' => 'oge18_p4_img15.png'],
                        ]
                    ],
                    // IV) Теорема Пифагора
                    [
                        'number' => 8,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1×1 изображены две точки. Найдите расстояние между ними.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p5_img1.png'],
                            ['id' => 2, 'image' => 'oge18_p5_img2.png'],
                            ['id' => 3, 'image' => 'oge18_p5_img3.png'],
                            ['id' => 4, 'image' => 'oge18_p5_img4.png'],
                            ['id' => 5, 'image' => 'oge18_p5_img5.png'],
                            ['id' => 6, 'image' => 'oge18_p5_img6.png'],
                        ]
                    ],
                    // V) Подобные треугольники. Средняя линия
                    [
                        'number' => 9,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1×1 изображён △ABC. Найдите длину его средней линии, параллельной стороне AC.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p5_img7.png'],
                            ['id' => 2, 'image' => 'oge18_p5_img8.png'],
                            ['id' => 3, 'image' => 'oge18_p5_img9.png'],
                            ['id' => 4, 'image' => 'oge18_p5_img10.png'],
                            ['id' => 5, 'image' => 'oge18_p6_img1.png'],
                            ['id' => 6, 'image' => 'oge18_p6_img2.png'],
                        ]
                    ],
                    [
                        'number' => 10,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1×1 изображена фигура. Найдите длину отрезка AB по данным чертежа.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p6_img3.png'],
                            ['id' => 2, 'image' => 'oge18_p6_img4.png'],
                            ['id' => 3, 'image' => 'oge18_p6_img5.png'],
                            ['id' => 4, 'image' => 'oge18_p6_img6.png'],
                            ['id' => 5, 'image' => 'oge18_p6_img7.png'],
                            ['id' => 6, 'image' => 'oge18_p6_img8.png'],
                            ['id' => 7, 'image' => 'oge18_p6_img9.png'],
                            ['id' => 8, 'image' => 'oge18_p6_img10.png'],
                            ['id' => 9, 'image' => 'oge18_p6_img11.png'],
                        ]
                    ],
                    [
                        'number' => 11,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1×1 изображена трапеция. Найдите длину её средней линии.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p6_img12.png'],
                            ['id' => 2, 'image' => 'oge18_p6_img13.png'],
                            ['id' => 3, 'image' => 'oge18_p7_img1.png'],
                            ['id' => 4, 'image' => 'oge18_p7_img2.png'],
                            ['id' => 5, 'image' => 'oge18_p7_img3.png'],
                            ['id' => 6, 'image' => 'oge18_p7_img4.png'],
                            ['id' => 7, 'image' => 'oge18_p7_img5.png'],
                            ['id' => 8, 'image' => 'oge18_p7_img6.png'],
                            ['id' => 9, 'image' => 'oge18_p7_img7.png'],
                        ]
                    ],
                    // VI) Площадь круга
                    [
                        'number' => 12,
                        'instruction' => 'На клетчатой бумаге изображены два круга. Во сколько раз площадь большего круга больше площади меньшего?',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p7_img8.png'],
                            ['id' => 2, 'image' => 'oge18_p7_img9.png'],
                            ['id' => 3, 'image' => 'oge18_p7_img10.png'],
                            ['id' => 4, 'image' => 'oge18_p7_img11.png'],
                            ['id' => 5, 'image' => 'oge18_p7_img12.png'],
                            ['id' => 6, 'image' => 'oge18_p8_img1.png'],
                            ['id' => 7, 'image' => 'oge18_p8_img2.png'],
                            ['id' => 8, 'image' => 'oge18_p8_img3.png'],
                            ['id' => 9, 'image' => 'oge18_p8_img4.png'],
                        ]
                    ],
                ]
            ],
            // =====================
            // БЛОК 2. ФИПИ. Расширенная версия
            // =====================
            [
                'number' => 2,
                'title' => 'ФИПИ. Расширенная версия',
                'zadaniya' => [
                    // I) Расстояние
                    [
                        'number' => 1,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см отмечены точки А, В и С. Найдите расстояние от точки А до середины отрезка ВС. Ответ выразите в сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p8_img5.png'],
                            ['id' => 2, 'image' => 'oge18_p8_img6.png'],
                            ['id' => 3, 'image' => 'oge18_p9_img1.png'],
                            ['id' => 4, 'image' => 'oge18_p9_img2.png'],
                            ['id' => 5, 'image' => 'oge18_p9_img3.png'],
                            ['id' => 6, 'image' => 'oge18_p9_img4.png'],
                        ]
                    ],
                    [
                        'number' => 2,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см отмечены точки А, В и С. Найдите расстояние от точки А до прямой ВС. Ответ выразите в сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p9_img5.png'],
                            ['id' => 2, 'image' => 'oge18_p9_img6.png'],
                            ['id' => 3, 'image' => 'oge18_p9_img7.png'],
                            ['id' => 4, 'image' => 'oge18_p9_img8.png'],
                            ['id' => 5, 'image' => 'oge18_p9_img9.png'],
                            ['id' => 6, 'image' => 'oge18_p9_img10.png'],
                        ]
                    ],
                    [
                        'number' => 3,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см отмечены точки А, В и С. Найдите расстояние от точки А до середины отрезка ВС. Ответ выразите в сантиметрах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p9_img11.png'],
                            ['id' => 2, 'image' => 'oge18_p9_img12.png'],
                            ['id' => 3, 'image' => 'oge18_p10_img1.png'],
                            ['id' => 4, 'image' => 'oge18_p10_img2.png'],
                            ['id' => 5, 'image' => 'oge18_p10_img3.png'],
                            ['id' => 6, 'image' => 'oge18_p10_img4.png'],
                        ]
                    ],
                    // II) Площадь
                    [
                        'number' => 4,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см изображена фигура. Найдите её площадь.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p10_img5.png'],
                            ['id' => 2, 'image' => 'oge18_p10_img6.png'],
                            ['id' => 3, 'image' => 'oge18_p10_img7.png'],
                            ['id' => 4, 'image' => 'oge18_p10_img8.png'],
                            ['id' => 5, 'image' => 'oge18_p10_img9.png'],
                            ['id' => 6, 'image' => 'oge18_p10_img10.png'],
                        ]
                    ],
                    [
                        'number' => 5,
                        'instruction' => 'На клетчатой бумаге с размером клетки 1 см × 1 см изображена фигура. Найдите её площадь.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p10_img11.png'],
                            ['id' => 2, 'image' => 'oge18_p10_img12.png'],
                            ['id' => 3, 'image' => 'oge18_p11_img1.png'],
                            ['id' => 4, 'image' => 'oge18_p11_img2.png'],
                            ['id' => 5, 'image' => 'oge18_p11_img3.png'],
                            ['id' => 6, 'image' => 'oge18_p11_img4.png'],
                            ['id' => 7, 'image' => 'oge18_p11_img5.png'],
                            ['id' => 8, 'image' => 'oge18_p11_img6.png'],
                            ['id' => 9, 'image' => 'oge18_p11_img7.png'],
                        ]
                    ],
                    // III) Углы
                    [
                        'number' => 6,
                        'instruction' => 'Найдите угол ABC. Ответ дайте в градусах.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p11_img8.png'],
                            ['id' => 2, 'image' => 'oge18_p11_img9.png'],
                            ['id' => 3, 'image' => 'oge18_p11_img10.png'],
                            ['id' => 4, 'image' => 'oge18_p11_img11.png'],
                            ['id' => 5, 'image' => 'oge18_p11_img12.png'],
                            ['id' => 6, 'image' => 'oge18_p12_img1.png'],
                        ]
                    ],
                    [
                        'number' => 7,
                        'instruction' => 'Найдите тангенс угла А треугольника ABC, изображённого на рисунке.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p12_img2.png'],
                            ['id' => 2, 'image' => 'oge18_p12_img3.png'],
                        ]
                    ],
                    [
                        'number' => 8,
                        'instruction' => 'Найдите тангенс угла B треугольника ABC, изображённого на рисунке.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p12_img4.png'],
                            ['id' => 2, 'image' => 'oge18_p12_img5.png'],
                        ]
                    ],
                    [
                        'number' => 9,
                        'instruction' => 'Найдите тангенс угла С треугольника ABC, изображённого на рисунке.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p12_img6.png'],
                            ['id' => 2, 'image' => 'oge18_p12_img7.png'],
                        ]
                    ],
                    [
                        'number' => 10,
                        'instruction' => 'Найдите тангенс угла AOB, изображенного на рисунке.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p12_img8.png'],
                            ['id' => 2, 'image' => 'oge18_p12_img9.png'],
                            ['id' => 3, 'image' => 'oge18_p12_img10.png'],
                            ['id' => 4, 'image' => 'oge18_p12_img11.png'],
                            ['id' => 5, 'image' => 'oge18_p12_img12.png'],
                            ['id' => 6, 'image' => 'oge18_p13_img1.png'],
                            ['id' => 7, 'image' => 'oge18_p13_img2.png'],
                            ['id' => 8, 'image' => 'oge18_p13_img3.png'],
                            ['id' => 9, 'image' => 'oge18_p13_img4.png'],
                            ['id' => 10, 'image' => 'oge18_p13_img5.png'],
                            ['id' => 11, 'image' => 'oge18_p13_img6.png'],
                            ['id' => 12, 'image' => 'oge18_p13_img7.png'],
                        ]
                    ],
                    [
                        'number' => 11,
                        'instruction' => 'Найдите тангенс угла AOB, изображенного на рисунке.',
                        'type' => 'grid_image',
                        'tasks' => [
                            ['id' => 1, 'image' => 'oge18_p13_img8.png'],
                            ['id' => 2, 'image' => 'oge18_p13_img9.png'],
                            ['id' => 3, 'image' => 'oge18_p13_img10.png'],
                            ['id' => 4, 'image' => 'oge18_p13_img11.png'],
                            ['id' => 5, 'image' => 'oge18_p13_img12.png'],
                            ['id' => 6, 'image' => 'oge18_p14_img1.png'],
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Get all blocks data for Topic 19 - Анализ геометрических высказываний
     */
    protected function getAllBlocksData19(): array
    {
        return [
            // =====================
            // БЛОК 1. ФИПИ
            // =====================
            [
                'number' => 1,
                'title' => 'ФИПИ',
                'zadaniya' => [
                    // I) Начальные геометрические сведения
                    [
                        'number' => 1,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Начальные геометрические сведения (отрезки, прямые и углы)',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 1, 'text' => 'Точка, лежащая на серединном перпендикуляре к отрезку, равноудалена от концов этого отрезка.', 'is_true' => true],
                            ['id' => 2, 'text' => 'Существуют три прямые, которые проходят через одну точку.', 'is_true' => true],
                            ['id' => 3, 'text' => 'Смежные углы всегда равны.', 'is_true' => false],
                            ['id' => 4, 'text' => 'Вертикальные углы равны.', 'is_true' => true],
                            ['id' => 5, 'text' => 'Всегда один из двух смежных углов острый, а другой тупой.', 'is_true' => false],
                            ['id' => 6, 'text' => 'Через заданную точку плоскости можно провести только одну прямую.', 'is_true' => false],
                            ['id' => 7, 'text' => 'Если точка лежит на биссектрисе угла, то она равноудалена от сторон этого угла.', 'is_true' => true],
                            ['id' => 8, 'text' => 'Если угол острый, то смежный с ним угол также является острым.', 'is_true' => false],
                        ]
                    ],
                    // II) Параллельные и перпендикулярные прямые
                    [
                        'number' => 2,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Параллельные и перпендикулярные прямые',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 9, 'text' => 'Две прямые, параллельные третьей прямой, перпендикулярны.', 'is_true' => false],
                            ['id' => 10, 'text' => 'Две прямые, перпендикулярные третьей прямой, перпендикулярны.', 'is_true' => false],
                            ['id' => 11, 'text' => 'Две различные прямые, перпендикулярные третьей прямой, параллельны.', 'is_true' => true],
                            ['id' => 12, 'text' => 'Через точку, не лежащую на данной прямой, можно провести прямую, перпендикулярную этой прямой.', 'is_true' => true],
                            ['id' => 13, 'text' => 'Через точку, не лежащую на данной прямой, можно провести прямую, параллельную этой прямой.', 'is_true' => true],
                        ]
                    ],
                    // III) Треугольник
                    [
                        'number' => 3,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Треугольник',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 14, 'text' => 'Если в треугольнике есть один острый угол, то этот треугольник остроугольный.', 'is_true' => false],
                            ['id' => 15, 'text' => 'В любом тупоугольном треугольнике есть острый угол.', 'is_true' => true],
                            ['id' => 16, 'text' => 'В тупоугольном треугольнике все углы тупые.', 'is_true' => false],
                            ['id' => 17, 'text' => 'В остроугольном треугольнике все углы острые.', 'is_true' => true],
                            ['id' => 18, 'text' => 'В треугольнике против большего угла лежит большая сторона.', 'is_true' => true],
                            ['id' => 19, 'text' => 'Внешний угол треугольника больше не смежного с ним внутреннего угла.', 'is_true' => true],
                            ['id' => 20, 'text' => 'Внешний угол треугольника равен сумме его внутренних углов.', 'is_true' => false],
                            ['id' => 21, 'text' => 'Один из углов треугольника всегда не превышает 60 градусов.', 'is_true' => true],
                            ['id' => 22, 'text' => 'Медиана треугольника делит пополам угол, из вершины которого проведена.', 'is_true' => false],
                            ['id' => 23, 'text' => 'Отношение площадей подобных треугольников равно коэффициенту подобия.', 'is_true' => false],
                            ['id' => 24, 'text' => 'Площадь треугольника меньше произведения двух его сторон.', 'is_true' => true],
                            ['id' => 25, 'text' => 'Сумма углов любого треугольника равна 360 градусам.', 'is_true' => false],
                            ['id' => 26, 'text' => 'Треугольник со сторонами 1, 2, 4 существует.', 'is_true' => false],
                            ['id' => 27, 'text' => 'Треугольника со сторонами 1, 2, 5 не существует.', 'is_true' => true],
                            ['id' => 28, 'text' => 'Биссектриса треугольника делит пополам сторону, к которой проведена.', 'is_true' => false],
                            ['id' => 29, 'text' => 'Если два угла одного треугольника равны двум углам другого треугольника, то такие треугольники подобны.', 'is_true' => true],
                            ['id' => 30, 'text' => 'Если две стороны и угол одного треугольника равны соответственно двум сторонам и углу другого треугольника, то такие треугольники равны.', 'is_true' => false],
                            ['id' => 31, 'text' => 'Если две стороны одного треугольника соответственно равны двум сторонам другого треугольника, то такие треугольники равны.', 'is_true' => false],
                            ['id' => 32, 'text' => 'Если три угла одного треугольника равны соответственно трём углам другого треугольника, то такие треугольники равны.', 'is_true' => false],
                            ['id' => 33, 'text' => 'Биссектрисы треугольника пересекаются в точке, которая является центром окружности, вписанной в треугольник.', 'is_true' => true],
                            ['id' => 34, 'text' => 'Серединные перпендикуляры к сторонам треугольника пересекаются в точке, являющейся центром окружности, описанной около треугольника.', 'is_true' => true],
                            ['id' => 35, 'text' => 'Все равнобедренные треугольники подобны.', 'is_true' => false],
                            ['id' => 36, 'text' => 'Всякий равнобедренный треугольник является остроугольным.', 'is_true' => false],
                            ['id' => 37, 'text' => 'Каждая из биссектрис равнобедренного треугольника является его высотой.', 'is_true' => false],
                            ['id' => 38, 'text' => 'Каждая из биссектрис равнобедренного треугольника является его медианой.', 'is_true' => false],
                            ['id' => 39, 'text' => 'Сумма углов равнобедренного треугольника равна 180 градусам.', 'is_true' => true],
                            ['id' => 40, 'text' => 'Все высоты равностороннего треугольника равны.', 'is_true' => true],
                            ['id' => 41, 'text' => 'Всякий равносторонний треугольник является равнобедренным.', 'is_true' => true],
                            ['id' => 42, 'text' => 'Всякий равносторонний треугольник является остроугольным.', 'is_true' => true],
                            ['id' => 43, 'text' => 'Любые два равносторонних треугольника подобны.', 'is_true' => true],
                            ['id' => 44, 'text' => 'Все равносторонние треугольники подобны.', 'is_true' => true],
                            ['id' => 45, 'text' => 'В прямоугольном треугольнике гипотенуза равна сумме катетов.', 'is_true' => false],
                            ['id' => 46, 'text' => 'Все прямоугольные треугольники подобны.', 'is_true' => false],
                            ['id' => 47, 'text' => 'В прямоугольном треугольнике квадрат гипотенузы равен разности квадратов катетов.', 'is_true' => false],
                            ['id' => 48, 'text' => 'Длина гипотенузы прямоугольного треугольника меньше суммы длин его катетов.', 'is_true' => true],
                            ['id' => 49, 'text' => 'Площадь прямоугольного треугольника равна произведению длин его катетов.', 'is_true' => false],
                            ['id' => 50, 'text' => 'Косинус острого угла прямоугольного треугольника равен отношению гипотенузы к прилежащему к этому углу катету.', 'is_true' => false],
                            ['id' => 51, 'text' => 'Сумма углов прямоугольного треугольника равна 90 градусам.', 'is_true' => false],
                            ['id' => 52, 'text' => 'Тангенс любого острого угла меньше единицы.', 'is_true' => false],
                            ['id' => 53, 'text' => 'Сумма острых углов прямоугольного треугольника равна 90 градусам.', 'is_true' => true],
                        ]
                    ],
                    // IV) Четырехугольник
                    [
                        'number' => 4,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Четырехугольник',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 54, 'text' => 'В любой четырёхугольник можно вписать окружность.', 'is_true' => false],
                            ['id' => 55, 'text' => 'Если стороны одного четырёхугольника соответственно равны сторонам другого четырёхугольника, то такие четырёхугольники равны.', 'is_true' => false],
                            ['id' => 56, 'text' => 'Сумма углов выпуклого четырёхугольника равна 360 градусам.', 'is_true' => true],
                        ]
                    ],
                    // V) Параллелограмм
                    [
                        'number' => 5,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Параллелограмм',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 57, 'text' => 'Диагонали параллелограмма равны.', 'is_true' => false],
                            ['id' => 58, 'text' => 'В параллелограмме есть два равных угла.', 'is_true' => true],
                            ['id' => 59, 'text' => 'Площадь любого параллелограмма равна произведению длин его сторон.', 'is_true' => false],
                            ['id' => 60, 'text' => 'Площадь параллелограмма равна половине произведения его диагоналей.', 'is_true' => false],
                            ['id' => 61, 'text' => 'Диагональ параллелограмма делит его на два равных треугольника.', 'is_true' => true],
                        ]
                    ],
                    // VI) Квадрат, прямоугольник
                    [
                        'number' => 6,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Квадрат, прямоугольник',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 62, 'text' => 'В любой прямоугольник можно вписать окружность.', 'is_true' => false],
                            ['id' => 63, 'text' => 'Диагонали любого прямоугольника делят его на четыре равных треугольника.', 'is_true' => false],
                            ['id' => 64, 'text' => 'Диагонали прямоугольника точкой пересечения делятся пополам.', 'is_true' => true],
                            ['id' => 65, 'text' => 'Существует прямоугольник, диагонали которого взаимно перпендикулярны.', 'is_true' => true],
                            ['id' => 66, 'text' => 'Если диагонали параллелограмма равны, то это прямоугольник.', 'is_true' => true],
                            ['id' => 67, 'text' => 'Любой прямоугольник можно вписать в окружность.', 'is_true' => true],
                            ['id' => 68, 'text' => 'Все углы прямоугольника равны.', 'is_true' => true],
                            ['id' => 69, 'text' => 'В любом прямоугольнике диагонали взаимно перпендикулярны.', 'is_true' => false],
                            ['id' => 70, 'text' => 'Площадь прямоугольника равна произведению длин всех его сторон.', 'is_true' => false],
                            ['id' => 71, 'text' => 'Площадь прямоугольника равна произведению длин его смежных сторон.', 'is_true' => true],
                            ['id' => 72, 'text' => 'Если в параллелограмме диагонали равны и перпендикулярны, то этот параллелограмм является квадратом.', 'is_true' => true],
                            ['id' => 73, 'text' => 'Если диагонали параллелограмма равны, то этот параллелограмм является квадратом.', 'is_true' => false],
                            ['id' => 74, 'text' => 'Если диагонали выпуклого четырёхугольника равны и перпендикулярны, то этот четырёхугольник является квадратом.', 'is_true' => false],
                            ['id' => 75, 'text' => 'Любой квадрат является прямоугольником.', 'is_true' => true],
                            ['id' => 76, 'text' => 'Площадь квадрата равна произведению двух его смежных сторон.', 'is_true' => true],
                            ['id' => 77, 'text' => 'Площадь квадрата равна произведению его диагоналей.', 'is_true' => false],
                            ['id' => 78, 'text' => 'Существует квадрат, который не является прямоугольником.', 'is_true' => false],
                            ['id' => 79, 'text' => 'Все квадраты имеют равные площади.', 'is_true' => false],
                        ]
                    ],
                    // VII) Трапеция
                    [
                        'number' => 7,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Трапеция',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 80, 'text' => 'Основания любой трапеции параллельны.', 'is_true' => true],
                            ['id' => 81, 'text' => 'Основания равнобедренной трапеции равны.', 'is_true' => false],
                            ['id' => 82, 'text' => 'Площадь трапеции равна произведению основания трапеции на высоту.', 'is_true' => false],
                            ['id' => 83, 'text' => 'Средняя линия трапеции параллельна её основаниям.', 'is_true' => true],
                            ['id' => 84, 'text' => 'Средняя линия трапеции равна сумме её оснований.', 'is_true' => false],
                            ['id' => 85, 'text' => 'Средняя линия трапеции равна полусумме её оснований.', 'is_true' => true],
                            ['id' => 86, 'text' => 'Боковые стороны любой трапеции равны.', 'is_true' => false],
                            ['id' => 87, 'text' => 'В любой прямоугольной трапеции есть два равных угла.', 'is_true' => true],
                            ['id' => 88, 'text' => 'Диагонали трапеции пересекаются и делятся точкой пересечения пополам.', 'is_true' => false],
                            ['id' => 89, 'text' => 'Диагонали прямоугольной трапеции равны.', 'is_true' => false],
                        ]
                    ],
                ]
            ],
            // =====================
            // БЛОК 2. Открытый банк
            // =====================
            [
                'number' => 2,
                'title' => 'Открытый банк',
                'zadaniya' => [
                    // I) Начальные геометрические сведения
                    [
                        'number' => 1,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Начальные геометрические сведения',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 1, 'text' => 'Если угол равен 45°, то вертикальный с ним угол равен 45°.', 'is_true' => true],
                            ['id' => 2, 'text' => 'Если угол равен 60°, то смежный с ним угол равен 120°.', 'is_true' => true],
                            ['id' => 3, 'text' => 'Существует прямоугольник, диагонали которого взаимно перпендикулярны.', 'is_true' => true],
                            ['id' => 4, 'text' => 'Точка пересечения двух окружностей равноудалена от центров этих окружностей.', 'is_true' => false],
                        ]
                    ],
                    // II) Треугольники
                    [
                        'number' => 2,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Треугольники',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 5, 'text' => 'Если один из углов треугольника прямой, то треугольник прямоугольный.', 'is_true' => true],
                            ['id' => 6, 'text' => 'Если гипотенуза и острый угол одного прямоугольного треугольника соответственно равны гипотенузе и углу другого прямоугольного треугольника, то такие треугольники равны.', 'is_true' => true],
                            ['id' => 7, 'text' => 'Площадь треугольника не превышает произведения двух его сторон.', 'is_true' => true],
                            ['id' => 8, 'text' => 'Против большей стороны треугольника лежит больший угол.', 'is_true' => true],
                            ['id' => 9, 'text' => 'Против равных сторон треугольника лежат равные углы.', 'is_true' => true],
                            ['id' => 10, 'text' => 'Сумма углов любого треугольника равна 180°.', 'is_true' => true],
                            ['id' => 11, 'text' => 'Если две стороны одного треугольника пропорциональны двум сторонам другого треугольника и углы, образованные этими сторонами, равны, то треугольники подобны.', 'is_true' => true],
                            ['id' => 12, 'text' => 'Если три стороны одного треугольника пропорциональны трём сторонам другого треугольника, то треугольники подобны.', 'is_true' => true],
                            ['id' => 13, 'text' => 'Если три угла одного треугольника соответственно равны трём углам другого треугольника, то такие треугольники подобны.', 'is_true' => true],
                            ['id' => 14, 'text' => 'В любой треугольник можно вписать окружность.', 'is_true' => true],
                        ]
                    ],
                    // III) Параллелограмм, прямоугольник, квадрат
                    [
                        'number' => 3,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Параллелограмм, прямоугольник, квадрат',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 15, 'text' => 'В любом параллелограмме диагонали точкой пересечения делятся пополам.', 'is_true' => true],
                            ['id' => 16, 'text' => 'Существует параллелограмм, который не является прямоугольником.', 'is_true' => true],
                            ['id' => 17, 'text' => 'Диагонали любого прямоугольника равны.', 'is_true' => true],
                            ['id' => 18, 'text' => 'Существует прямоугольник, который не является параллелограммом.', 'is_true' => false],
                            ['id' => 19, 'text' => 'Не существует прямоугольника, диагонали которого взаимно перпендикулярны.', 'is_true' => false],
                            ['id' => 20, 'text' => 'Диагонали квадрата взаимно перпендикулярны.', 'is_true' => true],
                            ['id' => 21, 'text' => 'Диагонали квадрата точкой пересечения делятся пополам.', 'is_true' => true],
                            ['id' => 22, 'text' => 'Квадрат диагонали прямоугольника равен сумме квадратов двух его смежных сторон.', 'is_true' => true],
                            ['id' => 23, 'text' => 'Квадрат является прямоугольником.', 'is_true' => true],
                            ['id' => 24, 'text' => 'Любой квадрат можно вписать в окружность.', 'is_true' => true],
                            ['id' => 25, 'text' => 'Сумма квадратов диагоналей прямоугольника равна сумме квадратов всех его сторон.', 'is_true' => true],
                        ]
                    ],
                    // IV) Трапеция
                    [
                        'number' => 4,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Трапеция',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 26, 'text' => 'Площадь трапеции равна произведению средней линии на высоту.', 'is_true' => true],
                            ['id' => 27, 'text' => 'У любой трапеции боковые стороны равны.', 'is_true' => false],
                            ['id' => 28, 'text' => 'У любой трапеции основания параллельны.', 'is_true' => true],
                        ]
                    ],
                    // V) Ромб
                    [
                        'number' => 5,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Ромб',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 29, 'text' => 'Если в четырёхугольнике диагонали перпендикулярны, то этот четырёхугольник – ромб.', 'is_true' => false],
                            ['id' => 30, 'text' => 'Если в параллелограмме две смежные стороны равны, то такой параллелограмм является ромбом.', 'is_true' => true],
                            ['id' => 31, 'text' => 'Существует квадрат, который не является ромбом.', 'is_true' => false],
                            ['id' => 32, 'text' => 'Ромб не является параллелограммом.', 'is_true' => false],
                            ['id' => 33, 'text' => 'Любой квадрат является ромбом.', 'is_true' => true],
                            ['id' => 34, 'text' => 'Существует ромб, который не является квадратом.', 'is_true' => true],
                        ]
                    ],
                    // VI) Окружность
                    [
                        'number' => 6,
                        'instruction' => 'Укажите номера верных утверждений.',
                        'section' => 'Окружность',
                        'type' => 'statements',
                        'statements' => [
                            ['id' => 35, 'text' => 'В плоскости все точки, равноудалённые от заданной точки, лежат на одной окружности.', 'is_true' => true],
                            ['id' => 36, 'text' => 'В плоскости для точки, лежащей вне круга, расстояние до центра круга больше его радиуса.', 'is_true' => true],
                            ['id' => 37, 'text' => 'Вокруг любого треугольника можно описать окружность.', 'is_true' => true],
                            ['id' => 38, 'text' => 'Вокруг любого параллелограмма можно описать окружность.', 'is_true' => false],
                            ['id' => 39, 'text' => 'Для точки, лежащей внутри круга, расстояние до центра круга меньше его радиуса.', 'is_true' => true],
                            ['id' => 40, 'text' => 'Если из точки M проведены две касательные к окружности и А и В – точки касания, то отрезки MA и MB равны.', 'is_true' => true],
                            ['id' => 41, 'text' => 'Из двух хорд окружности больше та, середина которой находится дальше от центра окружности.', 'is_true' => false],
                            ['id' => 42, 'text' => 'Площадь круга меньше квадрата длины его диаметра.', 'is_true' => true],
                            ['id' => 43, 'text' => 'Центр вписанной окружности равнобедренного треугольника лежит на высоте, проведённой к основанию треугольника.', 'is_true' => true],
                            ['id' => 44, 'text' => 'Центр описанной окружности равнобедренного треугольника лежит на высоте, проведённой к основанию треугольника.', 'is_true' => true],
                            ['id' => 45, 'text' => 'Центром вписанной в треугольник окружности является точка пересечения его биссектрис.', 'is_true' => true],
                            ['id' => 46, 'text' => 'Центром описанной окружности треугольника является точка пересечения серединных перпендикуляров к его сторонам.', 'is_true' => true],
                            ['id' => 47, 'text' => 'Центры вписанной и описанной окружностей равнобедренного треугольника совпадают.', 'is_true' => false],
                        ]
                    ],
                ]
            ],
        ];
    }
}
