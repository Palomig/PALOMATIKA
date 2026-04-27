<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PracticeLeaderboardService
{
    public const TOP_LIMIT = 20;

    public function topRuns(string $slug, string $scope, string $period, User $viewer): array
    {
        $base = $this->scopedQuery($slug, $period, $scope, $viewer);

        if ($base === null) {
            return [
                'available' => false,
                'entries' => [],
                'viewer_entry' => null,
            ];
        }

        $rows = (clone $base)
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.grade_num as grade_num',
                'users.grade_letter as grade_letter',
                DB::raw('MAX(pgr.score) as best_score'),
                DB::raw('MIN(pgr.ended_at) as first_at'),
            )
            ->groupBy('users.id', 'users.name', 'users.grade_num', 'users.grade_letter')
            ->orderByDesc('best_score')
            ->orderBy('first_at')
            ->limit(self::TOP_LIMIT)
            ->get();

        $entries = $rows->values()->map(fn ($row, int $idx) => [
            'rank' => $idx + 1,
            'user_id' => (int) $row->user_id,
            'name' => $this->formatName((string) $row->user_name),
            'class' => $this->formatClass($row->grade_num, $row->grade_letter),
            'score' => (int) $row->best_score,
            'is_viewer' => (int) $row->user_id === $viewer->id,
        ])->all();

        $viewerInTop = collect($entries)->contains(fn ($e) => $e['is_viewer']);
        $viewerEntry = $viewerInTop ? null : $this->viewerEntry($base, $viewer);

        return [
            'available' => true,
            'entries' => $entries,
            'viewer_entry' => $viewerEntry,
        ];
    }

    public function availableScopes(User $viewer): array
    {
        return [
            'all' => true,
            'school' => $this->hasSchool($viewer),
            'class' => $this->hasClass($viewer),
        ];
    }

    private function scopedQuery(string $slug, string $period, string $scope, User $viewer): ?Builder
    {
        if ($scope === 'class' && !$this->hasClass($viewer)) {
            return null;
        }
        if ($scope === 'school' && !$this->hasSchool($viewer)) {
            return null;
        }

        $query = DB::table('practice_game_runs as pgr')
            ->join('users', 'users.id', '=', 'pgr.user_id')
            ->where('pgr.slug', $slug)
            ->whereNotNull('pgr.ended_at')
            ->where('pgr.score', '>', 0)
            ->where('users.role', 'student');

        if ($period === 'week') {
            $query->where('pgr.ended_at', '>=', now()->startOfWeek());
        }

        if ($scope === 'class') {
            $query->where('users.school_number', $viewer->school_number)
                ->where('users.grade_num', $viewer->grade_num)
                ->where('users.grade_letter', $viewer->grade_letter);
            if ($viewer->city) {
                $query->where('users.city', $viewer->city);
            }
        } elseif ($scope === 'school') {
            $query->where('users.school_number', $viewer->school_number);
            if ($viewer->city) {
                $query->where('users.city', $viewer->city);
            }
        }

        return $query;
    }

    private function viewerEntry(Builder $base, User $viewer): ?array
    {
        $bestRow = (clone $base)
            ->where('pgr.user_id', $viewer->id)
            ->select(
                DB::raw('MAX(pgr.score) as best_score'),
                DB::raw('MIN(pgr.ended_at) as first_at'),
            )
            ->first();

        if (!$bestRow || $bestRow->best_score === null) {
            return null;
        }

        $bestScore = (int) $bestRow->best_score;
        $firstAt = $bestRow->first_at;

        $aheadCount = (clone $base)
            ->where(function ($q) use ($bestScore, $firstAt) {
                $q->where('pgr.score', '>', $bestScore)
                    ->orWhere(function ($q2) use ($bestScore, $firstAt) {
                        $q2->where('pgr.score', '=', $bestScore)
                            ->where('pgr.ended_at', '<', $firstAt);
                    });
            })
            ->distinct()
            ->count('pgr.user_id');

        return [
            'rank' => $aheadCount + 1,
            'user_id' => $viewer->id,
            'name' => $this->formatName((string) $viewer->name),
            'class' => $this->formatClass($viewer->grade_num, $viewer->grade_letter),
            'score' => $bestScore,
            'is_viewer' => true,
        ];
    }

    private function hasSchool(User $viewer): bool
    {
        return !empty($viewer->school_number);
    }

    private function hasClass(User $viewer): bool
    {
        return $this->hasSchool($viewer)
            && $viewer->grade_num !== null
            && !empty($viewer->grade_letter);
    }

    private function formatName(string $fullName): string
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return 'Без имени';
        }

        $parts = preg_split('/\s+/u', $fullName) ?: [$fullName];
        if (count($parts) === 1) {
            return $parts[0];
        }

        $first = $parts[0];
        $lastInitial = mb_substr($parts[1], 0, 1);

        return "{$first} {$lastInitial}.";
    }

    private function formatClass(mixed $gradeNum, mixed $gradeLetter): ?string
    {
        if ($gradeNum === null || $gradeLetter === null || $gradeLetter === '') {
            return null;
        }

        return ((int) $gradeNum) . mb_strtoupper((string) $gradeLetter);
    }
}
