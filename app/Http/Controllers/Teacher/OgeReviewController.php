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

        return view('teacher.oge.teachers', compact('teachers'));
    }

    public function variants(int $teacherId): View
    {
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

        $taskNumbers = $this->resolveTaskNumbers($variant, $attempts);
        $resultsMatrix = $this->buildResultsMatrix($attempts, $taskNumbers);

        return view('teacher.oge.results', compact('variant', 'attempts', 'taskNumbers', 'resultsMatrix'));
    }

    private function resolveTaskNumbers(OgeVariant $variant, $attempts): array
    {
        if (!$variant->isCustomRandom()) {
            return range(6, 19);
        }

        $configNumbers = $variant->config_json['custom_task_numbers'] ?? [];
        $fromConfig = is_array($configNumbers)
            ? array_values(array_filter(array_map('intval', $configNumbers), fn (int $number): bool => $number > 0 && $number <= 255))
            : [];

        $fromAttempts = $attempts
            ->flatMap(fn ($attempt) => $attempt->answers->pluck('task_number'))
            ->map(fn ($number) => (int) $number)
            ->filter(fn (int $number): bool => $number > 0 && $number <= 255)
            ->values()
            ->all();

        $resolved = array_values(array_unique(array_merge($fromConfig, $fromAttempts)));
        sort($resolved);

        return !empty($resolved) ? $resolved : range(1, 19);
    }

    private function buildResultsMatrix($attempts, array $taskNumbers): array
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

            foreach ($taskNumbers as $taskNumber) {
                $scoring = $scoringsByTask->get((int) $taskNumber);
                $isCorrect = $scoring?->is_correct;

                $marksByAttemptAndTask[$attemptId][(int) $taskNumber] = [
                    'is_correct' => is_null($isCorrect) ? null : (bool) $isCorrect,
                    'mark' => $this->resolveCorrectnessMark($isCorrect),
                ];
            }
        }

        $rows = [];
        foreach ($taskNumbers as $taskNumber) {
            $cells = [];

            foreach ($studentColumns as $studentColumn) {
                $attemptId = $studentColumn['attempt_id'];
                $cells[] = $marksByAttemptAndTask[$attemptId][(int) $taskNumber] ?? [
                    'is_correct' => null,
                    'mark' => '.',
                ];
            }

            $rows[] = [
                'task_number' => (int) $taskNumber,
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
