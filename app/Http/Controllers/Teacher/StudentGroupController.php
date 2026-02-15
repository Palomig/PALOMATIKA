<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\StudentGroup;
use App\Models\TeacherStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentGroupController extends Controller
{
    public function index()
    {
        return view('teacher.groups.index');
    }

    public function groups(Request $request): JsonResponse
    {
        $ownerTeacherId = $this->ownerTeacherId($request);

        $groups = StudentGroup::query()
            ->withCount('students')
            ->with(['students:id,name,email'])
            ->where('owner_teacher_id', $ownerTeacherId)
            ->orderBy('name')
            ->get()
            ->map(fn (StudentGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'students_count' => (int) $group->students_count,
                'students' => $group->students
                    ->map(fn ($student) => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'groups' => $groups,
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $ownerTeacherId = $this->ownerTeacherId($request);

        $students = TeacherStudent::query()
            ->with('student:id,name,email')
            ->where('teacher_id', $ownerTeacherId)
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ]);

        return response()->json([
            'success' => true,
            'students' => $students,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
        ]);

        $group = StudentGroup::query()->create([
            'owner_teacher_id' => $this->ownerTeacherId($request),
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
        ]);

        return response()->json([
            'success' => true,
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'students_count' => 0,
                'students' => [],
            ],
        ], 201);
    }

    public function addStudent(Request $request, StudentGroup $group): JsonResponse
    {
        $this->authorizeOwnership($request, $group);

        $validated = $request->validate([
            'student_id' => 'required|integer|exists:users,id',
        ]);

        $isLinkedToTeacher = TeacherStudent::query()
            ->where('teacher_id', $group->owner_teacher_id)
            ->where('student_id', (int) $validated['student_id'])
            ->exists();

        if (!$isLinkedToTeacher) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not linked to this teacher',
            ], 422);
        }

        $group->students()->syncWithoutDetaching([(int) $validated['student_id']]);

        return response()->json(['success' => true]);
    }

    public function removeStudent(Request $request, StudentGroup $group, int $studentId): JsonResponse
    {
        $this->authorizeOwnership($request, $group);

        $group->students()->detach($studentId);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, StudentGroup $group): JsonResponse
    {
        $this->authorizeOwnership($request, $group);

        $group->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeOwnership(Request $request, StudentGroup $group): void
    {
        if ((int) $group->owner_teacher_id !== (int) $this->ownerTeacherId($request)) {
            abort(404);
        }
    }

    private function ownerTeacherId(Request $request): int
    {
        $user = $request->user();

        if ($user->role === 'admin' && $request->filled('teacher_id')) {
            return (int) $request->integer('teacher_id');
        }

        return (int) $user->id;
    }
}
