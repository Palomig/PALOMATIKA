<?php

namespace App\Http\Controllers;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\StarTransaction;
use App\Models\TeacherStudent;
use App\Models\UserGift;
use App\Models\User;
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

class MiniAppStudentController extends Controller
{
    use Traits\MiniAppHelpers;

    public function __construct(
        private readonly MiniVariantService $miniVariant,
        private readonly OgeAttemptService $attemptService,
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly OgeVariantPoolService $poolService,
        private readonly TaskDataService $taskData,
        private readonly MiniAppTaskCanonicalizer $taskCanonicalizer,
        private readonly MiniAppTaskSanitizer $taskSanitizer,
    ) {
    }

    /**
     * Dashboard — main hub after login.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $effectiveRole = $this->resolveMiniAppRole($request, $user);

        if ($effectiveRole === 'teacher') {
            return redirect('/tg/teacher/dashboard');
        }

        // Новые ученики проходят диагностику перед дашбордом
        $diagnosticService = app(\App\Services\DiagnosticService::class);
        if (!$diagnosticService->hasCompletedDiagnostic($user->id)) {
            return redirect()->route('miniapp.diagnostic');
        }

        // If Mini App was opened via startapp=oge_variant_hash_..., jump directly to test.
        $startParam = trim((string) $request->query('startapp', ''));
        if ($startParam === '') {
            $startParam = trim((string) $request->query('tgWebAppStartParam', ''));
        }
        if ($startParam === '') {
            $startParam = trim((string) $request->session()->get('telegram_start_param', ''));
        }

        if ($startParam !== '' && preg_match('/^oge_variant_hash_([a-z0-9]{8,32})$/i', $startParam, $m)) {
            $hash = strtolower($m[1]);
            $variant = OgeVariant::whereRaw('LOWER(hash) = ?', [$hash])->first();
            if ($variant) {
                [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);
                $request->session()->forget('telegram_start_param');
                return redirect('/tg/test/' . $attempt->id . '?battle=1');
            }
        }

        // Weak topics (from all submitted/scored attempts)
        $weakTopics = $this->computeWeakTopics($user->id);

        $newFipiCount = $this->countNewFipiTasks();

        // Find all unfinished attempts for resume banner
        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
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

        // Check for pending gift
        $pendingGift = UserGift::where('user_id', $user->id)
            ->whereNull('shown_at')
            ->orderBy('id')
            ->first();

        return view('miniapp.dashboard', compact(
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
                if ($text === '') {
                    continue;
                }

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

            // Topic 10: grouped spoiler view with explicit task id mapping
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
                        if (!$task) {
                            continue;
                        }
                        $text = trim((string) ($task['text'] ?? ''));
                        if ($text === '') {
                            continue;
                        }

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
                        $groups[] = [
                            'title' => $groupSpec['title'],
                            'tasks' => $groupTasks,
                        ];
                    }
                }
                $groupedByTopic[$topicId] = $groups;
            } else {
                $groupedByTopic[$topicId] = [];
            }
        }

        return view('miniapp.new-tasks', [
            'topics' => $topics,
            'selectedTopic' => $selected,
            'newByTopic' => $newByTopic,
            'groupedByTopic' => $groupedByTopic,
            'isPremium'     => Auth::user()->hasTgPremium(),
            'trialUsed'     => (bool) Auth::user()->tg_trial_used,
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
            // '22' => ['title' => 'Графики', 'icon' => '📈'],
            '23' => ['title' => 'Геометрия (вычисление)', 'icon' => '📐'],
            // '24' => ['title' => 'Геометрия (доказательство)', 'icon' => '✏️'],
            // '25' => ['title' => 'Геометрия (повышенная)', 'icon' => '🔺'],
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
                    $zadaniya[] = [
                        'title' => $title,
                        'hint'  => $zadanie['answer_hint'] ?? null,
                        'tasks' => $tasks,
                    ];
                }
            }
        }

        return view('miniapp.part2', [
            'topicsMeta'    => $topicsMeta,
            'topics'        => $topics,
            'selectedTopic' => $selected,
            'zadaniya'      => $zadaniya,
            'isPremium'     => Auth::user()->hasTgPremium(),
            'trialUsed'     => (bool) Auth::user()->tg_trial_used,
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

                // Handle statement-type zadaniya (topic 19)
                if ($zadanieType === 'statements' && !empty($zadanie['statements'])) {
                    $tasks = [];
                    foreach ($zadanie['statements'] as $s) {
                        $text = trim((string) ($s['text'] ?? ''));
                        if ($text === '') continue;
                        $tasks[] = [
                            'id'         => $s['id'] ?? null,
                            'text'       => $text,
                            'expression' => null,
                            'svg'        => $s['svg'] ?? null,
                            'image'      => $s['image'] ?? null,
                            'options'    => null,
                            'question'   => null,
                            'answer'     => $s['answer'] ?? null,
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

                // Handle regular tasks
                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text = trim((string) ($t['text'] ?? ''));
                    $expression = trim((string) ($t['expression'] ?? ''));
                    $question = trim((string) ($t['question'] ?? ''));

                    // Skip tasks that have no displayable content at all
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

                    $graphOptions = $t['graph_options'] ?? null;
                    if (is_array($graphOptions)) {
                        $graphOptions = array_map(function ($option) {
                            if (!is_array($option)) {
                                return $option;
                            }

                            if (isset($option['text'])) {
                                $option['text'] = MiniAppTaskCanonicalizer::latexToUnicode((string) $option['text']);
                            }

                            return $option;
                        }, $graphOptions);
                    }

                    $tasks[] = [
                        'id'                 => $t['id'] ?? null,
                        'text'               => $text,
                        'expression'         => $expression !== '' ? $expression : null,
                        'svg'                => $t['svg'] ?? null,
                        'image'              => $t['image'] ?? null,
                        'options'            => $rawOptions,
                        'graph_options'      => $graphOptions,
                        'graph_options_mode' => $t['graph_options_mode'] ?? null,
                        'question'           => $question !== '' ? $question : null,
                        'answer'             => $t['answer'] ?? null,
                    ];
                }
                if (!empty($tasks)) {
                    $section = trim((string) ($zadanie['section'] ?? ''));
                    $instruction = trim((string) ($zadanie['instruction'] ?? ''));
                    $label = trim((string) ($zadanie['label'] ?? ''));
                    $num = $zadanie['number'] ?? '';
                    $title = $label !== '' ? $label : ($section !== '' ? $section : ($instruction !== '' ? "Задание {$num}. {$instruction}" : "Задание {$num}"));
                    $zadaniya[] = [
                        'title' => $title,
                        'tasks' => $tasks,
                    ];
                }
            }
        }

        $taskCount = array_sum(array_map(fn($z) => count($z['tasks']), $zadaniya));

        return view('miniapp.tasks-part1', [
            'topicIds'      => $topicIds,
            'selectedTopic' => $selected,
            'zadaniya'      => $zadaniya,
            'taskCount'     => $taskCount,
            'isPremium'     => Auth::user()->hasTgPremium(),
            'trialUsed'     => (bool) Auth::user()->tg_trial_used,
        ]);
    }

    /**
     * Mini-OGE mode selection page.
     */
    public function mini()
    {
        return view('miniapp.mini');
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
            \Log::error('Mini start pool error', [
                'user' => $user->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5),
            ]);
            return response()->json([
                'error' => 'Нет доступных заданий для этого режима. Попробуйте позже.',
            ], 422);
        }

        try {
            [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);
        } catch (\Throwable $e) {
            \Log::error('Mini start attempt error', ['user' => $user->id, 'mode' => $mode, 'hash' => $variant->hash, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Ошибка создания попытки: ' . mb_substr($e->getMessage(), 0, 100),
            ], 500);
        }

        return response()->json([
            'redirect' => '/tg/test/' . $attempt->id,
        ]);
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

        $type = $request->boolean('part2') ? 'full_with_part2' : 'full';

        try {
            $variant = $this->poolService->getOrCreateVariant($user, $type);
        } catch (\RuntimeException $e) {
            \Log::warning('Full start pool error', ['user' => $user->id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Нет доступных заданий. Попробуйте позже.',
            ], 422);
        }

        try {
            [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);
        } catch (\Throwable $e) {
            \Log::error('Full start attempt error', ['user' => $user->id, 'hash' => $variant->hash, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Ошибка создания попытки: ' . mb_substr($e->getMessage(), 0, 100),
            ], 500);
        }

        return response()->json([
            'redirect' => '/tg/test/' . $attempt->id,
        ]);
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

        // If attempt is already finalized, redirect to results
        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect('/tg/results/' . $attempt->id);
        }

        $variant = $attempt->variant;
        $config = $variant->config_json ?? [];

        // Get tasks from config (mini app variants) or build from hash
        if (!empty($config['tasks'])) {
            $tasks = $config['tasks'];
        } else {
            $builtVariant = $this->variantBuilder->build($variant->hash);
            $tasks = $builtVariant['tasks'] ?? [];
        }

        // Normalize task payload for mini-app UI (works for both old and new variants)
        $tasks = array_map(function ($task) {
            if (!is_array($task)) {
                return $task;
            }

            return $this->taskCanonicalizer->normalizeForUi($task);
        }, $tasks);

        if (config('features.shuffle_options', false)) {
            $tasks = array_map(fn ($t) => is_array($t) ? $this->shuffleTaskOptionsForAttempt($t, (int) $attempt->id) : $t, $tasks);
        }

        // Sanitize tasks once on server before rendering client payload.
        $tasks = array_map(fn ($t) => is_array($t) ? $this->taskSanitizer->sanitize($t) : $t, $tasks);

        $tasks = $this->normalizeAttemptTasksForMiniApp($variant, $tasks);

        // Sort by display_task_number (exam number) after normalization has set it.
        if (is_array($tasks)) {
            usort($tasks, function ($a, $b) {
                $an = (int) ($a['display_task_number'] ?? 0);
                $bn = (int) ($b['display_task_number'] ?? 0);
                return $an <=> $bn;
            });
        }

        // Get existing answers for this attempt
        $existingAnswers = $this->resolveExistingAttemptAnswersForMiniApp($attempt, $tasks);

        $mode = $variant->mode ?? 'full';
        $title = $variant->title ?? 'Вариант ОГЭ';

        return view('miniapp.test', [
            'attempt' => $attempt,
            'tasks' => $tasks,
            'answers' => $existingAnswers,
            'mode' => $mode,
            'title' => $title,
            'battleMode' => $request->boolean('battle'),
        ]);
    }

    /**
     * Assign two non-overlapping numbering fields to every task:
     *
     *  - display_task_number — the exam number shown in the UI (e.g. 6, 8, 12).
     *  - attempt_task_number — a stable 1..N key used to store/match answers
     *    on both client (JS `taskKey()`) and server (scoring). For mini modes
     *    this is always sequential; for full variants it equals task_number.
     *
     * @param array<int, array<string, mixed>> $tasks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAttemptTasksForMiniApp(OgeVariant $variant, array $tasks): array
    {
        foreach ($tasks as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
            $task['display_task_number'] = $numbers['exam_number'];
            $task['attempt_task_number'] = $numbers['slot'];
            $task['slot'] = $numbers['slot'];
            $task['exam_number'] = $numbers['exam_number'];
            $tasks[$index] = $task;
        }

        return $tasks;
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     * @return array<int, string>
     */
    private function resolveExistingAttemptAnswersForMiniApp(OgeAttempt $attempt, array $tasks): array
    {
        $existingAnswers = $attempt->answers()
            ->pluck('current_answer', 'task_number')
            ->toArray();

        if (empty($existingAnswers)) {
            return [];
        }

        $variant = $attempt->variant;
        $mapped = [];
        foreach ($tasks as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            $numbers = VariantTaskNumberResolver::resolve($task, $index, $variant);
            $slot = $numbers['slot'];
            $examNumber = $numbers['exam_number'];

            if (array_key_exists($slot, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$slot];
                continue;
            }

            // Legacy: answers may have been stored under exam_number
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
            return redirect('/tg/test/' . $attempt->id);
        }

        $scorings = OgeAttemptScoring::where('attempt_id', $attempt->id)
            ->orderBy('task_number')
            ->get();

        $answers = $attempt->answers()->get();

        $attempt->loadMissing('variant');
        $configTaskCount = count($attempt->variant?->config_json['tasks'] ?? []);
        $totalTasks = $configTaskCount > 0 ? $configTaskCount : $scorings->count();
        $correctCount = $scorings->where('is_correct', true)->count();

        $totalTime = 0;
        if ($attempt->started_at && $attempt->submitted_at) {
            $totalTime = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        // Battle leaderboard: only for variants created via bot /battle command.
        $leaderboard = [];
        $isBattleVariant = !empty($attempt->variant?->config_json['is_battle']);
        if ($isBattleVariant && $attempt->variant_id) {
            $battleAttempts = OgeAttempt::where('variant_id', $attempt->variant_id)
                ->whereIn('status', ['submitted', 'scored'])
                ->with(['student:id,name', 'scorings'])
                ->get();

            foreach ($battleAttempts as $a) {
                $aTotal = $a->scorings->count();
                $aCorrect = $a->scorings->where('is_correct', true)->count();
                $aTime = 0;
                if ($a->started_at && $a->submitted_at) {
                    $aTime = $a->submitted_at->diffInSeconds($a->started_at);
                }

                $leaderboard[] = [
                    'attempt_id' => (int) $a->id,
                    'student_name' => (string) ($a->student->name ?? 'Участник'),
                    'correct' => $aCorrect,
                    'total' => $aTotal,
                    'time_sec' => $aTime,
                    'is_me' => (int) $a->student_id === (int) $user->id,
                ];
            }

            usort($leaderboard, function ($x, $y) {
                // 1) more correct is better
                if ($x['correct'] !== $y['correct']) {
                    return $y['correct'] <=> $x['correct'];
                }
                // 2) less time is better (non-zero preferred over zero)
                if ($x['time_sec'] !== $y['time_sec']) {
                    if ($x['time_sec'] === 0) return 1;
                    if ($y['time_sec'] === 0) return -1;
                    return $x['time_sec'] <=> $y['time_sec'];
                }
                // 3) stable fallback
                return $x['attempt_id'] <=> $y['attempt_id'];
            });

            foreach ($leaderboard as $idx => &$row) {
                $row['rank'] = $idx + 1;
            }
            unset($row);
        }

        return view('miniapp.results', compact(
            'attempt', 'scorings', 'answers', 'totalTasks', 'correctCount', 'totalTime', 'leaderboard', 'isBattleVariant'
        ));
    }

    /**
     * Student attempt history — list of all completed attempts.
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

            $list[] = [
                'id' => $att->id,
                'label' => $this->variantModeLabel($att->variant),
                'correct' => $correct,
                'total' => $total,
                'time' => $time,
                'date' => $att->submitted_at,
            ];
        }

        return view('miniapp.history', compact('user', 'list'));
    }

    /**
     * Student attempt history — detail view showing errors for a specific attempt.
     */
    public function historyDetail(Request $request, int $attemptId)
    {
        $user = Auth::user();

        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $user->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with([
                'variant:id,hash,title,mode,config_json',
                'answers:id,attempt_id,task_number,current_answer',
                'scorings:id,attempt_id,task_number,is_correct,correct_answer',
            ])
            ->firstOrFail();

        $correct = $attempt->scorings->where('is_correct', true)->count();
        $configTaskCount = count($attempt->variant?->config_json['tasks'] ?? []);
        $total = $configTaskCount > 0 ? $configTaskCount : $attempt->scorings->count();
        $time = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $time = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        // Build task map from variant config.
        // Mini modes use sequential attempt_task_number (1..N) for scoring,
        // while config_json stores the original topic-based task_number.
        $taskMap = [];
        $displayNumMap = []; // slot → exam_number for display
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

        // Build wrong tasks list (reusing teacher profile logic)
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
            $taskExpression = (string) (($def['expression'] ?? $inner['expression'] ?? $inner['formula'] ?? $inner['latex'] ?? '') ?: '');

            $rawOptions = $def['options'] ?? $inner['options'] ?? $def['variants'] ?? $inner['variants'] ?? null;
            $taskOptions = [];
            if (is_array($rawOptions)) {
                $taskOptions = array_values($rawOptions);
            } elseif (is_string($rawOptions) && trim($rawOptions) !== '') {
                $taskOptions = array_values(array_filter(array_map('trim', preg_split('/\R+/', $rawOptions))));
            }

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

        // Add unanswered tasks as errors (student skipped them).
        $scoredTaskNumbers = $attempt->scorings->pluck('task_number')->map(fn ($n) => (int) $n)->all();
        foreach ($taskMap as $num => $taskDef) {
            if (in_array($num, $scoredTaskNumbers, true)) continue;
            $inner = is_array($taskDef['task'] ?? null) ? $taskDef['task'] : [];

            $instructionText = trim((string) (($taskDef['instruction'] ?? $inner['instruction'] ?? '') ?: ''));
            $conditionText = trim((string) (($inner['text'] ?? $taskDef['text'] ?? $inner['prompt'] ?? $inner['question'] ?? $inner['condition'] ?? '') ?: ''));
            $taskText = $conditionText !== '' ? $conditionText : $instructionText;

            $wrongTasks[] = [
                'task_number' => $displayNumMap[$num] ?? $num,
                'task_instruction' => ($taskText !== $instructionText) ? $instructionText : '',
                'task_text' => $taskText,
                'task_expression' => (string) (($taskDef['expression'] ?? $inner['expression'] ?? '') ?: ''),
                'task_svg' => (string) (($taskDef['svg'] ?? $inner['svg'] ?? '') ?: ''),
                'task_image' => (string) (($taskDef['image'] ?? $inner['image'] ?? '') ?: ''),
                'task_options' => [],
                'student_answer' => '—',
                'correct_answer' => (string) (($taskDef['correct_answer'] ?? $inner['answer'] ?? '') ?: '—'),
            ];
        }

        usort($wrongTasks, fn($a, $b) => $a['task_number'] <=> $b['task_number']);

        $label = $this->variantModeLabel($attempt->variant);

        return view('miniapp.history-detail', compact('user', 'attempt', 'label', 'correct', 'total', 'time', 'wrongTasks'));
    }

    /**
     * Tutor page.
     */
    public function tutor()
    {
        $user = Auth::user();

        // Get last result for context
        $lastAttempt = OgeAttempt::where('student_id', $user->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->orderByDesc('submitted_at')
            ->first();

        $lastCorrect = 0;
        $lastTotal = 0;
        $weakTopics = [];

        if ($lastAttempt) {
            $scorings = OgeAttemptScoring::where('attempt_id', $lastAttempt->id)->get();
            $lastTotal = $scorings->count();
            $lastCorrect = $scorings->where('is_correct', true)->count();
        }

        $weakTopics = $this->computeWeakTopics($user->id);

        return view('miniapp.tutor', compact('lastCorrect', 'lastTotal', 'weakTopics'));
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

    /**
     * Compute weak topics from all submitted/scored attempts.
     */
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

        if ($stats->isEmpty()) {
            return [];
        }

        $topics = $stats->map(function ($row) use ($topicNames) {
            $pct = $row->total > 0 ? round(($row->correct / $row->total) * 100) : 0;
            return [
                'task_number' => $row->task_number,
                'name' => $topicNames[$row->task_number] ?? "Задание {$row->task_number}",
                'pct' => $pct,
                'total' => $row->total,
                'correct' => $row->correct,
            ];
        })->sortBy('pct')->take(4)->values()->toArray();

        return $topics;
    }

    private function shuffleTaskOptionsForAttempt(array $task, int $attemptId): array
    {
        $options = $task['options'] ?? null;
        if (!is_array($options) || count($options) < 2) {
            return $task;
        }

        $type = (string) ($task['type'] ?? '');
        if (in_array($type, ['matching', 'matching_signs', 'matching_4', 'statements'], true)) {
            return $task;
        }

        // Deterministic shuffle per attempt/task, so UI remains stable on refresh.
        $seed = crc32($attemptId . ':' . (string) ($task['task_number'] ?? 0));
        mt_srand($seed);
        $indexes = range(0, count($options) - 1);
        for ($i = count($indexes) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$indexes[$i], $indexes[$j]] = [$indexes[$j], $indexes[$i]];
        }
        mt_srand();

        $shuffled = [];
        foreach ($indexes as $idx) {
            $shuffled[] = $options[$idx];
        }

        $task['options'] = $shuffled;

        return $task;
    }

    /**
     * Student homework page — list assigned homework.
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

        return view('miniapp.student-homework', compact('user', 'list'));
    }

    public function profile()
    {
        $user = Auth::user();

        $transactions = StarTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $referralCount = User::where('referred_by_user_id', $user->id)->count();
        $pendingPayout = StarTransaction::where('user_id', $user->id)
            ->where('type', 'payout')
            ->where('status', 'pending')
            ->exists();

        return view('miniapp.profile', [
            'user' => $user,
            'isPremium' => $user->hasTgPremium(),
            'trialUsed' => (bool) $user->tg_trial_used,
            'transactions' => $transactions,
            'referralCount' => $referralCount,
            'pendingPayout' => $pendingPayout,
        ]);
    }

    public function diagnostic(Request $request, \App\Services\DiagnosticService $diagnosticService)
    {
        $user = Auth::user();

        if ($diagnosticService->hasCompletedDiagnostic($user->id)) {
            return redirect()->route('miniapp.diagnostic.results');
        }

        $questions = $diagnosticService->generateQuestions();
        $request->session()->put('diagnostic_questions', $questions);

        return view('miniapp.diagnostic', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
        ]);
    }

    public function submitDiagnostic(Request $request, \App\Services\DiagnosticService $diagnosticService)
    {
        $user = Auth::user();
        $questions = $request->session()->pull('diagnostic_questions', []);

        if (empty($questions)) {
            return redirect()->route('miniapp.diagnostic');
        }

        $answers = $request->input('answers', []);
        $results = [];

        foreach ($questions as $i => $q) {
            $userAnswer = trim($answers[$i] ?? '');
            $correctAnswer = trim($q['correct_answer'] ?? '');

            $normalizedUser = $this->normalizeDiagnosticAnswer($userAnswer);
            $normalizedCorrect = $this->normalizeDiagnosticAnswer($correctAnswer);

            $results[] = [
                'skill_id' => $q['skill_id'],
                'is_correct' => $normalizedUser === $normalizedCorrect,
            ];
        }

        $diagnosticService->processResults($user->id, $results);

        return redirect()->route('miniapp.diagnostic.results');
    }

    public function diagnosticResults(\App\Services\DiagnosticService $diagnosticService)
    {
        $user = Auth::user();

        if (!$diagnosticService->hasCompletedDiagnostic($user->id)) {
            return redirect()->route('miniapp.diagnostic');
        }

        $userSkills = $user->skills()
            ->with('skill.parent')
            ->orderBy('weight', 'asc')
            ->get();

        $byCategory = $userSkills->groupBy(fn ($us) => $us->skill->category ?? $us->skill->parent?->name ?? 'Другое');

        return view('miniapp.diagnostic-results', [
            'byCategory' => $byCategory,
            'weakSkills' => $userSkills->where('weight', '<', 0.5)->values(),
            'strongSkills' => $userSkills->where('weight', '>=', 0.7)->values(),
            'averageWeight' => round($userSkills->avg('weight') ?? 0, 3),
        ]);
    }

    private function normalizeDiagnosticAnswer(string $answer): string
    {
        $answer = mb_strtolower(trim($answer));
        $answer = str_replace(',', '.', $answer);
        if (is_numeric($answer)) {
            $answer = rtrim(rtrim((string) floatval($answer), '0'), '.');
        }
        return $answer;
    }
}
