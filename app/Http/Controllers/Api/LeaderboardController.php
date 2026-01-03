<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\LeagueParticipant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function weekly(Request $request): JsonResponse
    {
        $user = $request->user();
        $weekStart = now()->startOfWeek();

        // Get user's current league
        $participation = LeagueParticipant::where('user_id', $user->id)
            ->where('week_start', $weekStart)
            ->with('league')
            ->first();

        if (!$participation) {
            // Create participation in bronze league
            $bronzeLeague = League::where('slug', 'bronze')->first();
            if ($bronzeLeague) {
                $participation = LeagueParticipant::create([
                    'user_id' => $user->id,
                    'league_id' => $bronzeLeague->id,
                    'week_start' => $weekStart,
                ]);
                $participation->load('league');
            }
        }

        // Get leaderboard for user's league
        $leaderboard = [];
        if ($participation) {
            $leaderboard = LeagueParticipant::where('league_id', $participation->league_id)
                ->where('week_start', $weekStart)
                ->with('user:id,name,avatar')
                ->orderByDesc('xp_earned')
                ->take(50)
                ->get()
                ->map(fn($p, $i) => [
                    'rank' => $i + 1,
                    'user' => $p->user,
                    'xp_earned' => $p->xp_earned,
                    'is_current_user' => $p->user_id === $user->id,
                ]);
        }

        return response()->json([
            'league' => $participation?->league,
            'user_xp' => $participation?->xp_earned ?? 0,
            'user_rank' => $leaderboard->firstWhere('is_current_user')?->get('rank'),
            'leaderboard' => $leaderboard,
            'week_ends_at' => now()->endOfWeek(),
        ]);
    }

    public function allTime(): JsonResponse
    {
        $leaderboard = User::select('id', 'name', 'avatar')
            ->withCount(['attempts as total_xp' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(xp_earned), 0)'));
            }])
            ->orderByDesc('total_xp')
            ->take(100)
            ->get()
            ->map(fn($u, $i) => [
                'rank' => $i + 1,
                'user' => $u,
                'total_xp' => $u->total_xp,
            ]);

        return response()->json(['leaderboard' => $leaderboard]);
    }

    public function leagues(): JsonResponse
    {
        $leagues = League::ordered()
            ->withCount(['participants' => function ($query) {
                $query->where('week_start', now()->startOfWeek());
            }])
            ->get();

        return response()->json(['leagues' => $leagues]);
    }
}
