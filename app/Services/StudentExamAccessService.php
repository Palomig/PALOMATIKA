<?php

namespace App\Services;

use App\Models\OgeVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentExamAccessService
{
    /**
     * @return array<int, string>
     */
    public function allowedExamTypesFor(?User $user): array
    {
        if (!$user) {
            return [];
        }

        if ($user->role !== 'student') {
            return [
                OgeVariant::EXAM_OGE,
                OgeVariant::EXAM_VPR5,
                OgeVariant::EXAM_VPR6,
                OgeVariant::EXAM_VPR7,
                OgeVariant::EXAM_VPR8,
                OgeVariant::EXAM_EGE,
            ];
        }

        return match ((int) ($user->grade_num ?? 0)) {
            5 => [OgeVariant::EXAM_VPR5],
            6 => [OgeVariant::EXAM_VPR6],
            7 => [OgeVariant::EXAM_VPR7],
            8 => [OgeVariant::EXAM_VPR8, OgeVariant::EXAM_OGE],
            9 => [OgeVariant::EXAM_OGE],
            10, 11 => [OgeVariant::EXAM_EGE],
            default => [],
        };
    }

    public function canAccessExamType(?User $user, ?string $examType): bool
    {
        if (!$user || !$examType) {
            return false;
        }

        return in_array($examType, $this->allowedExamTypesFor($user), true);
    }

    public function canAccessVariant(?User $user, ?OgeVariant $variant): bool
    {
        return $variant !== null && $this->canAccessExamType($user, $variant->exam_type);
    }

    public function applyAttemptAccessScope(Builder $query, ?User $user, string $relation = 'variant'): Builder
    {
        $allowedExamTypes = $this->allowedExamTypesFor($user);
        if ($allowedExamTypes === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas($relation, function (Builder $variantQuery) use ($allowedExamTypes) {
            $variantQuery->whereIn('exam_type', $allowedExamTypes);
        });
    }
}
