<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\View\View;

class OgeReviewController extends Controller
{
    public function teachers(): View
    {
        $teachers = User::query()
            ->whereIn('role', ['teacher', 'admin'])
            ->withCount('ownedOgeVariants')
            ->orderBy('name')
            ->get();

        // Virtual bucket for mini-app generated variants (owner_teacher_id = null, source = miniapp)
        $miniappCount = OgeVariant::query()
            ->where('source', OgeVariant::SOURCE_MINIAPP)
            ->count();

        $teachers->push((object) [
            'id' => 0,
            'name' => 'MiniApp (автогенерация)',
            'email' => 'system@palomatika',
            'owned_oge_variants_count' => $miniappCount,
        ]);

        return view('teacher.oge.teachers', compact('teachers'));
    }

    public function variants(int $teacherId): View
    {
        if ($teacherId === 0) {
            $teacher = (object) [
                'id' => 0,
                'name' => 'MiniApp (автогенерация)',
                'email' => 'system@palomatika',
            ];

            $variants = OgeVariant::query()
                ->where('source', OgeVariant::SOURCE_MINIAPP)
                ->withCount('attempts')
                ->orderByDesc('created_at')
                ->get();

            return view('teacher.oge.variants', compact('teacher', 'variants'));
        }

        $teacher = User::findOrFail($teacherId);

        $variants = OgeVariant::query()
            ->where('owner_teacher_id', $teacherId)
            ->withCount('attempts')
            ->orderByDesc('created_at')
            ->get();

        return view('teacher.oge.variants', compact('teacher', 'variants'));
    }

    public function results(int $variantId): View
    {
        $variant = OgeVariant::query()
            ->with('ownerTeacher')
            ->findOrFail($variantId);

        $attempts = $variant->attempts()
            ->with(['student:id,name,email', 'answers', 'taskTimings', 'scorings'])
            ->orderByDesc('created_at')
            ->get();

        $taskColumns = $this->resolveTaskColumns($variant, $attempts);
        $resultsMatrix = $this->buildResultsMatrix($attempts, $taskColumns);

        return view('teacher.oge.results', compact('variant', 'attempts', 'taskColumns', 'resultsMatrix'));
    }

    private function resolveTaskColumns(OgeVariant $variant, $attempts): array
    {
        if (!$variant->isCustomRandom()) {
            return array_map(
                fn (int $taskNumber): array => [
                    'display_task_number' => $taskNumber,
                    'attempt_task_number' => $taskNumber,
                ],
                range(6, 19)
            );
        }

        $columnsByDisplayTaskNumber = [];
        $hasVariantTaskDefinition = false;
        $config = is_array($variant->config_json ?? null) ? $variant->config_json : [];
        $observedAttemptTaskNumbers = $attempts
            ->flatMap(fn ($attempt) => collect([$attempt->answers, $attempt->taskTimings, $attempt->scorings]))
            ->flatten(1)
            ->pluck('task_number')
            ->map(fn ($number) => (int) $number)
            ->filter(fn (int $number): bool => $number > 0 && $number <= 255)
            ->unique()
            ->values()
            ->all();
        $observedAttemptTaskNumberSet = array_fill_keys($observedAttemptTaskNumbers, true);

        $customTasks = $config['custom_tasks'] ?? [];
        if (is_array($customTasks)) {
            foreach ($customTasks as $index => $taskData) {
                if (!is_array($taskData)) {
                    continue;
                }

                $displayTaskNumber = (int) ($taskData['zadanie_number'] ?? 0);
                if ($displayTaskNumber < 1 || $displayTaskNumber > 255) {
                    continue;
                }

                $attemptTaskNumber = (int) ($taskData['attempt_task_number'] ?? $taskData['task_number'] ?? $taskData['test_number'] ?? ($index + 1));
                if ($attemptTaskNumber < 1 || $attemptTaskNumber > 255) {
                    continue;
                }

                $hasVariantTaskDefinition = true;
                $columnsByDisplayTaskNumber[$displayTaskNumber] = [
                    'display_task_number' => $displayTaskNumber,
                    'attempt_task_number' => $attemptTaskNumber,
                ];
            }
        }

        $configNumbers = $config['custom_task_numbers'] ?? [];
        if (is_array($configNumbers)) {
            foreach (array_values($configNumbers) as $index => $number) {
                $displayTaskNumber = (int) $number;
                if ($displayTaskNumber < 1 || $displayTaskNumber > 255) {
                    continue;
                }

                $hasVariantTaskDefinition = true;
                $columnsByDisplayTaskNumber[$displayTaskNumber] ??= [
                    'display_task_number' => $displayTaskNumber,
                    'attempt_task_number' => isset($observedAttemptTaskNumberSet[$displayTaskNumber])
                        ? $displayTaskNumber
                        : ((int) $index + 1),
                ];
            }
        }

        if (!$hasVariantTaskDefinition) {
            foreach ($observedAttemptTaskNumbers as $taskNumber) {
                $columnsByDisplayTaskNumber[$taskNumber] = [
                    'display_task_number' => $taskNumber,
                    'attempt_task_number' => $taskNumber,
                ];
            }
        }

        ksort($columnsByDisplayTaskNumber, SORT_NUMERIC);

        return array_values($columnsByDisplayTaskNumber);
    }

    private function buildResultsMatrix($attempts, array $taskColumns): array
    {
        $studentColumns = [];
        $marksByAttemptAndTask = [];

        foreach ($attempts as $attempt) {
            $attemptId = (int) $attempt->id;
            $scoringsByTask = $attempt->scorings->keyBy(fn ($scoring) => (int) $scoring->task_number);

            $studentColumns[] = [
                'attempt_id' => $attemptId,
                'student_name' => $attempt->student->name ?? '—',
                'student_short_name' => $this->formatStudentShortName($attempt->student->name ?? null),
                'status' => (string) ($attempt->status ?? ''),
            ];

            foreach ($taskColumns as $taskColumn) {
                $displayTaskNumber = (int) ($taskColumn['display_task_number'] ?? 0);
                $attemptTaskNumber = (int) ($taskColumn['attempt_task_number'] ?? 0);
                $scoring = $scoringsByTask->get($attemptTaskNumber);
                $isCorrect = $scoring?->is_correct;

                $marksByAttemptAndTask[$attemptId][$displayTaskNumber] = [
                    'is_correct' => is_null($isCorrect) ? null : (bool) $isCorrect,
                    'mark' => $this->resolveCorrectnessMark($isCorrect),
                ];
            }
        }

        $rows = [];
        foreach ($taskColumns as $taskColumn) {
            $displayTaskNumber = (int) ($taskColumn['display_task_number'] ?? 0);
            $cells = [];

            foreach ($studentColumns as $studentColumn) {
                $attemptId = $studentColumn['attempt_id'];
                $cells[] = $marksByAttemptAndTask[$attemptId][$displayTaskNumber] ?? [
                    'is_correct' => null,
                    'mark' => '.',
                ];
            }

            $rows[] = [
                'task_number' => $displayTaskNumber,
                'cells' => $cells,
            ];
        }

        return [
            'students' => $studentColumns,
            'rows' => $rows,
        ];
    }

    private function resolveCorrectnessMark(mixed $isCorrect): string
    {
        if ($isCorrect === true) {
            return '+';
        }

        if ($isCorrect === false) {
            return '-';
        }

        return '.';
    }

    private function formatStudentShortName(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '—';
        }

        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        $short = $this->sliceText($first, 2);

        if ($second !== '') {
            $short .= $this->sliceText($second, 1);
        }

        if ($short === '') {
            return $this->sliceText($first, 1) ?: '—';
        }

        return $short;
    }

    private function sliceText(string $value, int $length): string
    {
        if ($value === '' || $length <= 0) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
