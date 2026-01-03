<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Duel;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DuelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendingDuels = Duel::where('opponent_id', $user->id)
            ->where('status', 'pending')
            ->with('challenger:id,name,avatar')
            ->get();

        $activeDuels = Duel::where(function ($q) use ($user) {
            $q->where('challenger_id', $user->id)
                ->orWhere('opponent_id', $user->id);
        })
            ->where('status', 'active')
            ->with(['challenger:id,name,avatar', 'opponent:id,name,avatar'])
            ->get();

        $recentDuels = Duel::where(function ($q) use ($user) {
            $q->where('challenger_id', $user->id)
                ->orWhere('opponent_id', $user->id);
        })
            ->where('status', 'completed')
            ->with(['challenger:id,name,avatar', 'opponent:id,name,avatar', 'winner:id,name'])
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'pending_invites' => $pendingDuels,
            'active_duels' => $activeDuels,
            'recent_duels' => $recentDuels,
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'opponent_id' => 'required|exists:users,id|different:' . $request->user()->id,
            'topic_id' => 'nullable|exists:topics,id',
            'tasks_count' => 'integer|min:3|max:10',
        ]);

        $user = $request->user();

        $duel = Duel::create([
            'challenger_id' => $user->id,
            'opponent_id' => $request->opponent_id,
            'topic_id' => $request->topic_id,
            'tasks_count' => $request->tasks_count ?? 5,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
        ]);

        // Select random tasks
        $tasksQuery = Task::active();
        if ($request->topic_id) {
            $tasksQuery->where('topic_id', $request->topic_id);
        }

        $tasks = $tasksQuery->inRandomOrder()
            ->take($duel->tasks_count)
            ->get();

        foreach ($tasks as $order => $task) {
            $duel->tasks()->attach($task->id, ['task_order' => $order]);
        }

        return response()->json([
            'duel' => $duel->load(['opponent:id,name,avatar', 'tasks']),
        ], 201);
    }

    public function accept(Request $request, Duel $duel): JsonResponse
    {
        $user = $request->user();

        if ($duel->opponent_id !== $user->id) {
            return response()->json(['message' => 'Это приглашение не для вас'], 403);
        }

        if ($duel->status !== 'pending') {
            return response()->json(['message' => 'Дуэль уже начата или завершена'], 400);
        }

        $duel->update([
            'status' => 'active',
            'accepted_at' => now(),
            'started_at' => now(),
        ]);

        return response()->json([
            'duel' => $duel->load(['challenger:id,name,avatar', 'tasks.steps.blocks']),
        ]);
    }

    public function decline(Request $request, Duel $duel): JsonResponse
    {
        $user = $request->user();

        if ($duel->opponent_id !== $user->id) {
            return response()->json(['message' => 'Это приглашение не для вас'], 403);
        }

        $duel->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Дуэль отклонена']);
    }

    public function submitResults(Request $request, Duel $duel): JsonResponse
    {
        $request->validate([
            'correct_count' => 'required|integer|min:0',
            'time_seconds' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        if ($duel->challenger_id === $user->id) {
            $duel->update([
                'challenger_correct' => $request->correct_count,
                'challenger_time_seconds' => $request->time_seconds,
            ]);
        } elseif ($duel->opponent_id === $user->id) {
            $duel->update([
                'opponent_correct' => $request->correct_count,
                'opponent_time_seconds' => $request->time_seconds,
            ]);
        } else {
            return response()->json(['message' => 'Вы не участник этой дуэли'], 403);
        }

        // Check if both players finished
        if ($duel->challenger_correct !== null && $duel->opponent_correct !== null) {
            $this->finishDuel($duel);
        }

        return response()->json(['duel' => $duel->fresh()]);
    }

    private function finishDuel(Duel $duel): void
    {
        $winnerId = null;

        if ($duel->challenger_correct > $duel->opponent_correct) {
            $winnerId = $duel->challenger_id;
        } elseif ($duel->opponent_correct > $duel->challenger_correct) {
            $winnerId = $duel->opponent_id;
        } elseif ($duel->challenger_time_seconds < $duel->opponent_time_seconds) {
            $winnerId = $duel->challenger_id;
        } elseif ($duel->opponent_time_seconds < $duel->challenger_time_seconds) {
            $winnerId = $duel->opponent_id;
        }

        $duel->update([
            'status' => 'completed',
            'winner_id' => $winnerId,
            'finished_at' => now(),
        ]);
    }

    public function show(Duel $duel): JsonResponse
    {
        $duel->load([
            'challenger:id,name,avatar',
            'opponent:id,name,avatar',
            'winner:id,name',
            'tasks',
        ]);

        return response()->json(['duel' => $duel]);
    }
}
