<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index(): JsonResponse
    {
        $topics = Topic::active()
            ->ordered()
            ->withCount('tasks')
            ->get();

        return response()->json(['topics' => $topics]);
    }

    public function show(Topic $topic): JsonResponse
    {
        $topic->load(['tasks' => function ($query) {
            $query->active()->take(10);
        }]);

        return response()->json(['topic' => $topic]);
    }

    public function progress(Request $request, Topic $topic): JsonResponse
    {
        $user = $request->user();

        $totalTasks = $topic->tasks()->active()->count();
        $attemptedTasks = $user->attempts()
            ->whereHas('task', fn($q) => $q->where('topic_id', $topic->id))
            ->distinct('task_id')
            ->count('task_id');
        $correctTasks = $user->attempts()
            ->where('is_correct', true)
            ->whereHas('task', fn($q) => $q->where('topic_id', $topic->id))
            ->distinct('task_id')
            ->count('task_id');

        return response()->json([
            'topic_id' => $topic->id,
            'total_tasks' => $totalTasks,
            'attempted_tasks' => $attemptedTasks,
            'correct_tasks' => $correctTasks,
            'progress_percent' => $totalTasks > 0 ? round($correctTasks / $totalTasks * 100, 1) : 0,
        ]);
    }
}
