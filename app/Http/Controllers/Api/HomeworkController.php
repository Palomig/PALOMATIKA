<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    // Teacher methods

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            $homeworks = $user->homeworks()
                ->with(['topic', 'assignments.student'])
                ->withCount('assignments')
                ->latest()
                ->paginate(20);
        } else {
            $homeworks = HomeworkAssignment::where('student_id', $user->id)
                ->with(['homework.teacher', 'homework.topic'])
                ->latest()
                ->paginate(20);
        }

        return response()->json($homeworks);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isTeacher()) {
            return response()->json(['message' => 'Только учителя могут создавать ДЗ'], 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'homework_type' => 'required|in:specific_tasks,topic_random,weak_skills',
            'topic_id' => 'required_if:homework_type,topic_random|exists:topics,id',
            'tasks_count' => 'required_if:homework_type,topic_random,weak_skills|integer|min:1|max:50',
            'task_ids' => 'required_if:homework_type,specific_tasks|array',
            'task_ids.*' => 'exists:tasks,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
            'deadline_at' => 'nullable|date|after:now',
        ]);

        $homework = Homework::create([
            'teacher_id' => $user->id,
            'title' => $request->title,
            'homework_type' => $request->homework_type,
            'topic_id' => $request->topic_id,
            'tasks_count' => $request->tasks_count,
            'deadline_at' => $request->deadline_at,
        ]);

        // Attach specific tasks
        if ($request->homework_type === 'specific_tasks' && $request->task_ids) {
            foreach ($request->task_ids as $order => $taskId) {
                $homework->tasks()->attach($taskId, ['task_order' => $order]);
            }
        }

        // Create assignments for students
        foreach ($request->student_ids as $studentId) {
            HomeworkAssignment::create([
                'homework_id' => $homework->id,
                'student_id' => $studentId,
                'tasks_total' => $request->tasks_count ?? count($request->task_ids ?? []),
            ]);
        }

        return response()->json([
            'homework' => $homework->load(['tasks', 'assignments.student']),
        ], 201);
    }

    public function show(Homework $homework): JsonResponse
    {
        $homework->load(['teacher', 'topic', 'tasks', 'assignments.student']);

        return response()->json(['homework' => $homework]);
    }

    // Student methods

    public function start(Request $request, HomeworkAssignment $assignment): JsonResponse
    {
        $user = $request->user();

        if ($assignment->student_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        if ($assignment->status === 'completed') {
            return response()->json(['message' => 'ДЗ уже выполнено'], 400);
        }

        if ($assignment->status === 'assigned') {
            $assignment->update([
                'status' => 'started',
                'started_at' => now(),
            ]);
        }

        $homework = $assignment->homework->load('tasks.steps.blocks');

        return response()->json([
            'assignment' => $assignment,
            'homework' => $homework,
        ]);
    }

    public function complete(Request $request, HomeworkAssignment $assignment): JsonResponse
    {
        $user = $request->user();

        if ($assignment->student_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $assignment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'assignment' => $assignment,
            'accuracy' => $assignment->accuracy,
        ]);
    }

    public function statistics(Request $request, Homework $homework): JsonResponse
    {
        $user = $request->user();

        if ($homework->teacher_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $assignments = $homework->assignments()->with('student')->get();

        $stats = [
            'total_students' => $assignments->count(),
            'started' => $assignments->where('status', '!=', 'assigned')->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'average_accuracy' => round($assignments->avg('accuracy') ?? 0, 1),
            'completion_rate' => $homework->completion_rate,
        ];

        return response()->json([
            'stats' => $stats,
            'assignments' => $assignments,
        ]);
    }
}
