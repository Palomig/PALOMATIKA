<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(): JsonResponse
    {
        $skills = Skill::active()
            ->roots()
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['skills' => $skills]);
    }

    public function show(Skill $skill): JsonResponse
    {
        $skill->load(['parent', 'children', 'dependencies']);

        return response()->json(['skill' => $skill]);
    }

    public function userProgress(Request $request): JsonResponse
    {
        $user = $request->user();

        $userSkills = $user->skills()
            ->with('skill')
            ->orderByDesc('weight')
            ->get()
            ->map(fn($us) => [
                'skill' => $us->skill,
                'weight' => round($us->weight, 3),
                'mastery_level' => $us->mastery_level,
                'accuracy' => $us->accuracy,
                'attempts_count' => $us->attempts_count,
                'last_practiced_at' => $us->last_practiced_at,
            ]);

        $strongSkills = $userSkills->where('weight', '>=', 0.7)->values();
        $weakSkills = $userSkills->where('weight', '<', 0.5)->values();

        return response()->json([
            'skills' => $userSkills,
            'strong_skills' => $strongSkills,
            'weak_skills' => $weakSkills,
            'average_weight' => round($userSkills->avg('weight') ?? 0, 3),
        ]);
    }

    public function byCategory(): JsonResponse
    {
        $skills = Skill::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return response()->json(['skills_by_category' => $skills]);
    }
}
