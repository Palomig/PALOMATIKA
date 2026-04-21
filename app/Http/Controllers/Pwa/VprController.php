<?php
namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MiniAppHelpers;
use App\Models\OgeAttempt;
use App\Models\OgeAttemptScoring;
use App\Models\TeacherStudent;
use App\Models\UserGift;
use App\Models\OgeVariant;
use App\Services\OgeAttemptService;
use App\Services\VariantTaskNumberResolver;
use App\Services\VprTaskDataService;
use App\Services\VprVariantBuilderService;
use App\Services\VprVariantPoolService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VprController extends Controller
{
    use MiniAppHelpers;

    private function resolveVprGrade(Request $request): int
    {
        $user = Auth::user();
        $userGrade = (int) ($user->grade_num ?? 0);

        if ($this->supportsStudentViewContext($request, $user)) {
            $requestedGrade = (int) ($request->input('grade', $request->query('grade', 0)));

            if (in_array($requestedGrade, [5, 6, 7, 8], true)) {
                return $requestedGrade;
            }

            return $this->resolveStudentViewVprGrade($request, $user);
        }

        if ($user && $user->role === 'admin') {
            $requestedGrade = (int) ($request->input('grade', $request->query('grade', 5)));
            return in_array($requestedGrade, [5, 6, 7, 8], true) ? $requestedGrade : 5;
        }

        return $userGrade;
    }

    /**
     * Не-админ с классом вне [5-8] не должен видеть ВПР-дашборд —
     * перенаправляем на универсальный диспетчер, который разведёт по экзаменам.
     */
    private function redirectIfWrongGrade(int $grade)
    {
        $user = Auth::user();
        if ($user && ($user->role === 'admin' || $user->role === 'teacher')) {
            return null;
        }
        if ($grade < 5 || $grade > 8) {
            return redirect()->route('pwa.student.dashboard');
        }
        return null;
    }

    /**
     * Определяет класс по exam_type варианта (vpr_5 → 5). Дефолт — 5.
     */
    private function gradeFromVariant(?OgeVariant $variant): int
    {
        $grade = (int) preg_replace('/\D+/', '', (string) ($variant->exam_type ?? ''));
        return ($grade >= 5 && $grade <= 8) ? $grade : 5;
    }

    private function makePool(int $grade): VprVariantPoolService
    {
        $taskData = new VprTaskDataService($grade);
        $builder  = new VprVariantBuilderService($taskData);
        return new VprVariantPoolService($taskData, $builder);
    }

    /**
     * ВПР-дашборд (home page).
     */
    public function home(Request $request)
    {
        $user  = Auth::user();
        $grade = $this->resolveVprGrade($request);

        if ($redirect = $this->redirectIfWrongGrade($grade)) {
            return $redirect;
        }

        $examType = 'vpr_' . $grade;
        $activeAttempts = OgeAttempt::where('student_id', $user->id)
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->whereHas('variant', fn($q) => $q->where('exam_type', $examType))
            ->with('variant')
            ->orderByDesc('last_seen_at')
            ->get();

        $activeAttemptsList = $activeAttempts->map(function ($att) {
            $variant = $att->variant;
            $mode = $variant->mode ?? OgeVariant::MODE_FULL;
            $isMini = str_starts_with($mode, 'mini_');

            return [
                'id'            => $att->id,
                'title'         => $variant->title ?? 'Вариант ВПР',
                'answeredCount' => $att->answers()->count(),
                'totalCount'    => count($variant->config_json['tasks'] ?? []),
                'startedAt'     => $att->started_at,
                'type'          => $isMini ? 'Мини-ВПР' : 'Полный вариант',
            ];
        })->all();

        $weakTopics = [];
        $newFipiCount = 0;
        $hasTeacher = TeacherStudent::where('student_id', $user->id)->exists();
        $pendingGift = UserGift::where('user_id', $user->id)
            ->whereNull('shown_at')
            ->orderBy('id')
            ->first();

        return view('pwa.student.vpr-home', compact(
            'user',
            'grade',
            'activeAttemptsList',
            'weakTopics',
            'newFipiCount',
            'hasTeacher',
            'pendingGift'
        ));
    }

    /**
     * Старт mini-ВПР (POST).
     */
    public function startMini(Request $request, OgeAttemptService $attemptService)
    {
        $request->validate([
            'mode' => 'required|string|in:mixed',
            'grade' => 'nullable|integer|in:5,6,7,8',
        ]);

        $user  = Auth::user();
        $grade = $this->resolveVprGrade($request);

        if ($grade < 5 || $grade > 8) {
            abort(403, 'ВПР доступно только для 5–8 классов');
        }

        try {
            $pool = $this->makePool($grade);
            $variant = $pool->getOrCreateVariant($user, 'mixed');
        } catch (\Throwable $e) {
            Log::error('PWA VPR mini start pool error', ['user' => $user?->id, 'grade' => $grade, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Нет доступных заданий для мини-ВПР. Попробуйте позже.'], 422);
        }

        try {
            [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('PWA VPR mini start attempt error', ['user' => $user?->id, 'grade' => $grade, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка создания попытки. Попробуйте ещё раз.'], 500);
        }

        return response()->json(['redirect' => route('pwa.student.vpr.test', $attempt->id)]);
    }

    /**
     * Старт нового варианта ВПР (POST).
     */
    public function startFull(Request $request, OgeAttemptService $attemptService)
    {
        $request->validate([
            'grade' => 'nullable|integer|in:5,6,7,8',
        ]);

        $user  = Auth::user();
        $grade = $this->resolveVprGrade($request);

        if ($grade < 5 || $grade > 8) {
            abort(403, 'ВПР доступно только для 5–8 классов');
        }

        $pool    = $this->makePool($grade);
        $variant = $pool->getOrCreateVariant($user, 'full');

        [$variant, $attempt] = $attemptService->startAttempt($user, $variant->hash, [
            'user_agent' => $request->userAgent(),
            'ip'         => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('pwa.student.vpr.test', $attempt->id)]);
        }

        return redirect()->route('pwa.student.vpr.test', $attempt->id);
    }

    /**
     * Страница решения варианта ВПР.
     */
    public function test(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->with('variant')
            ->when($user->role !== 'admin', fn ($query) => $query->where('student_id', $user->id))
            ->firstOrFail();

        if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
            return redirect()->route('pwa.student.vpr.results', $attempt->id);
        }

        $variant = $attempt->variant;
        $tasks   = $this->normalizeAttemptTasks($variant, $variant->config_json['tasks'] ?? []);
        $answers = $this->resolveExistingAttemptAnswers($attempt, $tasks);
        $mode    = $variant->mode ?? 'full';
        $title   = $variant->title ?? 'Вариант ВПР';

        $topicNames = (new VprTaskDataService($this->gradeFromVariant($variant)))->getTopicNamesMap();

        return view('pwa.student.vpr-test', compact(
            'attempt', 'tasks', 'answers', 'mode', 'title', 'topicNames'
        ));
    }

    /**
     * База заданий ВПР — просмотр всех задач по номеру.
     */
    public function taskDatabase(Request $request)
    {
        $user  = Auth::user();
        $grade = $this->resolveVprGrade($request);
        $taskData = new VprTaskDataService($grade);

        $topicIds = collect(array_keys($taskData->getAllTopicsMeta()))
            ->map(fn($topicId) => str_pad((string) $topicId, 2, '0', STR_PAD_LEFT))
            ->filter(fn(string $topicId) => $taskData->topicDataExists($topicId))
            ->values()
            ->all();

        if ($topicIds === []) {
            $topicIds = ['01'];
        }

        $maxTopic = (int) ltrim((string) end($topicIds), '0');

        $selected = str_pad((string) $request->query('topic', '1'), 2, '0', STR_PAD_LEFT);
        if (!in_array($selected, $topicIds, true)) {
            $selected = $topicIds[0];
        }

        $blocks   = $taskData->getBlocks($selected);

        $zadaniya = [];
        foreach ($blocks as $block) {
            foreach (($block['zadaniya'] ?? []) as $zadanie) {
                $tasks = [];
                foreach (($zadanie['tasks'] ?? []) as $t) {
                    $text       = trim((string) ($t['text'] ?? ''));
                    $expression = trim((string) ($t['expression'] ?? ''));
                    $question   = trim((string) ($t['question'] ?? ''));
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
                        'answer'     => $t['answer'] ?? null,
                    ];
                }
                if (!empty($tasks)) {
                    $num   = $zadanie['number'] ?? '';
                    $label = trim((string) ($zadanie['label'] ?? ''));
                    $instr = trim((string) ($zadanie['instruction'] ?? ''));
                    $title = $label !== '' ? $label : ($instr !== '' ? "Задание {$num}. {$instr}" : "Задание {$num}");
                    $zadaniya[] = ['title' => $title, 'tasks' => $tasks];
                }
            }
        }

        $taskCount = array_sum(array_map(fn($z) => count($z['tasks']), $zadaniya));

        return view('pwa.student.vpr-tasks', compact(
            'grade', 'topicIds', 'selected', 'zadaniya', 'taskCount', 'maxTopic'
        ));
    }

    /**
     * Страница результатов ВПР.
     */
    public function results(Request $request, int $attemptId)
    {
        $user    = Auth::user();
        $attempt = OgeAttempt::where('id', $attemptId)
            ->whereIn('status', ['submitted', 'scored', 'error'])
            ->with(['variant', 'scorings'])
            ->when($user->role !== 'admin', fn ($query) => $query->where('student_id', $user->id))
            ->firstOrFail();

        $variantTasks = $this->normalizeAttemptTasks($attempt->variant, $attempt->variant?->config_json['tasks'] ?? []);
        if ($attempt->variant) {
            $config = is_array($attempt->variant->config_json) ? $attempt->variant->config_json : [];
            $config['tasks'] = $variantTasks;
            $attempt->variant->config_json = $config;
        }
        $scorings = $this->resolveResultScorings($attempt, $variantTasks);
        $answers = $this->resolveResultAnswers($attempt, $variantTasks);
        $totalTasks = count($variantTasks);
        $correctCount = $scorings->where('is_correct', true)->count();
        $totalTime = 0;
        if ($attempt->started_at && $attempt->submitted_at) {
            $totalTime = $attempt->submitted_at->diffInSeconds($attempt->started_at);
        }

        $topicNames = (new VprTaskDataService($this->gradeFromVariant($attempt->variant)))->getTopicNamesMap();

        return view('pwa.student.vpr-results', compact(
            'user',
            'attempt',
            'scorings',
            'answers',
            'totalTasks',
            'correctCount',
            'totalTime',
            'topicNames'
        ));
    }

    /**
     * @param array<int, mixed> $tasks
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAttemptTasks(OgeVariant $variant, array $tasks): array
    {
        $rawGrade = (int) preg_replace('/\D+/', '', (string) ($variant->exam_type ?? ''));
        $taskData = ($rawGrade >= 5 && $rawGrade <= 8) ? new VprTaskDataService($rawGrade) : null;

        foreach ($tasks as $index => $task) {
            if (!is_array($task)) {
                continue;
            }

            if ($taskData && !empty($task['topic_id'])) {
                $task = $taskData->canonicalizeLegacyTask((string) $task['topic_id'], $task);
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
    private function resolveExistingAttemptAnswers(OgeAttempt $attempt, array $tasks): array
    {
        $existingAnswers = $attempt->answers()->pluck('current_answer', 'task_number')->toArray();
        if ($existingAnswers === []) {
            return [];
        }

        $maxSlot = count($tasks);
        $hasLegacyNumbering = collect(array_keys($existingAnswers))
            ->contains(fn ($taskNumber) => (int) $taskNumber > $maxSlot);

        $mapped = [];
        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $slot = (int) ($task['attempt_task_number'] ?? 0);
            $examNumber = (int) ($task['display_task_number'] ?? $task['task_number'] ?? 0);
            if ($slot < 1) {
                continue;
            }

            if ($hasLegacyNumbering && $examNumber > 0 && array_key_exists($examNumber, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$examNumber];
                continue;
            }

            if (array_key_exists($slot, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$slot];
                continue;
            }

            if (!$hasLegacyNumbering && $examNumber > 0 && array_key_exists($examNumber, $existingAnswers)) {
                $mapped[$slot] = $existingAnswers[$examNumber];
            }
        }

        return $mapped;
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     */
    private function resolveResultScorings(OgeAttempt $attempt, array $tasks): Collection
    {
        $rawScorings = OgeAttemptScoring::where('attempt_id', $attempt->id)
            ->orderBy('task_number')
            ->get();

        $slotNumbers = collect($tasks)
            ->map(fn (array $task) => (int) ($task['attempt_task_number'] ?? 0))
            ->filter(fn (int $slot) => $slot > 0)
            ->values();

        $displayToSlot = collect($tasks)
            ->mapWithKeys(function (array $task) {
                $slot = (int) ($task['attempt_task_number'] ?? 0);
                $display = (int) ($task['display_task_number'] ?? $task['task_number'] ?? 0);

                return $slot > 0 && $display > 0 ? [$display => $slot] : [];
            });

        $canonical = collect();
        foreach ($slotNumbers as $slot) {
            $scoring = $rawScorings->firstWhere('task_number', $slot);
            if (!$scoring) {
                $displayNumber = $displayToSlot->search($slot, true);
                if (is_int($displayNumber) && $displayNumber > 0) {
                    $scoring = $rawScorings->firstWhere('task_number', $displayNumber);
                }
            }

            if ($scoring) {
                $canonical->push($scoring);
            }
        }

        return $canonical;
    }

    /**
     * @param array<int, array<string, mixed>> $tasks
     */
    private function resolveResultAnswers(OgeAttempt $attempt, array $tasks): Collection
    {
        $rawAnswers = $attempt->answers()->get();
        $answersByTask = $rawAnswers->keyBy(fn ($answer) => (int) $answer->task_number);
        $maxSlot = count($tasks);
        $hasLegacyNumbering = $answersByTask->keys()->contains(fn ($taskNumber) => (int) $taskNumber > $maxSlot);
        $canonical = collect();

        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $slot = (int) ($task['attempt_task_number'] ?? 0);
            $display = (int) ($task['display_task_number'] ?? $task['task_number'] ?? 0);
            if ($slot < 1) {
                continue;
            }

            $answer = null;
            if ($hasLegacyNumbering && $display > 0) {
                $answer = $answersByTask->get($display);
            }

            if (!$answer) {
                $answer = $answersByTask->get($slot);
            }

            if (!$answer && !$hasLegacyNumbering && $display > 0) {
                $answer = $answersByTask->get($display);
            }

            if ($answer) {
                $canonical->push($answer);
            }
        }

        return $canonical;
    }
}
