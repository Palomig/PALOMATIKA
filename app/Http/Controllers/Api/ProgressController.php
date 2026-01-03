<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDailyStat;
use App\Models\UserStreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Today stats
        $todayStats = UserDailyStat::getOrCreateForDate($user->id);

        // Week stats
        $weekStart = now()->startOfWeek();
        $weekStats = $user->dailyStats()
            ->where('date', '>=', $weekStart)
            ->get();

        // Streak
        $streak = $user->streak ?? UserStreak::create(['user_id' => $user->id]);

        // Recent activity
        $recentAttempts = $user->attempts()
            ->with('task.topic')
            ->latest('created_at')
            ->take(10)
            ->get();

        return response()->json([
            'today' => [
                'tasks_completed' => $todayStats->tasks_completed,
                'tasks_correct' => $todayStats->tasks_correct,
                'accuracy' => $todayStats->accuracy,
                'xp_earned' => $todayStats->xp_earned,
                'active_minutes' => $todayStats->active_minutes,
            ],
            'week' => [
                'tasks_completed' => $weekStats->sum('tasks_completed'),
                'tasks_correct' => $weekStats->sum('tasks_correct'),
                'xp_earned' => $weekStats->sum('xp_earned'),
                'active_days' => $weekStats->count(),
            ],
            'streak' => [
                'current' => $streak->current_streak,
                'longest' => $streak->longest_streak,
                'is_protected' => $streak->isProtected(),
            ],
            'recent_attempts' => $recentAttempts,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'in:week,month,all',
            'page' => 'integer|min:1',
        ]);

        $user = $request->user();
        $period = $request->period ?? 'week';

        $query = $user->dailyStats()->orderByDesc('date');

        if ($period === 'week') {
            $query->where('date', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $query->where('date', '>=', now()->subMonth());
        }

        $stats = $query->paginate(30);

        return response()->json($stats);
    }

    public function topicProgress(Request $request): JsonResponse
    {
        $user = $request->user();

        $topicProgress = DB::table('attempts')
            ->join('tasks', 'attempts.task_id', '=', 'tasks.id')
            ->join('topics', 'tasks.topic_id', '=', 'topics.id')
            ->where('attempts.user_id', $user->id)
            ->where('attempts.is_completed', true)
            ->select(
                'topics.id',
                'topics.name',
                'topics.oge_number',
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('SUM(CASE WHEN attempts.is_correct = 1 THEN 1 ELSE 0 END) as correct_attempts'),
                DB::raw('ROUND(AVG(CASE WHEN attempts.is_correct = 1 THEN 100 ELSE 0 END), 1) as accuracy')
            )
            ->groupBy('topics.id', 'topics.name', 'topics.oge_number')
            ->orderBy('topics.oge_number')
            ->get();

        return response()->json(['topic_progress' => $topicProgress]);
    }

    public function updateStreak(Request $request): JsonResponse
    {
        $user = $request->user();
        $streak = $user->streak ?? UserStreak::create(['user_id' => $user->id]);

        $today = today();
        $lastActivity = $streak->last_activity_date;

        if (!$lastActivity || $lastActivity->lt($today)) {
            if ($lastActivity && $lastActivity->diffInDays($today) === 1) {
                // Continue streak
                $streak->current_streak++;
            } elseif (!$lastActivity || $lastActivity->diffInDays($today) > 1) {
                // Reset streak (unless protected)
                if (!$streak->isProtected()) {
                    $streak->current_streak = 1;
                }
            }

            $streak->last_activity_date = $today;
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
            $streak->save();
        }

        return response()->json([
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
        ]);
    }
}
