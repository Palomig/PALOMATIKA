<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Topic;
use App\Models\Attempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function show(Task $task): JsonResponse
    {
        $task->load(['topic', 'steps.blocks', 'skills']);

        return response()->json(['task' => $task]);
    }

    public function getNext(Request $request): JsonResponse
    {
        $request->validate([
            'topic_id' => 'nullable|exists:topics,id',
            'difficulty' => 'nullable|integer|min:1|max:5',
        ]);

        $user = $request->user();

        $query = Task::active();

        if ($request->topic_id) {
            $query->where('topic_id', $request->topic_id);
        }

        if ($request->difficulty) {
            $query->where('difficulty', $request->difficulty);
        }

        // Exclude recently attempted tasks
        $recentTaskIds = $user->attempts()
            ->where('created_at', '>', now()->subHours(2))
            ->pluck('task_id');

        $query->whereNotIn('id', $recentTaskIds);

        // Order by times_shown (prioritize less shown tasks)
        $task = $query->orderBy('times_shown')->first();

        if (!$task) {
            // If no task found, get any task
            $task = Task::active()
                ->when($request->topic_id, fn($q) => $q->where('topic_id', $request->topic_id))
                ->inRandomOrder()
                ->first();
        }

        if (!$task) {
            return response()->json(['message' => 'Задачи не найдены'], 404);
        }

        $task->load(['topic', 'steps.blocks', 'skills']);

        return response()->json(['task' => $task]);
    }

    public function startAttempt(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        $attempt = Attempt::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'session_id' => $request->session_id ?? Str::uuid(),
            'source' => $request->source ?? 'practice',
            'homework_id' => $request->homework_id,
            'duel_id' => $request->duel_id,
            'started_at' => now(),
        ]);

        // Increment times_shown
        $task->increment('times_shown');

        return response()->json([
            'attempt' => $attempt,
            'task' => $task->load(['steps.blocks']),
        ]);
    }

    public function submitAttempt(Request $request, Attempt $attempt): JsonResponse
    {
        $request->validate([
            'answer' => 'required|string',
        ]);

        if ($attempt->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        if ($attempt->is_completed) {
            return response()->json(['message' => 'Попытка уже завершена'], 400);
        }

        $task = $attempt->task;
        $isCorrect = $this->checkAnswer($task, $request->answer);

        $attempt->update([
            'is_completed' => true,
            'is_correct' => $isCorrect,
            'finished_at' => now(),
            'time_spent_seconds' => $attempt->started_at->diffInSeconds(now()),
            'xp_earned' => $isCorrect ? $this->calculateXp($task) : 0,
        ]);

        if ($isCorrect) {
            $task->increment('times_correct');
        }

        // Update user skills
        $this->updateUserSkills($attempt);

        return response()->json([
            'is_correct' => $isCorrect,
            'xp_earned' => $attempt->xp_earned,
            'correct_answer' => $task->correct_answer,
        ]);
    }

    private function checkAnswer(Task $task, string $answer): bool
    {
        $correctAnswer = mb_strtolower(trim($task->correct_answer));
        $userAnswer = mb_strtolower(trim($answer));

        // Remove spaces for comparison
        $correctAnswer = preg_replace('/\s+/', '', $correctAnswer);
        $userAnswer = preg_replace('/\s+/', '', $userAnswer);

        return $correctAnswer === $userAnswer;
    }

    private function calculateXp(Task $task): int
    {
        $baseXp = 10;
        $difficultyMultiplier = $task->difficulty;

        return $baseXp * $difficultyMultiplier;
    }

    private function updateUserSkills(Attempt $attempt): void
    {
        $user = $attempt->user;
        $task = $attempt->task;

        foreach ($task->skills as $skill) {
            $userSkill = $user->skills()->firstOrCreate(
                ['skill_id' => $skill->id],
                ['weight' => 0]
            );

            $userSkill->increment('attempts_count');

            if ($attempt->is_correct) {
                $userSkill->increment('correct_count');

                // Update weight using ELO-like formula
                $newWeight = min(1, $userSkill->weight + (1 - $userSkill->weight) * 0.1);
                $userSkill->update([
                    'weight' => $newWeight,
                    'last_practiced_at' => now(),
                ]);
            } else {
                // Decrease weight slightly on wrong answer
                $newWeight = max(0, $userSkill->weight - $userSkill->weight * 0.05);
                $userSkill->update([
                    'weight' => $newWeight,
                    'last_practiced_at' => now(),
                ]);
            }
        }
    }
}
