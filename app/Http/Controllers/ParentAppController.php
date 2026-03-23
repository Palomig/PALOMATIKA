<?php

namespace App\Http\Controllers;

use App\Models\LessonAttendance;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentAppController extends Controller
{
    /**
     * Дашборд родителя — карточки привязанных детей с кратким саммари.
     */
    public function dashboard()
    {
        $parent = Auth::user();
        $children = $parent->children()->get();

        $childSummaries = $children->map(function (User $child) {
            $skills = UserSkill::where('user_id', $child->id)->get();
            $weakCount = $skills->where('weight', '<', 0.5)->count();
            $avgWeight = round($skills->avg('weight') ?? 0, 2);

            $lastAttendance = LessonAttendance::where('student_id', $child->id)
                ->orderByDesc('lesson_date')
                ->first();

            $pendingHomework = $child->homeworkAssignments()
                ->whereNull('completed_at')
                ->count();

            return [
                'child' => $child,
                'avg_skill_weight' => $avgWeight,
                'weak_skills_count' => $weakCount,
                'total_skills' => $skills->count(),
                'last_attendance' => $lastAttendance,
                'pending_homework' => $pendingHomework,
                'diagnostic_completed' => $skills->isNotEmpty(),
            ];
        });

        return view('parent.dashboard', [
            'childSummaries' => $childSummaries,
        ]);
    }

    /**
     * Детальное дерево навыков конкретного ребёнка.
     */
    public function childSkills(int $studentId)
    {
        $parent = Auth::user();
        $child = $parent->children()->findOrFail($studentId);

        $userSkills = UserSkill::where('user_id', $child->id)
            ->with('skill.parent')
            ->orderBy('weight', 'asc')
            ->get();

        $byCategory = $userSkills->groupBy(fn ($us) => $us->skill->category ?? $us->skill->parent?->name ?? 'Другое');

        return view('parent.child-skills', [
            'child' => $child,
            'byCategory' => $byCategory,
            'weakSkills' => $userSkills->where('weight', '<', 0.5)->values(),
            'strongSkills' => $userSkills->where('weight', '>=', 0.7)->values(),
            'averageWeight' => round($userSkills->avg('weight') ?? 0, 3),
        ]);
    }

    /**
     * История посещаемости ребёнка.
     */
    public function childAttendance(int $studentId)
    {
        $parent = Auth::user();
        $child = $parent->children()->findOrFail($studentId);

        $attendance = LessonAttendance::where('student_id', $child->id)
            ->orderByDesc('lesson_date')
            ->paginate(30);

        $stats = [
            'total' => LessonAttendance::where('student_id', $child->id)->count(),
            'present' => LessonAttendance::where('student_id', $child->id)->where('status', 'present')->count(),
            'absent' => LessonAttendance::where('student_id', $child->id)->where('status', 'absent')->count(),
            'late' => LessonAttendance::where('student_id', $child->id)->where('status', 'late')->count(),
        ];

        return view('parent.child-attendance', [
            'child' => $child,
            'attendance' => $attendance,
            'stats' => $stats,
        ]);
    }

    /**
     * Статус домашних заданий ребёнка.
     */
    public function childHomework(int $studentId)
    {
        $parent = Auth::user();
        $child = $parent->children()->findOrFail($studentId);

        $assignments = $child->homeworkAssignments()
            ->with('homework')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('parent.child-homework', [
            'child' => $child,
            'assignments' => $assignments,
        ]);
    }
}
