<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MiniAppHelpers;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\StarTransaction;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Models\UserGift;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\MiniAppTaskSanitizer;
use App\Services\MiniVariantService;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\VariantTaskNumberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    use MiniAppHelpers;

    public function __construct(
        private readonly MiniVariantService $miniVariant,
        private readonly OgeAttemptService $attemptService,
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly OgeVariantPoolService $poolService,
        private readonly TaskDataService $taskData,
        private readonly MiniAppTaskCanonicalizer $taskCanonicalizer,
        private readonly MiniAppTaskSanitizer $taskSanitizer,
    ) {}

    private function base(): string
    {
        return 'https://student.' . config('app.base_domain');
    }

    // Onboarding (GET)
    public function onboarding(Request $request)
    {
        if (Auth::check() && Auth::user()->onboarding_completed_at) {
            return redirect($this->base() . '/');
        }
        return view('pwa.student.onboarding');
    }

    // Onboarding (POST)
    public function saveOnboarding(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|min:2|max:100',
            'grade_num'     => 'required|integer|in:9',
            'grade_letter'  => 'required|string|in:А,Б,В,Г,Д,К,М',
            'school_number' => 'required|string|max:20',
            'city'          => 'nullable|string|max:80',
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $user->update([
            'name'                    => $data['name'],
            'grade_num'               => $data['grade_num'],
            'grade_letter'            => $data['grade_letter'],
            'school_number'           => $data['school_number'],
            'city'                    => $data['city'] ?: 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        return redirect($this->base() . '/');
    }

    /**
     * Dashboard — main hub after login.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        // Weak topics (from all submitted/scored attempts)
        $weakTopics = $this->computeWeakTopics($user->id);

        $newFipiCount = $this->countNewFipiTasks();

        // Find all unfinished attempts for resume banner
        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->with('variant')
            ->orderByDesc('last_seen_at')
            ->get();

        $activeAttemptsList = [];
        foreach ($activeAttempts as $att) {
            $variant = $att->variant;
            if (!$variant) continue;
            $answeredCount = $att->answers()->count();
            $totalCount = count($variant->config_json['tasks'] ?? []);
            $mode = $variant->mode ?? 'full';
            $isMini = str_starts_with($mode, 'mini_');
            $activeAttemptsList[] = [
                'id' => $att->id,
                'title' => $variant->title ?? 'Вариант ОГЭ',
                'answeredCount' => $answeredCount,
                'totalCount' => $totalCount,
                'startedAt' => $att->started_at,
                'type' => match ($mode) {
                    'mini_algebra' => 'Мини-ОГЭ · Алгебра',
                    'mini_geometry' => 'Мини-ОГЭ · Геометрия',
                    'mini_mixed' => 'Мини-ОГЭ · Смешанное',
                    'mini_part2' => 'Мини-ОГЭ · 2-я часть',
                    'full' => 'Полный вариант',
                    'part2' => '2-я часть',
                    'tasks_part1' => '1-я часть',
                    default => $isMini ? 'Мини-ОГЭ' : 'Вариант ОГЭ',
                },
            ];
        }

        $hasTeacher = TeacherStudent::where('student_id', $user->id)->exists();

        $pendingGift = UserGift::where('user_id', $user->id)
            ->whereNull('shown_at')
            ->orderBy('id')
            ->first();

        return view('pwa.student.dashboard', compact(
            'user', 'weakTopics', 'newFipiCount', 'activeAttemptsList', 'hasTeacher', 'pendingGift'
        ));
    }

    /**
     * New FIPI tasks showcase by topic.
     */
    public function newTasks(Request $request)
    {
        $topics = ['09', '10', '15', '16', '17'];
        $selected = str_pad((string) $request->query('topic', '09'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topics, true)) {
            $selected = '09';
        }

        $newByTopic = [];
        $groupedByTopic = [];

        foreach ($topics as $topicId) {
            $data = $this->taskData->getTopicData($topicId);
            $tasks = [];
            $allTasksIndexed = [];

            foreach (($data['blocks'] ?? []) as $block) {
                foreach (($block['zadaniya'] ?? []) as $zadanie) {
                    foreach (($zadanie['tasks'] ?? []) as $t) {
                        $tid = $t['id'] ?? null;
                        if (is_int($tid)) {
                            $allTasksIndexed[$tid] = $t;
                        }
                    }

                    if (($zadanie['label'] ?? null) !== 'Новые задания') {
                        continue;
                    }
                    $tasks = $zadanie['tasks'] ?? [];
                }
            }

            $all = [];
            foreach ($tasks as $task) {
                $text = trim((string) ($task['text'] ?? ''));
                if ($text === '') continue;

                $all[] = [
                    'id' => $task['id'] ?? null,
                    'src_key' => $task['src_key'] ?? null,
                    'text' => $text,
                    'svg' => $task['svg'] ?? null,
                    'image' => $task['image'] ?? null,
                    'answer' => $task['answer'] ?? null,
                ];
            }
            $newByTopic[$topicId] = $all;

            if ($topicId === '10') {
                $spec = [
                    ['title' => 'Орёл или решка', 'ids' => array_merge(range(85, 94), range(77, 80))],
                    ['title' => 'Ящик с карандашами', 'ids' => array_merge(range(95, 104), range(65, 68))],
                    ['title' => 'Маркеры для доски', 'ids' => array_merge(range(105, 114), range(61, 64))],
                    ['title' => 'Вероятность события в опыте', 'ids' => array_merge(range(115, 124), range(73, 76))],
                    ['title' => 'Диаграмма Эйлера', 'ids' => range(125, 164)],
                    ['title' => 'Дерево случайного опыта', 'ids' => range(165, 174)],
                ];

                $groups = [];
                foreach ($spec as $groupSpec) {
                    $groupTasks = [];
                    foreach ($groupSpec['ids'] as $id) {
                        $task = $allTasksIndexed[$id] ?? null;
                        if (!$task) continue;
                        $text = trim((string) ($task['text'] ?? ''));
                        if ($text === '') continue;

                        $groupTasks[] = [
                            'id' => $task['id'] ?? null,
                            'src_key' => $task['src_key'] ?? null,
                            'text' => $text,
                            'svg' => $task['svg'] ?? null,
                            'image' => $task['image'] ?? null,
                            'answer' => $task['answer'] ?? null,
                        ];
                    }
                    if (!empty($groupTasks)) {
                        $groups[] = ['title' => $groupSpec['title'], 'tasks' => $groupTasks];
                    }
                }
                $groupedByTopic[$topicId] = $groups;
            } else {
                $groupedByTopic[$topicId] = [];
            }
        }

        return view('pwa.student.new-tasks', [
            'topics' => $topics,
            'selectedTopic' => $selected,
            'newByTopic' => $newByTopic,
            'groupedByTopic' => $groupedByTopic,
            'isPremium' => true, // No billing gate in PWA
            'trialUsed' => true,
        ]);
    }

    /**
     * OGE Part 2 tasks (topics 20-25).
     */
    public function part2(Request $request)
    {
        $topicsMeta = [
            '20' => ['title' => 'Уравнения', 'icon' => '🔢'],
            '21' => ['title' => 'Текстовые задачи', 'icon' => '🚗'],
            '23' => ['title' => 'Геометрия (вычисление)', 'icon' => '📐'],
        ];

        $topics = array_keys($topicsMeta);
        $selected = (string) $request->query('topic', '20');
        if (!in_array($selected, $topics)) {
            $selected = '20';
        }

        $data = $this->taskData->getTopicData($selected);
        $zadaniya = [];

        foreach (($data['blocks'] ?? []) as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text = trim((string) ($t['text'] ?? ''));
                    if ($text === '') continue;
                    $tasks[] = [
                        'id'     => $t['id'] ?? null,
                        'text'   => $text,
                        'image'  => $t['image'] ?? null,
                        'answer' => $t['answer'] ?? null,
                    ];
                }
                if (!empty($tasks)) {
                    $section = trim((string) ($zadanie['section'] ?? ''));
                    $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                    $num = $zadanie['number'] ?? '';
                    $title = $section !== '' ? $section : ($instruction !== '' ? "Задание {$num}. {$instruction}" : "Задание {$num}");
                    $zadaniya[] = ['title' => $title, 'hint' => $zadanie['answer_hint'] ?? null, 'tasks' => $tasks];
                }
            }
        }

        return view('pwa.student.part2', [
            'topicsMeta'    => $topicsMeta,
            'topics'        => $topics,
            'selectedTopic' => $selected,
            'zadaniya'      => $zadaniya,
            'isPremium'     => true, // No billing gate in PWA
            'trialUsed'     => true,  // No trial UI in PWA
        ]);
    }

    /**
     * OGE Part 1 tasks (topics 06-19, production status only).
     */
    public function tasksPart1(Request $request)
    {
        $topicIds = ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'];
        $selected = str_pad((string) $request->query('topic', '6'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topicIds, true)) {
            $selected = '06';
        }

        $blocks = $this->taskData->getBlocks($selected, 'production');

        $zadaniya = [];
        foreach ($blocks as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $zadanieType = $zadanie['type'] ?? '';

                if ($zadanieType === 'statements' && !empty($zadanie['statements'])) {
                    $tasks = [];
                    foreach ($zadanie['statements'] as $s) {
                        $text = trim((string) ($s['text'] ?? ''));
                        if ($text === '') continue;
                        $tasks[] = [
                            'id' => $s['id'] ?? null, 'text' => $text, 'expression' => null,
                            'svg' => $s['svg'] ?? null, 'image' => $s['image'] ?? null,
                            'options' => null, 'question' => null, 'answer' => $s['answer'] ?? null,
                        ];
                    }
                    if (!empty($tasks)) {
                        $section = trim((string) ($zadanie['section'] ?? ''));
                        $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                        $label = trim((string) ($zadanie['label'] ?? ''));
                        $num = $zadanie['number'] ?? '';
                        $title = $label !== '' ? $label : ($section !== '' ? $section : ($instruction !== '' ? "Задание {$num}. {$instruction}" : "Задание {$num}"));
                        $zadaniya[] = ['title' => $title, 'tasks' => $tasks];
                    }
                    continue;
                }

                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text = trim((string) ($t['text'] ?? ''));
                    $expression = trim((string) ($t['expression'] ?? ''));
                    $question = trim((string) ($t['question'] ?? ''));

                    if ($text === '' && $expression === '' && empty($t['svg']) && empty($t['image']) && $question === '') {
                        continue;
                    }

                    $rawOptions = $t['options'] ?? null;
                    if (is_array($rawOptions)) {
                        $rawOptions = array_map(function ($o) {
                            if (is_array($o)) {
                                foreach (['label', 'text', 'value'] as $k) {
                                    if (isset($o[$k])) {
                                        $o[$k] = MiniAppTaskCanonicalizer::latexToUnicode((string) $o[$k]);
                                    }
                                }
                                return $o;
                            }
                            return MiniAppTaskCanonicalizer::latexToUnicode((string) $o);
                        }, $rawOptions);
                    }

                    $tasks[] = [
                        'id'         => $t['id'] ?? null,
                        'text'       => $text,
                        'expression' => $expression !== '' ? $expression : null,
                        'svg'        => $t['svg'] ?? null,
                        'image'      => $t['image'] ?? null,
                        'options'    => $rawOptions,
                        'question'   => $question !== '' ? $question : null,
                        'answer'     => $t['answer'] ?? null,
                    ];
                }
                if (!empty($tasks)) {
                    $section = trim((string) ($zadanie['section'] ?? ''));
                    $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                    $label = trim((string) ($zadanie['label'] ?? ''));
                    $num = $zadanie['number'] ?? '';
                    $title = $label !== '' ? $label : ($section !== '' ? $section : ($instruction !== '' ? "Задание {$num}. {$instruction}" : "Задание {$num}"));
                    $zadaniya[] = ['title' => $title, 'tasks' => $tasks];
                }
            }
        }

        $taskCount = array_sum(array_map(fn($z) => count($z['tasks']), $zadaniya));

        return view('pwa.student.tasks-part1', [
            'topicIds'      => $topicIds,
            'selectedTopic' => $selected,
            'zadaniya'      => $zadaniya,
            'taskCount'     => $taskCount,
            'isPremium'     => true, // No billing gate in PWA
            'trialUsed'     => true,
        ]);
    }

    /**
     * Mini-OGE mode selection page.
     */
    public function mini()
    {
        return view('pwa.student.mini');
    }

    /**
     * Start a mini-OGE test (POST).
     */
    public function startMini(Request $request)
    {
        $request->validate(['mode' => 'required|string|in:geometry,algebra,mixed,part2']);
        $mode = $request->input('mode');
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Сессия истекла. Перезайдите в приложение.'], 401);
        }

        try {
            $variant = $this->poolService->getOrCreateVariant($user, $mode);
        } catch (\Throwable $e) {
            Log::error('PWA Mini start pool error', ['user' => $user->id, 'mode' => $mode, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Нет доступных заданий для этого режима. Попробуйте позже.'], 422);
        }

        try {
            [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);
        } catch (\Throwable $e) {
            Log::error('PWA Mini start attempt error', ['user' => $user->id, 'mode' => $mode, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка создания попытки: ' . mb_substr($e->getMessage(), 0, 100)], 500);
        }

        return response()->json(['redirect' => $this->base() . '/test/' . $attempt->id]);
    }

    /**
     * Start a full variant test (POST).
     */
    public function startFull(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Сессия истекла. Перезайдите в приложение.'], 401);
        }

        $variantHash = trim((string) $request->input('variant_hash', ''));

        if ($variantHash === '') {
            $type = $request->boolean('part2') ? 'full_with_part2' : 'full';

            try {
                $variant = $this->poolService->getOrCreateVariant($user, $type);
                $variantHash = $variant->hash;
            } catch (\RuntimeException $e) {
                Log::warning('PWA Full start pool error', ['user' => $user->id, 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Нет доступных заданий. Попробуйте позже.'], 422);
            }
        }

        try {
            [$variant, $attempt] = $this->attemptService->startAttempt($user, $variantHash);
        } catch (\Throwable $e) {
            Log::error('PWA Full start attempt error', ['user' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка создания попытки: ' . mb_substr($e->getMessage(), 0, 100)], 500);
        }

        return response()->json(['redirect' => $this->base() . '/test/' . $attempt->id]);
    }

    /**
     * Test screen — active attempt.
     */
    public function test(Request $request, int $attemptId)
    {
        $user = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->firstOrFail();

        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect($this->base() . '/results/' . $attempt->id);
        }

        $variant = $attempt->variant;
        $config = $variant->config_json ?? [];

        if (!empty($config['tasks'])) {
            $tasks = $config['tasks'];
        } else {
            $builtVariant = $this->variantBuilder->build($variant->hash);
            $tasks = $builtVariant['tasks'] ?? [];
        }

        $tasks = array_map(fn ($task) => is_array($task) ? $this->taskCanonicalizer->normalizeForUi($task) : $task, $tasks);
        $tasks = array_map(fn ($t) => is_array($t) ? $this->taskSanitizer->sanitize($t) : $t, $tasks);
        $tasks = $this->normalizeAttemptTasksForMiniApp($variant, $tasks);

        if (is_array($tasks)) {
            usort($tasks, fn($a, $b) => ((int)($a['display_task_number'] ?? 0)) <=> ((int)($b['display_task_number'] ?? 0)));
        }

        $existingAnswers = $this->resolveExistingAttemptAnswersForMiniApp($attempt, $tasks);

        $mode = $variant->mode ?? 'full';
        $title = $variant->title ?? 'Вариант ОГЭ';

        return view('pwa.student.test', [
            'attempt' => $attempt,
            'tasks' => $tasks,
            'answers' => $existingAnswers,
            'mode' => $mode,
            'title' => $title,
            'battleMode' => false,
        ]);
    }

    private function normalizeAttemptTasksForMiniApp(OgeVariant $variant, array $tasks): array
    {
        foreach ($tasks as $index => $task) {
            if (!is_array($task)) continue;
            $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
            $task['display_task_number'] = $numbers['exam_number'];
            $task['attempt_task_number'] = $numbers['slot'];
            $task['slot'] = $numbers['slot'];
            $task['exam_number'] = $numbers['exam_number'];
            $tasks[$index] = $task;
        }
        return $tasks;
    }

    private function resolveExistingAttemptAnswersForMiniApp(OgeAttempt $attempt, array $tasks): array
    {
        $existingAnswers = $attempt->answers()->pluck('current_answer', 'task_number')->toArray();
        if (empty($existingAnswers)) return [];

        $variant = $attempt->variant;
        $mapped = [];
        foreach ($tasks as $index => $task) {
            if (!is_array($task)) continue;
            $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
            $slot = $numbers['slot'];
            $examNumber = $numbers['exam_number'];

            if (array_key_exists($slot, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$slot];
                continue;
            }
            if (array_key_exists($examNumber, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$examNumber];
            }
        }
        return $mapped;
    }

    /**
     * Results screen — submitted attempt.
     */
    public function results(int $attemptId)
    {
        $user = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->firstOrFail();

        if (!in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect($this->base() . '/test/' . $attempt->id);
        }

        $scorings = OgeAttemptScoring::where('attempt_id', $attempt->id)->orderBy('task_number')->get();
        $answers = $attempt->answers()->get();

        $attempt->loadMissing('variant');
        $configTaskCount = count($attempt->variant?->config_json['tasks'] ?? []);
        $totalTasks = $configTaskCount > 0 ? $configTaskCount : $scorings->count();
        $correctCount = $scorings->where('is_correct', true)->count();

        $totalTime = 0;
        if ($attempt->started_at && $attempt->submitted_at) {
            $totalTime = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        return view('pwa.student.results', compact(
            'attempt', 'scorings', 'answers', 'totalTasks', 'correctCount', 'totalTime'
        ));
    }

    /**
     * Student attempt history.
     */
    public function history()
    {
        $user = Auth::user();

        $attempts = OgeAttempt::where('student_id', $user->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with(['variant:id,hash,title,mode,config_json', 'scorings:id,attempt_id,is_correct'])
            ->orderByDesc('submitted_at')
            ->limit(50)
            ->get();

        $list = [];
        foreach ($attempts as $att) {
            $correct = $att->scorings->where('is_correct', true)->count();
            $configTaskCount = count($att->variant?->config_json['tasks'] ?? []);
            $total = $configTaskCount > 0 ? $configTaskCount : $att->scorings->count();
            $time = null;
            if ($att->started_at && $att->submitted_at) {
                $time = $att->submitted_at->diffInSeconds($att->started_at);
            }
            $list[] = ['id' => $att->id, 'label' => $this->variantModeLabel($att->variant), 'correct' => $correct, 'total' => $total, 'time' => $time, 'date' => $att->submitted_at];
        }

        return view('pwa.student.history', compact('user', 'list'));
    }

    /**
     * Student attempt history — detail view.
     */
    public function historyDetail(Request $request, int $attemptId)
    {
        $user = Auth::user();

        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with(['variant:id,hash,title,mode,config_json', 'answers:id,attempt_id,task_number,current_answer', 'scorings:id,attempt_id,task_number,is_correct,correct_answer'])
            ->firstOrFail();

        $correct = $attempt->scorings->where('is_correct', true)->count();
        $configTaskCount = count($attempt->variant?->config_json['tasks'] ?? []);
        $total = $configTaskCount > 0 ? $configTaskCount : $attempt->scorings->count();
        $time = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $time = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        $taskMap = [];
        $displayNumMap = [];
        $cfg = $attempt->variant?->config_json;
        if ($attempt->variant && is_array($cfg) && isset($cfg['tasks']) && is_array($cfg['tasks'])) {
            $resolved = VariantTaskNumberResolver::resolveAll($cfg['tasks'], $attempt->variant);
            foreach ($resolved as $entry) {
                $taskMap[$entry['slot']] = $entry['task'];
                if ($entry['exam_number'] !== $entry['slot']) {
                    $displayNumMap[$entry['slot']] = $entry['exam_number'];
                }
            }
        }

        $wrongTasks = [];
        foreach ($attempt->scorings as $scoring) {
            if ($scoring->is_correct !== false && (int) $scoring->is_correct !== 0) continue;

            $taskNum = (int) $scoring->task_number;
            $studentAnswer = $attempt->answers->firstWhere('task_number', $taskNum);
            $def = $taskMap[$taskNum] ?? [];
            $inner = is_array($def['task'] ?? null) ? $def['task'] : [];

            $instructionText = trim((string) (($def['instruction'] ?? $inner['instruction'] ?? '') ?: ''));
            $conditionText = trim((string) (($inner['text'] ?? $def['text'] ?? $inner['prompt'] ?? $inner['question'] ?? $inner['condition'] ?? $inner['body'] ?? $inner['content'] ?? '') ?: ''));

            if ($instructionText !== '' && $conditionText !== '') {
                $normI = preg_replace('/\s+/u', ' ', mb_strtolower($instructionText));
                $normC = preg_replace('/\s+/u', ' ', mb_strtolower($conditionText));
                if ($normI === $normC) $instructionText = '';
            }

            $taskText = $conditionText !== '' ? $conditionText : $instructionText;
            $taskExpression = (string) (($def['expression'] ?? $inner['expression'] ?? '') ?: '');

            $rawOptions = $def['options'] ?? $inner['options'] ?? null;
            $taskOptions = is_array($rawOptions) ? array_values($rawOptions) : [];

            $wrongTasks[] = [
                'task_number' => $displayNumMap[$taskNum] ?? $taskNum,
                'task_instruction' => $instructionText,
                'task_text' => $taskText,
                'task_expression' => $taskExpression,
                'task_svg' => (string) (($def['svg'] ?? $inner['svg'] ?? '') ?: ''),
                'task_image' => (string) (($def['image'] ?? $inner['image'] ?? '') ?: ''),
                'task_options' => $taskOptions,
                'student_answer' => (string) (($studentAnswer->current_answer ?? '') ?: '—'),
                'correct_answer' => (string) (($scoring->correct_answer ?? '') ?: '—'),
            ];
        }

        usort($wrongTasks, fn($a, $b) => $a['task_number'] <=> $b['task_number']);

        $label = $this->variantModeLabel($attempt->variant);

        return view('pwa.student.history-detail', compact('user', 'attempt', 'label', 'correct', 'total', 'time', 'wrongTasks'));
    }

    /**
     * Tutor page.
     */
    public function tutor()
    {
        $user = Auth::user();

        $lastAttempt = OgeAttempt::where('student_id', $user->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->orderByDesc('submitted_at')
            ->first();

        $lastCorrect = 0;
        $lastTotal = 0;
        $weakTopics = $this->computeWeakTopics($user->id);

        if ($lastAttempt) {
            $scorings = OgeAttemptScoring::where('attempt_id', $lastAttempt->id)->get();
            $lastTotal = $scorings->count();
            $lastCorrect = $scorings->where('is_correct', true)->count();
        }

        return view('pwa.student.tutor', compact('lastCorrect', 'lastTotal', 'weakTopics'));
    }

    /**
     * Student homework page.
     */
    public function studentHomework(Request $request)
    {
        $user = $request->user();

        $assignments = \App\Models\HomeworkAssignment::where('student_id', $user->id)
            ->with('homework')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $list = [];
        foreach ($assignments as $a) {
            $hw = $a->homework;
            if (!$hw) continue;
            $list[] = [
                'id' => $a->id,
                'homework_id' => $hw->id,
                'type' => $hw->homework_type,
                'title' => $hw->title,
                'topic_number' => $hw->topic_number,
                'variant_hash' => $hw->variant_hash,
                'status' => $a->status,
                'assigned_at' => $hw->assigned_at,
                'completed_at' => $a->completed_at,
            ];
        }

        return view('pwa.student.student-homework', compact('user', 'list'));
    }

    /**
     * Profile page.
     */
    public function profile()
    {
        $user = Auth::user();

        $transactions = StarTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $referralCount = User::where('referred_by_user_id', $user->id)->count();

        return view('pwa.student.profile', [
            'user'          => $user,
            'transactions'  => $transactions,
            'referralCount' => $referralCount,
            'isPremium'     => true,
            'trialUsed'     => true,
        ]);
    }

    protected function countNewFipiTasks(): int
    {
        $topicIds = ['09', '10', '15', '16', '17'];
        $count = 0;

        foreach ($topicIds as $topicId) {
            $blocks = $this->taskData->getBlocks($topicId);
            foreach ($blocks as $block) {
                foreach (($block['zadaniya'] ?? []) as $zadanie) {
                    $zadanieIsNew = (bool) ($zadanie['is_new'] ?? false);
                    foreach (($zadanie['tasks'] ?? []) as $task) {
                        if ($zadanieIsNew || (bool) ($task['is_new'] ?? false)) {
                            $count++;
                        }
                    }
                }
            }
        }

        return $count;
    }

    protected function computeWeakTopics(int $userId): array
    {
        $topicNames = [
            6 => 'Дроби', 7 => 'Числа', 8 => 'Корни', 9 => 'Уравнения',
            10 => 'Вероятность', 11 => 'Графики', 12 => 'Формулы', 13 => 'Неравенства',
            14 => 'Прогрессии', 15 => 'Треугольники', 16 => 'Окружность',
            17 => 'Четырёхугольники', 18 => 'Клетчатая бумага', 19 => 'Высказывания',
        ];

        $stats = DB::table('oge_attempt_scorings as s')
            ->join('oge_attempts as a', 'a.id', '=', 's.attempt_id')
            ->where('a.student_id', $userId)
            ->whereIn('a.status', ['submitted', 'scored'])
            ->select(
                's.task_number',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN s.is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('s.task_number')
            ->get();

        if ($stats->isEmpty()) return [];

        return $stats->map(function ($row) use ($topicNames) {
            $pct = $row->total > 0 ? round(($row->correct / $row->total) * 100) : 0;
            return [
                'task_number' => $row->task_number,
                'name' => $topicNames[$row->task_number] ?? "Задание {$row->task_number}",
                'pct' => $pct,
                'total' => $row->total,
                'correct' => $row->correct,
            ];
        })->sortBy('pct')->take(4)->values()->toArray();
    }
}
