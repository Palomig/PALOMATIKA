<?php

namespace App\Http\Controllers;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MiniAppTaskCanonicalizer;
use App\Services\MiniAppTaskSanitizer;
use App\Services\MiniVariantService;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Services\TelegramMiniAppAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MiniAppController extends Controller
{
    protected function issueOnboardingToken(int $userId): string
    {
        $token = Str::random(48);
        Cache::put('tg_onb_token:' . $token, ['user_id' => $userId], now()->addMinutes(20));
        return $token;
    }

    public function __construct(
        private readonly MiniVariantService $miniVariant,
        private readonly OgeAttemptService $attemptService,
        private readonly OgeVariantBuilderService $variantBuilder,
        private readonly OgeVariantPoolService $poolService,
        private readonly TaskDataService $taskData,
        private readonly TelegramMiniAppAuthService $tgMiniAuth,
        private readonly MiniAppTaskCanonicalizer $taskCanonicalizer,
        private readonly MiniAppTaskSanitizer $taskSanitizer,
    ) {
    }

    /**
     * Home page — session-first entrypoint for Mini App.
     * If user already has a valid session, skip landing and open target screen immediately.
     */
    public function home(Request $request)
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $startapp = trim((string) ($request->query('startapp', $request->query('tgWebAppStartParam', ''))));

            if (!$user->onboarding_completed_at) {
                return redirect('/tg/onboarding');
            }

            $target = '/tg/dashboard';
            if ($startapp !== '') {
                $target .= '?startapp=' . rawurlencode($startapp);
            }

            return redirect($target);
        }

        return view('miniapp.home');
    }

    /**
     * Server-side Telegram WebApp authentication via form POST.
     * Verifies initData HMAC, logs in user, and redirects (302).
     * This avoids the session cookie loss issue with client-side fetch + JS redirect.
     */
    public function authenticate(Request $request)
    {
        $initData = trim((string) $request->input('initData', ''));

        if ($initData === '') {
            return redirect('/tg')->with('error', 'Нет данных Telegram для входа');
        }

        try {
            [$authFields, $telegramUser] = $this->tgMiniAuth->extractAndVerify($initData);
        } catch (\Throwable $e) {
            Log::warning('tg_auth_verify_failed', [
                'reason' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return redirect('/tg')->with('error', 'Данные Telegram недействительны. Перезапустите mini app.');
        }

        $user = $this->tgMiniAuth->findOrCreateUser($telegramUser);

        // Login with remember token + regenerate session for security
        Auth::login($user, true);
        $request->session()->regenerate();

        // Build redirect target
        $startParam = trim((string) ($authFields['start_param'] ?? $request->input('startParam', '')));
        if ($startParam !== '') {
            $request->session()->put('telegram_start_param', $startParam);
        }

        // Track referral for newly created users
        if ($user->wasRecentlyCreated) {
            $referrerId = null;

            if (preg_match('/^ref_(\d+)$/', $startParam, $refMatch)) {
                // ref_{user_id} — direct internal ID
                $referrerId = (int) $refMatch[1];
            } elseif (preg_match('/^ref_tg_(\d+)$/', $startParam, $refMatch)) {
                // ref_tg_{telegram_id} — lookup by Telegram oauth_id
                $referrer = User::where('oauth_provider', 'telegram')
                    ->where('oauth_id', $refMatch[1])
                    ->first();
                $referrerId = $referrer?->id;
            }

            if ($referrerId && $referrerId !== $user->id && User::where('id', $referrerId)->exists()) {
                $user->update(['referred_by_user_id' => $referrerId]);
            }
        }

        $redirectTo = !$user->onboarding_completed_at ? '/tg/onboarding' : '/tg/dashboard';
        if ($startParam !== '') {
            $redirectTo .= '?startapp=' . rawurlencode($startParam);
        }

        // Use a one-time handoff token to survive Telegram WebView cookie quirks.
        // The auth-bridge page will navigate to /tg/auth/continue?token=... which
        // re-establishes the session if the cookie was lost during the redirect hop.
        $handoffToken = Str::random(40);
        Cache::put('tg_auth_handoff:' . $handoffToken, [
            'user_id' => $user->id,
            'redirect_to' => $redirectTo,
        ], now()->addMinutes(2));

        return response()->view('miniapp.auth-bridge', [
            'redirectTo' => $redirectTo,
            'handoffToken' => $handoffToken,
        ]);
    }
    public function authBridgePing(Request $request)
    {
        return response()->json(['ok' => true]);
    }

    public function authContinue(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $handoff = $token !== '' ? Cache::pull('tg_auth_handoff:' . $token) : null;

        // Primary path: restore session from handoff token (survives WebView cookie loss)
        if (is_array($handoff) && !empty($handoff['user_id'])) {
            $user = User::find((int) $handoff['user_id']);
            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();

                // Render onboarding inline to avoid yet another redirect hop
                if (!$user->onboarding_completed_at) {
                    return view('miniapp.onboarding', [
                        'onboardingToken' => $this->issueOnboardingToken($user->id),
                    ]);
                }

                $target = (string) ($handoff['redirect_to'] ?? '/tg/dashboard');
                return response()->view('miniapp.auth-bridge-final', ['target' => $target]);
            }
        }

        // Fallback: session cookie survived the redirect — use existing auth
        $user = Auth::user();
        if (!$user) {
            return redirect('/tg')->with('error', 'Сессия входа не сохранилась. Попробуйте ещё раз.');
        }

        $target = !$user->onboarding_completed_at ? '/tg/onboarding' : '/tg/dashboard';
        return response()->view('miniapp.auth-bridge-final', ['target' => $target]);
    }

    /**
     * Onboarding form (GET).
     */
    public function onboarding(Request $request)
    {
        $user = $request->user();
        return view('miniapp.onboarding', [
            'onboardingToken' => $user ? $this->issueOnboardingToken($user->id) : null,
        ]);
    }

    /**
     * Save onboarding data (POST).
     */
    public function saveOnboarding(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'grade_num' => 'required|integer|in:9',
            'grade_letter' => 'required|string|in:А,Б,В,Г,Д',
            'school_number' => 'required|string|max:20',
            'city' => 'nullable|string|max:80',
            'onboarding_token' => 'nullable|string|max:128',
        ]);

        $user = $request->user();

        // Fallback: restore auth via onboarding token if session was lost
        if (!$user && !empty($data['onboarding_token'])) {
            $payload = Cache::pull('tg_onb_token:' . $data['onboarding_token']);
            if (is_array($payload) && !empty($payload['user_id'])) {
                $user = User::find((int) $payload['user_id']);
                if ($user) {
                    Auth::login($user, true);
                    $request->session()->regenerate();
                }
            }
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->update([
            'name' => $data['name'],
            'grade_num' => $data['grade_num'],
            'grade_letter' => $data['grade_letter'],
            'school_number' => $data['school_number'],
            'city' => $data['city'] ?: 'Чехов',
            'onboarding_completed_at' => now(),
        ]);

        return response()->json(['success' => true]);
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
                'type' => $isMini ? 'Мини-ОГЭ' : 'Полный вариант',
            ];
        }

        $hasTeacher = TeacherStudent::where('student_id', $user->id)->exists();

        return view('miniapp.dashboard', compact(
            'user', 'weakTopics', 'newFipiCount', 'activeAttemptsList', 'hasTeacher'
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
                        'id'    => $t['id'] ?? null,
                        'text'  => $text,
                        'image' => $t['image'] ?? null,
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

                    $tasks[] = [
                        'id'         => $t['id'] ?? null,
                        'text'       => $text,
                        'expression' => $expression !== '' ? $expression : null,
                        'svg'        => $t['svg'] ?? null,
                        'image'      => $t['image'] ?? null,
                        'options'    => $t['options'] ?? null,
                        'question'   => $question !== '' ? $question : null,
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

        // Keep mini-test tasks ordered by exam number (6, 8, 9, 10, ...)
        if (is_array($tasks)) {
            usort($tasks, function ($a, $b) {
                $an = (int) (($a['task_number'] ?? 0));
                $bn = (int) (($b['task_number'] ?? 0));
                return $an <=> $bn;
            });
        }

        // Get existing answers for this attempt
        $existingAnswers = $attempt->answers()
            ->pluck('current_answer', 'task_number')
            ->toArray();

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

        $totalTasks = $scorings->count();
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
            $total = $att->scorings->count();
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
        $total = $attempt->scorings->count();
        $time = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $time = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        // Build task map from variant config
        $taskMap = [];
        $cfg = $attempt->variant?->config_json;
        if (is_array($cfg) && isset($cfg['tasks']) && is_array($cfg['tasks'])) {
            foreach (array_values($cfg['tasks']) as $idx => $taskDef) {
                if (!is_array($taskDef)) continue;
                $num = (int) ($taskDef['task_number'] ?? $taskDef['attempt_task_number'] ?? $taskDef['test_number'] ?? 0);
                if ($num <= 0) {
                    $num = ($attempt->variant && $attempt->variant->isCustomRandom()) ? ($idx + 1) : (6 + $idx);
                }
                if ($num > 0) $taskMap[$num] = $taskDef;
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
                'task_number' => $taskNum,
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

        return view('miniapp.history-detail', compact('user', 'attempt', 'label', 'correct', 'total', 'time', 'wrongTasks'));
    }

    /**
     * Human-readable label for variant mode.
     */
    private function variantModeLabel(?OgeVariant $variant): string
    {
        if (!$variant) return 'Вариант ОГЭ';

        return match ($variant->mode) {
            OgeVariant::MODE_MINI_ALGEBRA => 'Мини-ОГЭ — алгебра',
            OgeVariant::MODE_MINI_GEOMETRY => 'Мини-ОГЭ — геометрия',
            OgeVariant::MODE_MINI_MIXED => 'Мини-ОГЭ — смешанный',
            OgeVariant::MODE_MINI_PART2 => 'Мини-ОГЭ — 2 часть',
            OgeVariant::MODE_FULL_WITH_PART2 => 'Полный вариант (1+2 часть)',
            OgeVariant::MODE_FULL => 'Полный вариант',
            default => $variant->title ?: 'Вариант ОГЭ',
        };
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

    public function teacherDashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;

        $studentCount = TeacherStudent::query()
            ->where('teacher_id', $teacherId)
            ->count();

        $aliasedCount = TeacherStudent::query()
            ->where('teacher_id', $teacherId)
            ->whereNotNull('student_alias')
            ->where('student_alias', '!=', '')
            ->count();

        $variantsCount = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->count();

        $curatedCount = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->where('is_curated', true)
            ->count();

        $recentVariants = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'hash', 'title', 'mode', 'is_curated', 'created_at']);

        return view('miniapp.teacher-dashboard', [
            'user' => $user,
            'studentCount' => $studentCount,
            'aliasedCount' => $aliasedCount,
            'variantsCount' => $variantsCount,
            'curatedCount' => $curatedCount,
            'recentVariants' => $recentVariants,
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
            'canSwitchMode' => $user->role === 'admin',
        ]);
    }

    public function teacherStudents(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;
        $search = trim((string) $request->query('search', ''));

        // Show all active students who use app/solve variants, with teacher-specific ownership marker.
        // Use scalar subqueries to avoid duplicates when multiple teacher_students rows exist.
        $students = User::query()
            ->where('users.role', 'student')
            ->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('oge_attempts')
                        ->whereColumn('oge_attempts.student_id', 'users.id');
                })->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('teacher_students')
                        ->whereColumn('teacher_students.student_id', 'users.id');
                });
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.avatar',
                'users.last_active_at',
            ])
            ->selectRaw(
                '(SELECT ts.student_alias FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as student_alias',
                [$teacherId]
            )
            ->selectRaw(
                '(SELECT ts.created_at FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id ORDER BY ts.id DESC LIMIT 1) as linked_at',
                [$teacherId]
            )
            ->selectRaw(
                'CASE WHEN EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id) THEN 1 ELSE 0 END as is_mine',
                [$teacherId]
            )
            ->when($search !== '', function ($query) use ($search, $teacherId) {
                $query->where(function ($nested) use ($search, $teacherId) {
                    $nested->where('users.name', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%')
                        ->orWhereRaw(
                            'EXISTS (SELECT 1 FROM teacher_students ts WHERE ts.teacher_id = ? AND ts.student_id = users.id AND ts.student_alias like ?)',
                            [$teacherId, '%' . $search . '%']
                        );
                });
            })
            ->orderByRaw('COALESCE(users.last_active_at, users.created_at) DESC')
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        return view('miniapp.teacher-students', [
            'students' => $students,
            'search' => $search,
            'canSwitchMode' => $user->role === 'admin',
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
        ]);
    }

    public function teacherVariants(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $teacherId = (int) $user->id;

        $variants = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->withCount('attempts')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('miniapp.teacher-variants', [
            'variants' => $variants,
            'canSwitchMode' => $user->role === 'admin',
            'effectiveRole' => $this->resolveMiniAppRole($request, $user),
        ]);
    }

    public function teacherReferrals(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        // Top referrers: users who invited the most people
        $referrers = User::query()
            ->whereHas('referrals')
            ->withCount('referrals')
            ->orderByDesc('referrals_count')
            ->limit(100)
            ->get(['id', 'name', 'role', 'created_at']);

        $totalUsers = User::count();
        $totalReferred = User::whereNotNull('referred_by_user_id')->count();

        // Recent referrals with who invited whom
        $recentReferrals = User::query()
            ->whereNotNull('referred_by_user_id')
            ->with('referrer:id,name,role')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'name', 'role', 'referred_by_user_id', 'created_at']);

        return view('miniapp.teacher-referrals', [
            'referrers' => $referrers,
            'totalUsers' => $totalUsers,
            'totalReferred' => $totalReferred,
            'recentReferrals' => $recentReferrals,
            'canSwitchMode' => $request->user()->role === 'admin',
        ]);
    }

    public function teacherStudentProfile(Request $request, int $studentId)
    {
        /** @var User $teacher */
        $teacher = $request->user();

        $student = User::query()
            ->where('role', 'student')
            ->findOrFail($studentId);

        $attempts = OgeAttempt::query()
            ->where('student_id', $student->id)
            ->with([
                'variant:id,hash,title,mode',
                'scorings:id,attempt_id,task_number,is_correct',
            ])
            // Show full student history in miniapp teacher profile (not only variants created by current teacher).
            ->orderByRaw('COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at) DESC')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $topicStats = [];
        $correctTotal = 0;
        $scoredTotal = 0;

        foreach ($attempts as $attempt) {
            foreach ($attempt->scorings as $scoring) {
                if ($scoring->is_correct === null) {
                    continue;
                }
                $taskNum = (int) $scoring->task_number;
                if (!isset($topicStats[$taskNum])) {
                    $topicStats[$taskNum] = ['task_number' => $taskNum, 'correct' => 0, 'total' => 0];
                }
                $topicStats[$taskNum]['total']++;
                $scoredTotal++;
                if ((bool) $scoring->is_correct) {
                    $topicStats[$taskNum]['correct']++;
                    $correctTotal++;
                }
            }
        }

        usort($topicStats, fn($a, $b) => $a['task_number'] <=> $b['task_number']);

        // Build variant history list (same format as student history page)
        $historyList = [];
        foreach ($attempts as $att) {
            if (!in_array($att->status, ['submitted', 'scored'])) {
                continue;
            }
            $correct = $att->scorings->where('is_correct', true)->count();
            $total = $att->scorings->count();
            $time = null;
            if ($att->started_at && $att->submitted_at) {
                $time = $att->submitted_at->diffInSeconds($att->started_at);
            }
            $historyList[] = [
                'id' => $att->id,
                'label' => $this->variantModeLabel($att->variant),
                'hash' => $att->variant->hash ?? null,
                'correct' => $correct,
                'total' => $total,
                'time' => $time,
                'date' => $att->submitted_at,
            ];
        }

        return view('miniapp.teacher-student-profile', [
            'student' => $student,
            'attempts' => $attempts,
            'topicStats' => $topicStats,
            'correctTotal' => $correctTotal,
            'scoredTotal' => $scoredTotal,
            'accuracy' => $scoredTotal > 0 ? (int) round(($correctTotal / $scoredTotal) * 100) : null,
            'historyList' => $historyList,
            'canSwitchMode' => $teacher->role === 'admin',
            'effectiveRole' => $this->resolveMiniAppRole($request, $teacher),
        ]);
    }

    public function teacherStudentAttemptDetail(Request $request, int $studentId, int $attemptId)
    {
        $student = User::where('role', 'student')->findOrFail($studentId);

        $attempt = OgeAttempt::where('id', $attemptId)
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'scored'])
            ->with([
                'variant:id,hash,title,mode,config_json',
                'answers:id,attempt_id,task_number,current_answer',
                'scorings:id,attempt_id,task_number,is_correct,correct_answer',
            ])
            ->firstOrFail();

        $correct = $attempt->scorings->where('is_correct', true)->count();
        $total = $attempt->scorings->count();
        $time = null;
        if ($attempt->started_at && $attempt->submitted_at) {
            $time = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        $taskMap = [];
        $cfg = $attempt->variant?->config_json;
        if (is_array($cfg) && isset($cfg['tasks']) && is_array($cfg['tasks'])) {
            foreach (array_values($cfg['tasks']) as $idx => $taskDef) {
                if (!is_array($taskDef)) continue;
                $num = (int) ($taskDef['task_number'] ?? $taskDef['attempt_task_number'] ?? $taskDef['test_number'] ?? 0);
                if ($num <= 0) {
                    $num = ($attempt->variant && $attempt->variant->isCustomRandom()) ? ($idx + 1) : (6 + $idx);
                }
                if ($num > 0) $taskMap[$num] = $taskDef;
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
            $taskExpression = (string) (($def['expression'] ?? $inner['expression'] ?? $inner['formula'] ?? $inner['latex'] ?? '') ?: '');

            $rawOptions = $def['options'] ?? $inner['options'] ?? $def['variants'] ?? $inner['variants'] ?? null;
            $taskOptions = [];
            if (is_array($rawOptions)) {
                $taskOptions = array_values($rawOptions);
            } elseif (is_string($rawOptions) && trim($rawOptions) !== '') {
                $taskOptions = array_values(array_filter(array_map('trim', preg_split('/\R+/', $rawOptions))));
            }

            $wrongTasks[] = [
                'task_number' => $taskNum,
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
        $backUrl = "/tg/teacher/students/{$studentId}";

        return view('miniapp.history-detail', compact('attempt', 'label', 'correct', 'total', 'time', 'wrongTasks', 'backUrl'));
    }

    public function toggleTeacherStudentOwnership(Request $request, int $studentId, AuditLogger $audit): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $student = User::query()->where('id', $studentId)->where('role', 'student')->firstOrFail();

        $relation = TeacherStudent::query()
            ->where('teacher_id', $user->id)
            ->where('student_id', $student->id)
            ->first();

        if ($relation) {
            $relation->delete();
            $isMine = false;
            $event = 'teacher_student_unlinked';
        } else {
            TeacherStudent::query()->create([
                'teacher_id' => $user->id,
                'student_id' => $student->id,
                // `teacher_students.source` enum: referral|manual|homework_invite
                'source' => 'manual',
            ]);
            $isMine = true;
            $event = 'teacher_student_linked';
        }

        $audit->log([
            'event_type' => $event,
            'category' => 'teacher',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $this->resolveMiniAppRole($request, $user),
            'subject_type' => 'teacher_student',
            'subject_id' => $user->id . ':' . $student->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => [
                'is_mine' => $isMine,
                'source' => 'miniapp',
            ],
        ]);

        return response()->json([
            'success' => true,
            'is_mine' => $isMine,
        ]);
    }

    public function updateTeacherStudentAlias(Request $request, int $studentId, AuditLogger $audit): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $request->validate([
            'alias' => 'nullable|string|max:80',
        ]);

        $alias = trim((string) ($payload['alias'] ?? ''));
        $alias = $alias === '' ? null : $alias;

        $relation = TeacherStudent::query()
            ->where('teacher_id', $user->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $previousAlias = $relation->student_alias;
        $relation->student_alias = $alias;
        $relation->save();

        $audit->log([
            'event_type' => 'teacher_student_alias_updated',
            'category' => 'teacher',
            'severity' => 'info',
            'actor_user_id' => $user->id,
            'actor_role' => $this->resolveMiniAppRole($request, $user),
            'subject_type' => 'teacher_student',
            'subject_id' => $user->id . ':' . $studentId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => [
                'previous_alias' => $previousAlias,
                'new_alias' => $alias,
            ],
        ]);

        return response()->json([
            'success' => true,
            'alias' => $alias,
        ]);
    }

    public function switchMode(Request $request, string $role, AuditLogger $audit)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless(in_array($role, ['student', 'teacher'], true), 404);

        $request->session()->put('view_as_role', $role);

        $audit->log([
            'event_type' => 'view_as_set',
            'category' => 'admin',
            'severity' => 'info',
            'actor_user_id' => $request->user()->id,
            'actor_role' => 'admin',
            'subject_type' => 'view_as_role',
            'subject_id' => $role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_json' => ['source' => 'miniapp'],
        ]);

        return redirect($role === 'teacher' ? '/tg/teacher/dashboard' : '/tg/dashboard');
    }

    private function resolveMiniAppRole(Request $request, ?User $user): string
    {
        if (!$user) {
            return 'student';
        }

        if ($user->role === 'teacher') {
            return 'teacher';
        }

        if ($user->role === 'admin') {
            $viewAsRole = $request->hasSession() ? $request->session()->get('view_as_role') : null;
            if (in_array($viewAsRole, ['student', 'teacher'], true)) {
                return $viewAsRole;
            }
        }

        return 'student';
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

    /**
     * Admin: list curated variants.
     */
    public function adminVariants()
    {
        $user = Auth::user();

        $variants = OgeVariant::where('owner_teacher_id', $user->id)
            ->where('is_curated', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'hash' => $v->hash,
                'title' => $v->title,
                'task_count' => $v->curatedTasks()->count(),
                'created' => $v->created_at?->format('d.m.Y'),
            ]);

        // Load topics with zadaniya for the create form (same as OGE generator)
        $topicIds = ['06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'];
        $topicsWithZadaniya = [];

        foreach ($topicIds as $topicId) {
            try {
                $topicMeta = $this->taskData->getTopicMeta($topicId);
                $blocks = $this->taskData->getBlocks($topicId);
                if (empty($blocks)) continue;

                $zadaniyaData = [];
                foreach ($blocks as $block) {
                    foreach ($block['zadaniya'] ?? [] as $zadanie) {
                        $example = null;
                        if (($zadanie['type'] ?? '') === 'statements' && isset($zadanie['statements'][0])) {
                            $example = ['type' => 'statements', 'text' => $zadanie['statements'][0]['text'] ?? ''];
                        } elseif (isset($zadanie['tasks'][0])) {
                            $firstTask = $zadanie['tasks'][0];
                            $example = [
                                'type' => $zadanie['type'] ?? 'expression',
                                'expression' => $firstTask['expression'] ?? '',
                                'text' => $firstTask['text'] ?? '',
                            ];
                        }

                        $taskCount = ($zadanie['type'] ?? '') === 'statements'
                            ? count($zadanie['statements'] ?? [])
                            : count($zadanie['tasks'] ?? []);

                        $zadaniyaData[] = [
                            'zadanie_id' => "{$topicId}_{$block['number']}_{$zadanie['number']}",
                            'block_number' => $block['number'],
                            'zadanie_number' => $zadanie['number'],
                            'instruction' => $zadanie['instruction'] ?? '',
                            'task_count' => $taskCount,
                            'example' => $example,
                        ];
                    }
                }

                if (!empty($zadaniyaData)) {
                    $topicsWithZadaniya[] = [
                        'topic_id' => $topicId,
                        'topic_number' => ltrim($topicId, '0'),
                        'title' => $topicMeta['title'],
                        'zadaniya' => $zadaniyaData,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return view('miniapp.admin-variants', [
            'variants' => $variants,
            'topicsWithZadaniya' => $topicsWithZadaniya,
        ]);
    }

    /**
     * Admin: create curated variant (POST).
     */
    public function createCuratedVariant(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|min:2|max:200',
            'zadaniya' => 'required|array|min:1|max:100',
            'zadaniya.*' => 'required|string|regex:/^\d{2}_\d+_\d+$/',
        ]);

        $user = $request->user();
        $hash = $this->miniVariant->generateHash();

        // Build tasks: for each zadanie_id, pick one random task
        $selectedTasks = [];
        $sortOrder = 1;

        foreach ($data['zadaniya'] as $zadanieId) {
            [$topicId, $blockNumber, $zadanieNumber] = explode('_', $zadanieId);
            $taskNumber = (int) ltrim($topicId, '0');

            $task = $this->taskData->getRandomTasksFromZadanie(
                $topicId,
                (int) $blockNumber,
                (int) $zadanieNumber,
                1
            );

            if (!empty($task)) {
                $t = $task[0];
                $t['task_number'] = $taskNumber;
                $selectedTasks[] = $t;
            }
            $sortOrder++;
        }

        if (empty($selectedTasks)) {
            return response()->json(['success' => false, 'message' => 'Не удалось найти задания'], 422);
        }

        $variant = OgeVariant::create([
            'hash' => $hash,
            'owner_teacher_id' => $user->id,
            'title' => $data['title'],
            'mode' => OgeVariant::MODE_FULL,
            'is_curated' => true,
            'source' => OgeVariant::SOURCE_CURATED,
            'config_json' => ['tasks' => $selectedTasks],
        ]);

        // Save curated task references
        $sortOrder = 1;
        foreach ($data['zadaniya'] as $zadanieId) {
            [$topicId, $blockNumber, $zadanieNumber] = explode('_', $zadanieId);
            $variant->curatedTasks()->create([
                'task_number' => (int) ltrim($topicId, '0'),
                'topic_id' => $topicId,
                'block_number' => (int) $blockNumber,
                'zadanie_number' => (int) $zadanieNumber,
                'task_index' => 0,
                'sort_order' => $sortOrder++,
            ]);
        }

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'hash' => $variant->hash,
                'title' => $variant->title,
                'task_count' => count($selectedTasks),
            ],
        ]);
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
     * Teacher homework page — list today's students and assigned homework.
     */
    public function teacherHomework(Request $request)
    {
        $user = $request->user();

        // Today's students from lesson_schedule
        $dow = (int) now()->format('N');
        $todaySlots = \App\Models\LessonSchedule::where('teacher_id', $user->id)
            ->where('day_of_week', $dow)
            ->where('is_active', true)
            ->with('student:id,name')
            ->orderBy('start_time')
            ->get();

        // All teacher's students (fallback if no schedule)
        $allStudentIds = TeacherStudent::where('teacher_id', $user->id)
            ->pluck('student_id');
        $allStudents = User::whereIn('id', $allStudentIds)->select('id', 'name')->orderBy('name')->get();

        // Recent homework assigned by this teacher
        $recentHomework = \App\Models\Homework::where('teacher_id', $user->id)
            ->whereIn('homework_type', ['full_variant', 'topic_practice'])
            ->orderByDesc('assigned_at')
            ->limit(30)
            ->get();

        $recentHomework->load('assignments.student:id,name');

        return view('miniapp.teacher-homework', compact(
            'user', 'todaySlots', 'allStudents', 'recentHomework'
        ));
    }

    /**
     * Assign homework to a student (POST).
     */
    public function assignHomework(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'type' => 'required|in:full_variant,topic_practice',
            'topic_number' => 'required_if:type,topic_practice|nullable|integer',
        ]);

        $studentId = (int) $data['student_id'];

        $isTeacher = TeacherStudent::where('teacher_id', $user->id)
            ->where('student_id', $studentId)
            ->exists();

        if (!$isTeacher) {
            return back()->with('error', 'Ученик не найден.');
        }

        $homework = new \App\Models\Homework();
        $homework->teacher_id = $user->id;
        $homework->homework_type = $data['type'];

        if ($data['type'] === 'full_variant') {
            $student = User::findOrFail($studentId);
            try {
                $variant = $this->poolService->getOrCreateVariant($student, 'full');
            } catch (\RuntimeException $e) {
                return back()->with('error', 'Не удалось создать вариант: ' . $e->getMessage());
            }
            $homework->variant_hash = $variant->hash;
            $homework->title = 'Полный вариант ОГЭ';
        } else {
            $topicNumber = (int) $data['topic_number'];
            $homework->topic_number = $topicNumber;
            $homework->title = 'Тема ' . $topicNumber;
        }

        $homework->assigned_at = now();
        $homework->save();

        \App\Models\HomeworkAssignment::create([
            'homework_id' => $homework->id,
            'student_id' => $studentId,
            'status' => 'assigned',
        ]);

        return back()->with('success', 'ДЗ выдано!');
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

    protected function modeName(string $mode): string
    {
        return match ($mode) {
            'geometry' => 'Геометрия',
            'algebra' => 'Алгебра',
            'mixed' => 'Смешанное',
            default => $mode,
        };
    }
}
