<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function index(): JsonResponse
    {
        $badges = Badge::active()
            ->orderBy('rarity')
            ->orderBy('condition_type')
            ->get()
            ->groupBy('rarity');

        return response()->json(['badges' => $badges]);
    }

    public function userBadges(Request $request): JsonResponse
    {
        $user = $request->user();

        $earnedBadges = $user->badges()
            ->with('badge')
            ->orderByDesc('earned_at')
            ->get();

        $showcasedBadges = $earnedBadges->where('is_showcased', true);

        $allBadges = Badge::active()->get();
        $earnedIds = $earnedBadges->pluck('badge_id');

        $availableBadges = $allBadges->reject(fn($b) => $earnedIds->contains($b->id));

        return response()->json([
            'earned' => $earnedBadges,
            'showcased' => $showcasedBadges,
            'available' => $availableBadges,
            'total_earned' => $earnedBadges->count(),
            'total_available' => $allBadges->count(),
        ]);
    }

    public function toggleShowcase(Request $request, int $userBadgeId): JsonResponse
    {
        $user = $request->user();
        $userBadge = $user->badges()->findOrFail($userBadgeId);

        // Limit showcased badges to 5
        if (!$userBadge->is_showcased) {
            $showcasedCount = $user->badges()->where('is_showcased', true)->count();
            if ($showcasedCount >= 5) {
                return response()->json([
                    'message' => 'Можно выставить не более 5 бейджей'
                ], 400);
            }
        }

        $userBadge->update(['is_showcased' => !$userBadge->is_showcased]);

        return response()->json(['badge' => $userBadge]);
    }

    public function checkAndAward(Request $request): JsonResponse
    {
        $user = $request->user();
        $newBadges = [];

        // Check streak badges
        $streak = $user->streak;
        if ($streak) {
            $streakBadges = Badge::active()
                ->where('condition_type', 'streak')
                ->where('condition_value', '<=', $streak->current_streak)
                ->get();

            foreach ($streakBadges as $badge) {
                if (!$user->badges()->where('badge_id', $badge->id)->exists()) {
                    $user->badges()->create([
                        'badge_id' => $badge->id,
                        'earned_at' => now(),
                    ]);
                    $newBadges[] = $badge;
                }
            }
        }

        // Check tasks badges
        $totalCorrect = $user->attempts()->where('is_correct', true)->count();
        $tasksBadges = Badge::active()
            ->where('condition_type', 'tasks')
            ->where('condition_value', '<=', $totalCorrect)
            ->get();

        foreach ($tasksBadges as $badge) {
            if (!$user->badges()->where('badge_id', $badge->id)->exists()) {
                $user->badges()->create([
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);
                $newBadges[] = $badge;
            }
        }

        return response()->json([
            'new_badges' => $newBadges,
            'total_badges' => $user->badges()->count(),
        ]);
    }
}
