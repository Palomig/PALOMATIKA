<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\OgeAttempt;
use App\Models\User;
use App\Services\OgeVariantBuilderService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentsController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $search = trim((string) $request->query('search', ''));
        $scope = $request->query('scope') === 'linked' ? 'linked' : 'all';

        $attemptMetrics = DB::table('oge_attempts')
            ->selectRaw('student_id, COUNT(*) as oge_attempt_count')
            ->selectRaw('MAX(COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at)) as oge_last_activity_at')
            ->groupBy('student_id');

        $scoringMetrics = DB::table('oge_attempts as oa')
            ->leftJoin('oge_attempt_scorings as oas', 'oas.attempt_id', '=', 'oa.id')
            ->selectRaw('oa.student_id')
            ->selectRaw('SUM(CASE WHEN oas.is_correct = 1 THEN 1 ELSE 0 END) as oge_correct_count')
            ->selectRaw('SUM(CASE WHEN oas.is_correct IS NOT NULL THEN 1 ELSE 0 END) as oge_scored_count')
            ->groupBy('oa.student_id');

        $students = DB::table('users')
            ->leftJoinSub($attemptMetrics, 'attempt_metrics', fn ($join) => $join->on('attempt_metrics.student_id', '=', 'users.id'))
            ->leftJoinSub($scoringMetrics, 'scoring_metrics', fn ($join) => $join->on('scoring_metrics.student_id', '=', 'users.id'))
            ->select('users.*')
            ->selectRaw('COALESCE(attempt_metrics.oge_attempt_count, 0) as oge_attempt_count')
            ->selectRaw('attempt_metrics.oge_last_activity_at as oge_last_activity_at')
            ->selectRaw('COALESCE(scoring_metrics.oge_correct_count, 0) as oge_correct_count')
            ->selectRaw('COALESCE(scoring_metrics.oge_scored_count, 0) as oge_scored_count')
            ->selectRaw(
                'CASE WHEN EXISTS (
                    SELECT 1
                    FROM teacher_students
                    WHERE teacher_students.student_id = users.id'
                . ($actor->role !== 'admin' ? ' AND teacher_students.teacher_id = ?' : '')
                . ') THEN 1 ELSE 0 END as is_linked',
                $actor->role !== 'admin' ? [$actor->id] : []
            )
            ->where('users.role', 'student')
            ->when($scope === 'linked', function ($query) use ($actor) {
                $query->whereExists(function (Builder $subquery) use ($actor) {
                    $subquery->selectRaw('1')
                        ->from('teacher_students')
                        ->whereColumn('teacher_students.student_id', 'users.id');

                    if ($actor->role !== 'admin') {
                        $subquery->where('teacher_students.teacher_id', $actor->id);
                    }
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('COALESCE(attempt_metrics.oge_last_activity_at, users.last_active_at, users.created_at) DESC')
            ->orderBy('users.name')
            ->paginate(10)
            ->withQueryString();

        $this->decorateRosterRows($students);

        return view('teacher.students', [
            'students' => $students,
            'search' => $search,
            'scope' => $scope,
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $actor = $request->user();

        $student = User::query()
            ->where('role', 'student')
            ->findOrFail($id);

        $attempts = OgeAttempt::query()
            ->where('student_id', $student->id)
            ->with([
                'variant:id,hash,owner_teacher_id,title,source,config_json',
                'answers:id,attempt_id,task_number,current_answer',
                'scorings:id,attempt_id,task_number,is_correct,correct_answer,checked_at',
            ])
            ->when($actor->role !== 'admin', function (EloquentBuilder $query) use ($actor) {
                $query->whereHas('variant', function (EloquentBuilder $variantQuery) use ($actor) {
                    $variantQuery->where('owner_teacher_id', $actor->id);
                });
            })
            ->orderByRaw('COALESCE(last_seen_at, submitted_at, started_at, updated_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();

        $summary = $this->buildStudentDrilldownSummary($attempts);
        $attemptHistory = $attempts->map(fn (OgeAttempt $attempt): array => $this->buildAttemptHistoryItem($attempt))->all();

        return view('teacher.students-show', [
            'student' => $student,
            'summary' => $summary,
            'attemptHistory' => $attemptHistory,
        ]);
    }

    private function decorateRosterRows(LengthAwarePaginator $students): void
    {
        $students->setCollection(
            $students->getCollection()->map(function ($student) {
                $student->oge_attempt_count = (int) ($student->oge_attempt_count ?? 0);
                $student->oge_correct_count = (int) ($student->oge_correct_count ?? 0);
                $student->oge_scored_count = (int) ($student->oge_scored_count ?? 0);
                $student->is_linked = (bool) ($student->is_linked ?? false);

                $student->oge_accuracy_percent = $student->oge_scored_count > 0
                    ? (int) round(($student->oge_correct_count / $student->oge_scored_count) * 100)
                    : null;

                $rawLastActivity = $student->oge_last_activity_at ?: $student->last_active_at;
                $student->roster_last_activity_at = $rawLastActivity ? Carbon::parse($rawLastActivity) : null;

                return $student;
            })
        );
    }

    /**
     * @param \Illuminate\Support\Collection<int, OgeAttempt> $attempts
     * @return array<string, mixed>
     */
    private function buildStudentDrilldownSummary($attempts): array
    {
        $attemptCount = $attempts->count();
        $correctCount = 0;
        $scoredCount = 0;
        $lastActivityAt = null;

        foreach ($attempts as $attempt) {
            foreach ($attempt->scorings as $scoring) {
                if ($scoring->is_correct === null) {
                    continue;
                }

                $scoredCount++;
                if ($scoring->is_correct) {
                    $correctCount++;
                }
            }

            $candidate = $this->resolveAttemptActivityAt($attempt);
            if ($candidate && (!$lastActivityAt || $candidate->gt($lastActivityAt))) {
                $lastActivityAt = $candidate;
            }
        }

        return [
            'attempts' => $attemptCount,
            'correct' => $correctCount,
            'scored' => $scoredCount,
            'accuracy_percent' => $scoredCount > 0 ? (int) round(($correctCount / $scoredCount) * 100) : null,
            'last_activity_at' => $lastActivityAt,
        ];
    }

    private function buildAttemptHistoryItem(OgeAttempt $attempt): array
    {
        $answersByTask = $attempt->answers->keyBy(fn ($answer) => (int) $answer->task_number);
        $taskDefinitions = $this->resolveVariantTaskDefinitions($attempt);

        $wrongTasks = $attempt->scorings
            ->filter(fn ($scoring) => $scoring->is_correct === false)
            ->sortBy(fn ($scoring) => (int) $scoring->task_number)
            ->values()
            ->map(function ($scoring) use ($attempt, $answersByTask, $taskDefinitions): array {
                $attemptTaskNumber = (int) $scoring->task_number;
                $definition = $taskDefinitions[$attemptTaskNumber] ?? null;
                $displayTaskNumber = (int) ($definition['display_task_number'] ?? $attemptTaskNumber);
                $taskText = $definition['task_text'] ?? null;
                $debugId = sprintf(
                    'attempt:%d variant:%s task:%d',
                    (int) $attempt->id,
                    (string) ($attempt->variant?->hash ?? ('id-' . (int) ($attempt->variant?->id ?? 0))),
                    $attemptTaskNumber
                );

                return [
                    'attempt_task_number' => $attemptTaskNumber,
                    'display_task_number' => $displayTaskNumber,
                    'task_text' => is_string($taskText) && trim($taskText) !== ''
                        ? trim($taskText)
                        : 'Текст задания недоступен (debug: ' . $debugId . ')',
                    'task_visual' => is_array($definition['task_visual'] ?? null) ? $definition['task_visual'] : null,
                    'task_debug_id' => $debugId,
                    'student_answer' => (string) (($answersByTask->get($attemptTaskNumber)->current_answer ?? '') ?: '—'),
                    'correct_answer' => (string) (($scoring->correct_answer ?? '') ?: '—'),
                ];
            })
            ->all();

        $scoredCount = $attempt->scorings->filter(fn ($scoring) => $scoring->is_correct !== null)->count();
        $correctCount = $attempt->scorings->filter(fn ($scoring) => $scoring->is_correct === true)->count();
        $accuracyPercent = $scoredCount > 0 ? (int) round(($correctCount / $scoredCount) * 100) : null;

        return [
            'id' => (int) $attempt->id,
            'variant_title' => $attempt->variant?->title ?: ('Вариант ' . ($attempt->variant?->hash ?? '—')),
            'variant_hash' => (string) ($attempt->variant?->hash ?? ''),
            'status' => (string) ($attempt->status ?? ''),
            'submitted_at' => $attempt->submitted_at,
            'activity_at' => $this->resolveAttemptActivityAt($attempt),
            'scored_count' => $scoredCount,
            'correct_count' => $correctCount,
            'accuracy_percent' => $accuracyPercent,
            'wrong_tasks' => $wrongTasks,
        ];
    }

    private function resolveAttemptActivityAt(OgeAttempt $attempt): ?Carbon
    {
        foreach (['last_seen_at', 'submitted_at', 'started_at', 'updated_at', 'created_at'] as $column) {
            $value = $attempt->{$column} ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            if ($value) {
                return Carbon::parse($value);
            }
        }

        return null;
    }

    /**
     * @return array<int, array{display_task_number:int, task_text:?string, task_visual:?array}>
     */
    private function resolveVariantTaskDefinitions(OgeAttempt $attempt): array
    {
        $variant = $attempt->variant;
        $config = is_array($variant?->config_json ?? null) ? $variant->config_json : [];
        $definitions = [];

        if ($variant?->isCustomRandom()) {
            $customTasks = $config['custom_tasks'] ?? [];
            if ((!is_array($customTasks) || empty($customTasks)) && is_string($variant?->hash) && $variant->hash !== '') {
                $customTasks = $this->loadCustomRandomTasksByHash($variant->hash);
            }

            if (is_array($customTasks)) {
                foreach ($customTasks as $index => $taskData) {
                    if (!is_array($taskData)) {
                        continue;
                    }

                    $attemptTaskNumber = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($index + 1));
                    if ($attemptTaskNumber < 1 || $attemptTaskNumber > 255) {
                        continue;
                    }

                    $presentation = $this->resolveTaskPresentation($taskData);
                    $definitions[$attemptTaskNumber] = [
                        'display_task_number' => (int) ($taskData['zadanie_number'] ?? $taskData['real_task_number'] ?? $attemptTaskNumber),
                        'task_text' => $presentation['task_text'],
                        'task_visual' => $presentation['task_visual'],
                    ];
                }
            }

            return $definitions;
        }

        $hash = (string) ($variant?->hash ?? '');
        if ($hash === '') {
            return $definitions;
        }

        try {
            $selected = $config['zadaniya'] ?? null;
            $payload = app(OgeVariantBuilderService::class)->build($hash, is_array($selected) ? $selected : null);
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve generator variant payload for teacher student drilldown', [
                'variant_id' => (int) ($variant?->id ?? 0),
                'variant_hash' => $hash,
                'error' => $e->getMessage(),
            ]);

            return $definitions;
        }

        foreach (($payload['tasks'] ?? []) as $index => $taskData) {
            if (!is_array($taskData)) {
                continue;
            }

            $attemptTaskNumber = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? (6 + $index));
            if ($attemptTaskNumber < 1 || $attemptTaskNumber > 255) {
                continue;
            }

            $presentation = $this->resolveTaskPresentation($taskData);
            $definitions[$attemptTaskNumber] = [
                'display_task_number' => (int) ($taskData['zadanie_number'] ?? $taskData['task_number'] ?? $attemptTaskNumber),
                'task_text' => $presentation['task_text'],
                'task_visual' => $presentation['task_visual'],
            ];
        }

        return $definitions;
    }

    private function resolveTaskText(array $taskData): ?string
    {
        $task = $taskData['task'] ?? null;
        $lines = [];

        foreach ([
            is_array($task) ? ($task['example'] ?? null) : null,
            is_array($task) ? ($task['text'] ?? null) : null,
            is_array($task) ? ($task['question'] ?? null) : null,
            is_array($task) ? ($task['expression'] ?? null) : null,
            $taskData['example'] ?? null,
            $taskData['text'] ?? null,
            $taskData['question'] ?? null,
            $taskData['expression'] ?? null,
            $taskData['instruction'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $lines[] = trim($candidate);
            }
        }

        foreach ([$taskData['options'] ?? null, is_array($task) ? ($task['options'] ?? null) : null] as $options) {
            $rendered = $this->renderOptionsText($options);
            if ($rendered !== null) {
                $lines[] = $rendered;
                break;
            }
        }

        $lines = array_values(array_unique(array_filter($lines, fn ($line) => is_string($line) && $line !== '')));

        if (!empty($lines)) {
            return implode("\n", $lines);
        }

        return null;
    }

    /**
     * @return array{task_text:?string, task_visual:?array}
     */
    private function resolveTaskPresentation(array $taskData): array
    {
        return [
            'task_text' => $this->resolveTaskText($taskData),
            'task_visual' => $this->resolveTaskVisual($taskData),
        ];
    }

    /**
     * @return array{type:string, content?:string, url?:string, ref?:string}|null
     */
    private function resolveTaskVisual(array $taskData): ?array
    {
        $task = is_array($taskData['task'] ?? null) ? $taskData['task'] : [];

        foreach ([$task['svg'] ?? null, $taskData['svg'] ?? null] as $svg) {
            if (is_string($svg) && trim($svg) !== '') {
                return ['type' => 'svg', 'content' => trim($svg)];
            }
        }

        foreach ([$task['image'] ?? null, $taskData['image'] ?? null] as $image) {
            if (!is_string($image) || trim($image) === '') {
                continue;
            }

            $image = trim($image);
            if (Str::startsWith($image, '<svg')) {
                return ['type' => 'svg', 'content' => $image];
            }

            if (Str::startsWith($image, ['http://', 'https://', '/'])) {
                return ['type' => 'image', 'url' => $image, 'ref' => $image];
            }

            $topicId = $this->resolveTaskTopicId($taskData, $task);
            if ($topicId !== null) {
                return [
                    'type' => 'image',
                    'url' => asset("images/tasks/{$topicId}/{$image}"),
                    'ref' => "images/tasks/{$topicId}/{$image}",
                ];
            }

            return ['type' => 'image_ref', 'ref' => $image];
        }

        return null;
    }

    private function resolveTaskTopicId(array $taskData, array $task): ?string
    {
        foreach ([
            $taskData['topic_id'] ?? null,
            $task['topic_id'] ?? null,
            $taskData['topicId'] ?? null,
            $task['topicId'] ?? null,
        ] as $candidate) {
            if ($candidate === null) {
                continue;
            }

            $topicId = str_pad((string) $candidate, 2, '0', STR_PAD_LEFT);
            if (preg_match('/^\d{2}$/', $topicId) === 1) {
                return $topicId;
            }
        }

        return null;
    }

    private function renderOptionsText(mixed $options): ?string
    {
        if (!is_array($options) || empty($options)) {
            return null;
        }

        $parts = [];
        foreach ($options as $option) {
            if (is_string($option) && trim($option) !== '') {
                $parts[] = trim($option);
                continue;
            }

            if (!is_array($option)) {
                continue;
            }

            foreach (['text', 'label', 'value', 'option'] as $key) {
                $value = $option[$key] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    $parts[] = trim($value);
                    break;
                }
            }
        }

        $parts = array_values(array_unique($parts));
        if (empty($parts)) {
            return null;
        }

        return implode(', ', $parts);
    }

    /**
     * @return array<int, mixed>
     */
    private function loadCustomRandomTasksByHash(string $hash): array
    {
        $cacheKey = "custom_random_test_{$hash}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $path = "custom_random_tests/{$hash}.json";
        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Failed to read custom random task payload for teacher student drilldown', [
                'variant_hash' => $hash,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (!is_array($decoded) || empty($decoded)) {
            return [];
        }

        Cache::put($cacheKey, $decoded, now()->addDays(7));

        return $decoded;
    }
}
