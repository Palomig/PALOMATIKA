<?php

namespace App\Http\Controllers;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Services\MiniVariantService;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\OgeVariantPoolService;
use App\Services\TaskDataService;
use App\Http\Controllers\Auth\TelegramBotAuthController;
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
    ) {
    }

    /**
     * Home page — the landing with countdown and CTA.
     */
    public function home()
    {
        return view('miniapp.home');
    }

    /**
     * Server-side Telegram WebApp authentication via form POST.
     * Verifies initData HMAC, logs in user, and redirects (302).
     * This avoids the session cookie loss issue with client-side fetch + JS redirect.
     */
    public function authenticate(Request $request)
    {
        $trace = function (string $event, array $ctx = []) {
            $line = json_encode([
                'ts' => now()->toIso8601String(),
                'event' => $event,
                'ctx' => $ctx,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            @file_put_contents(storage_path('app/tg_auth_trace.log'), $line . PHP_EOL, FILE_APPEND);
        };

        $initData = trim((string) $request->input('initData', ''));
        $trace('request_received', [
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'initData_len' => strlen($initData),
            'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
        ]);
        $trace('request_received', [
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'initData_len' => strlen($initData),
            'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
        ]);

        if ($initData === '') {
            $trace('fail_empty_initData');
            return redirect('/tg/')->with('error', 'Нет данных Telegram для входа');
        }

        $botToken = (string) config('services.telegram.bot_token', '');
        if ($botToken === '') {
            $trace('fail_no_bot_token');
            return redirect('/tg/')->with('error', 'Telegram не настроен');
        }

        // Parse and verify HMAC signature
        parse_str($initData, $fields);
        if (empty($fields) || empty($fields['hash'])) {
            $trace('fail_invalid_payload');
            return redirect('/tg/')->with('error', 'Некорректные данные Telegram');
        }

        $providedHash = $fields['hash'];
        $signableFields = $fields;
        unset($signableFields['hash']);

        // Normalize fields for signature check
        $normalizedFields = [];
        foreach ($signableFields as $key => $value) {
            if ($value === null) {
                continue;
            }
            $normalizedFields[(string) $key] = is_array($value)
                ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (string) $value;
        }

        ksort($normalizedFields);
        $dataCheckString = collect($normalizedFields)
            ->map(fn(string $value, string $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $providedHash)) {
            $trace('fail_bad_hmac', ['auth_date' => $fields['auth_date'] ?? null]);
            return redirect('/tg/')->with('error', 'Неверная подпись Telegram');
        }

        // Extract user from initData
        $userJson = $fields['user'] ?? null;
        if (is_string($userJson)) {
            $telegramUser = json_decode($userJson, true);
        } else {
            $telegramUser = null;
        }

        if (!is_array($telegramUser) || empty($telegramUser['id'])) {
            $trace('fail_no_user');
            return redirect('/tg/')->with('error', 'Нет данных пользователя');
        }

        // Find or create user
        $telegramId = (string) $telegramUser['id'];
        $user = \App\Models\User::where('oauth_provider', 'telegram')
            ->where('oauth_id', $telegramId)
            ->first();

        if (!$user) {
            $name = trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? ''));
            if ($name === '') {
                $name = $telegramUser['username'] ?? 'User';
            }
            $user = \App\Models\User::create([
                'name' => $name,
                'oauth_provider' => 'telegram',
                'oauth_id' => $telegramId,
                'avatar' => $telegramUser['photo_url'] ?? null,
                'trial_ends_at' => now()->addDays(7),
            ]);
        }

        // Login with remember + regenerate session
        Auth::login($user, true);
        $request->session()->regenerate();
        $trace('success', [
            'user_id' => $user->id,
            'tg_user_id' => $telegramUser['id'] ?? null,
            'session_id' => $request->session()->getId(),
        ]);

        // Preserve Telegram startapp deep-link context (e.g., oge_variant_hash_*).
        $startParam = trim((string) ($fields['start_param'] ?? $request->input('startParam', '')));
        if ($startParam !== '') {
            $request->session()->put('telegram_start_param', $startParam);
        }

        $redirectTo = !$user->onboarding_completed_at ? '/tg/onboarding' : '/tg/dashboard';
        if ($startParam !== '' && !$user->onboarding_completed_at) {
            $redirectTo .= '?startapp=' . rawurlencode($startParam);
        } elseif ($startParam !== '' && $user->onboarding_completed_at) {
            $redirectTo .= '?startapp=' . rawurlencode($startParam);
        }

        // Telegram WebView/VPN compatibility: do not rely on cookie continuity
        // between auth and next hop. Pass a short-lived one-time handoff token.
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
        @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
            'ts' => now()->toIso8601String(),
            'event' => 'bridge_ping',
            'ctx' => [
                'session_id' => $request->session()->getId(),
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

        return response()->json(['ok' => true]);
    }

    public function authContinue(Request $request)
    {
        $token = trim((string) $request->query('token', ''));
        $handoff = $token !== '' ? Cache::pull('tg_auth_handoff:' . $token) : null;

        if (is_array($handoff) && !empty($handoff['user_id'])) {
            $user = \App\Models\User::find((int) $handoff['user_id']);
            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();
                @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
                    'ts' => now()->toIso8601String(),
                    'event' => 'auth_continue_handoff_ok',
                    'ctx' => [
                        'user_id' => $user->id,
                        'session_id' => $request->session()->getId(),
                        'ip' => $request->ip(),
                        'ua' => $request->userAgent(),
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

                // Hard bypass for fragile WebView hops: render onboarding directly
                // in this authenticated request instead of one more redirect.
                if (!$user->onboarding_completed_at) {
                    @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
                        'ts' => now()->toIso8601String(),
                        'event' => 'onboarding_inline_render',
                        'ctx' => [
                            'user_id' => $user->id,
                            'session_id' => $request->session()->getId(),
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

                    return view('miniapp.onboarding', [
                        'onboardingToken' => $this->issueOnboardingToken($user->id),
                    ]);
                }

                $target = (string) ($handoff['redirect_to'] ?? '/tg/dashboard');
                return response()->view('miniapp.auth-bridge-final', [
                    'target' => $target,
                ]);
            }
        }

        $user = Auth::user();
        @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
            'ts' => now()->toIso8601String(),
            'event' => 'auth_continue',
            'ctx' => [
                'auth_check' => Auth::check(),
                'user_id' => optional($user)->id,
                'session_id' => $request->session()->getId(),
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'has_token' => $token !== '',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

        if (!$user) {
            return redirect('/tg/')->with('error', 'Сессия входа не сохранилась. Попробуйте ещё раз.');
        }

        return response()->view('miniapp.auth-bridge-final', [
            'target' => (!$user->onboarding_completed_at ? '/tg/onboarding' : '/tg/dashboard'),
        ]);
    }

    /**
     * Onboarding form (GET).
     */
    public function onboarding(Request $request)
    {
        @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
            'ts' => now()->toIso8601String(),
            'event' => 'onboarding_open',
            'ctx' => [
                'user_id' => optional($request->user())->id,
                'session_id' => $request->session()->getId(),
                'ip' => $request->ip(),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

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

        if (!$user && !empty($data['onboarding_token'])) {
            $payload = Cache::pull('tg_onb_token:' . $data['onboarding_token']);
            if (is_array($payload) && !empty($payload['user_id'])) {
                $user = \App\Models\User::find((int) $payload['user_id']);
                if ($user) {
                    Auth::login($user, true);
                    $request->session()->regenerate();
                    @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
                        'ts' => now()->toIso8601String(),
                        'event' => 'onboarding_token_auth_ok',
                        'ctx' => [
                            'user_id' => $user->id,
                            'session_id' => $request->session()->getId(),
                            'ip' => $request->ip(),
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
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
        @file_put_contents(storage_path('app/tg_auth_trace.log'), json_encode([
            'ts' => now()->toIso8601String(),
            'event' => 'dashboard_open',
            'ctx' => [
                'user_id' => optional($user)->id,
                'session_id' => $request->session()->getId(),
                'ip' => $request->ip(),
                'startapp_q' => (string) $request->query('startapp', ''),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

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

        // Last submitted attempt
        $lastAttempt = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->first();

        $lastCorrect = 0;
        $lastTotal = 0;
        $lastTime = null;

        if ($lastAttempt) {
            $scorings = OgeAttemptScoring::where('attempt_id', $lastAttempt->id)->get();
            $lastTotal = $scorings->count();
            $lastCorrect = $scorings->where('is_correct', true)->count();

            if ($lastAttempt->started_at && $lastAttempt->submitted_at) {
                $lastTime = $lastAttempt->submitted_at->diffInSeconds($lastAttempt->started_at);
            }
        }

        // Weak topics (from all submitted attempts)
        $weakTopics = $this->computeWeakTopics($user->id);

        $newFipiCount = $this->countNewFipiTasks();

        return view('miniapp.dashboard', compact(
            'user', 'lastAttempt', 'lastCorrect', 'lastTotal', 'lastTime', 'weakTopics', 'newFipiCount'
        ));
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
        $request->validate(['mode' => 'required|string|in:geometry,algebra,mixed']);
        $mode = $request->input('mode');
        $user = $request->user();

        try {
            $variant = $this->poolService->getOrCreateVariant($user, $mode);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'Нет доступных заданий для этого режима. Попробуйте позже.',
            ], 422);
        }

        // Start attempt
        [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);

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

        try {
            $variant = $this->poolService->getOrCreateVariant($user, 'full');
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => 'Нет доступных заданий. Попробуйте позже.',
            ], 422);
        }

        [$variant, $attempt] = $this->attemptService->startAttempt($user, $variant->hash);

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

        // If already submitted, redirect to results
        if ($attempt->status === 'submitted') {
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

            $inner = is_array($task['task'] ?? null) ? $task['task'] : [];

            $task['text'] = $task['text'] ?? ($inner['text'] ?? null);
            $task['expression'] = $task['expression'] ?? ($inner['expression'] ?? null);
            $task['svg'] = $task['svg'] ?? ($inner['svg'] ?? null);
            $task['options'] = $task['options'] ?? ($inner['options'] ?? null);

            // statements-mode fallback for old payloads
            if (($task['type'] ?? '') === 'statements') {
                $statements = $task['selected_statements'] ?? $task['statements'] ?? [];
                if (is_array($statements) && !empty($statements)) {
                    // Ensure UI has visible content even when expression/svg are absent.
                    $lines = [];
                    foreach ($statements as $idx => $s) {
                        if (is_array($s)) {
                            $text = (string) ($s['text'] ?? '');
                            $num = (int) ($s['display_number'] ?? ($idx + 1));
                        } else {
                            $text = (string) $s;
                            $num = $idx + 1;
                        }
                        if ($text !== '') {
                            $lines[] = $num . ') ' . e($text);
                        }
                    }
                    if (!empty($lines) && empty($task['text'])) {
                        $task['text'] = implode('<br>', $lines);
                    }
                }
            }

            return $task;
        }, $tasks);

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

        if (!in_array($attempt->status, ['submitted', 'scored'], true)) {
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

        // Battle leaderboard: for shared miniapp variants show other participants too.
        $leaderboard = [];
        $isBattleVariant = ($attempt->variant?->source() ?? '') === OgeVariant::SOURCE_MINIAPP;
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
     * Tutor page.
     */
    public function tutor()
    {
        $user = Auth::user();

        // Get last result for context
        $lastAttempt = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'submitted')
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
     * Compute weak topics from all submitted attempts.
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
            ->where('a.status', 'submitted')
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
