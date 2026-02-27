<?php

namespace App\Http\Controllers;

use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\OgeVariant;
use App\Services\MiniVariantService;
use App\Services\OgeAttemptService;
use App\Services\OgeVariantBuilderService;
use App\Services\TaskDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MiniAppController extends Controller
{
    public function __construct(
        private readonly MiniVariantService $miniVariant,
        private readonly OgeAttemptService $attemptService,
        private readonly OgeVariantBuilderService $variantBuilder,
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
     * Onboarding form (GET).
     */
    public function onboarding()
    {
        return view('miniapp.onboarding');
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
        ]);

        $user = $request->user();
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
    public function dashboard()
    {
        $user = Auth::user();

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

        return view('miniapp.dashboard', compact(
            'user', 'lastAttempt', 'lastCorrect', 'lastTotal', 'lastTime', 'weakTopics'
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

        $tasks = match ($mode) {
            'geometry' => $this->miniVariant->generateGeometry(),
            'algebra' => $this->miniVariant->generateAlgebra(),
            'mixed' => $this->miniVariant->generateMixed(),
        };

        $modeMap = [
            'geometry' => OgeVariant::MODE_MINI_GEOMETRY,
            'algebra' => OgeVariant::MODE_MINI_ALGEBRA,
            'mixed' => OgeVariant::MODE_MINI_MIXED,
        ];

        $hash = $this->miniVariant->generateHash();

        // Create variant with task selection stored in config_json
        $variant = OgeVariant::create([
            'hash' => $hash,
            'title' => 'Мини-ОГЭ: ' . $this->modeName($mode),
            'mode' => $modeMap[$mode],
            'source' => OgeVariant::SOURCE_MINIAPP,
            'config_json' => ['tasks' => $tasks, 'mode' => $mode],
        ]);

        // Start attempt
        [$variant, $attempt] = $this->attemptService->startAttempt($user, $hash);

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
        $tasks = $this->miniVariant->generateFull();
        $hash = $this->miniVariant->generateHash();

        $variant = OgeVariant::create([
            'hash' => $hash,
            'title' => 'Полный вариант ОГЭ',
            'mode' => OgeVariant::MODE_FULL,
            'source' => OgeVariant::SOURCE_MINIAPP,
            'config_json' => ['tasks' => $tasks, 'mode' => 'full'],
        ]);

        [$variant, $attempt] = $this->attemptService->startAttempt($user, $hash);

        return response()->json([
            'redirect' => '/tg/test/' . $attempt->id,
        ]);
    }

    /**
     * Test screen — active attempt.
     */
    public function test(int $attemptId)
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

        if ($attempt->status !== 'submitted') {
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

        return view('miniapp.results', compact(
            'attempt', 'scorings', 'answers', 'totalTasks', 'correctCount', 'totalTime'
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

        return view('miniapp.admin-variants', ['variants' => $variants]);
    }

    /**
     * Admin: create curated variant (POST).
     */
    public function createCuratedVariant(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|min:2|max:200',
            'tasks' => 'required|array|min:1|max:14',
            'tasks.*.topic_id' => 'required|string|size:2',
            'tasks.*.block_number' => 'required|integer',
            'tasks.*.zadanie_number' => 'required|integer',
            'tasks.*.task_index' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $hash = $this->miniVariant->generateHash();

        // Build tasks from selection
        $selectedTasks = [];
        $sortOrder = 1;
        foreach ($data['tasks'] as $topicId => $sel) {
            $taskNumber = (int) ltrim($topicId, '0');
            $task = $this->taskData->getRandomTasksFromZadanie(
                $topicId,
                $sel['block_number'],
                $sel['zadanie_number'],
                1
            );
            if (!empty($task)) {
                $t = $task[0];
                $t['task_number'] = $taskNumber;
                $selectedTasks[] = $t;
            }
            $sortOrder++;
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
        foreach ($data['tasks'] as $topicId => $sel) {
            $variant->curatedTasks()->create([
                'task_number' => (int) ltrim($topicId, '0'),
                'topic_id' => $topicId,
                'block_number' => $sel['block_number'],
                'zadanie_number' => $sel['zadanie_number'],
                'task_index' => $sel['task_index'],
                'sort_order' => $sortOrder++,
            ]);
        }

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'hash' => $variant->hash,
                'title' => $variant->title,
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
