<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StudentsController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $search = trim((string) $request->query('search', ''));

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
            ->where('users.role', 'student')
            ->whereExists(function (Builder $query) use ($actor) {
                $query->selectRaw('1')
                    ->from('teacher_students')
                    ->whereColumn('teacher_students.student_id', 'users.id');

                if ($actor->role !== 'admin') {
                    $query->where('teacher_students.teacher_id', $actor->id);
                }
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
        ]);
    }

    public function show(Request $request, int $id): View
    {
        return $this->index($request);
    }

    private function decorateRosterRows(LengthAwarePaginator $students): void
    {
        $students->setCollection(
            $students->getCollection()->map(function ($student) {
                $student->oge_attempt_count = (int) ($student->oge_attempt_count ?? 0);
                $student->oge_correct_count = (int) ($student->oge_correct_count ?? 0);
                $student->oge_scored_count = (int) ($student->oge_scored_count ?? 0);

                $student->oge_accuracy_percent = $student->oge_scored_count > 0
                    ? (int) round(($student->oge_correct_count / $student->oge_scored_count) * 100)
                    : null;

                $rawLastActivity = $student->oge_last_activity_at ?: $student->last_active_at;
                $student->roster_last_activity_at = $rawLastActivity ? Carbon::parse($rawLastActivity) : null;

                return $student;
            })
        );
    }
}
