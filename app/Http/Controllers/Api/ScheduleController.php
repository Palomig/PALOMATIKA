<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonSchedule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    /**
     * Verify deploy secret. Returns error response or null if OK.
     */
    private function auth(Request $request): ?JsonResponse
    {
        $secret = config('services.deploy.webhook_secret');

        if (empty($secret)) {
            return response()->json(['error' => 'Not configured'], 503);
        }

        if (!hash_equals($secret, $request->header('X-Deploy-Secret') ?? '')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return null;
    }

    /**
     * GET /api/schedule — all active slots grouped by day.
     */
    public function index(Request $request): JsonResponse
    {
        if ($err = $this->auth($request)) return $err;

        $slots = LessonSchedule::where('is_active', true)
            ->with('student:id,name')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'student_id' => $s->student_id,
                'student_name' => $s->student?->name,
                'day_of_week' => $s->day_of_week,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : null,
            ]);

        return response()->json(['slots' => $slots]);
    }

    /**
     * GET /api/schedule/today — students scheduled for today.
     */
    public function today(Request $request): JsonResponse
    {
        if ($err = $this->auth($request)) return $err;

        $dow = (int) now()->format('N'); // 1=Mon ... 7=Sun

        $slots = LessonSchedule::where('is_active', true)
            ->where('day_of_week', $dow)
            ->with('student:id,name')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'student_id' => $s->student_id,
                'student_name' => $s->student?->name,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : null,
            ]);

        return response()->json([
            'day_of_week' => $dow,
            'day_name' => ['', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'][$dow],
            'slots' => $slots,
        ]);
    }

    /**
     * POST /api/schedule — create a slot.
     * Body: { student_id, day_of_week, start_time, end_time? }
     */
    public function store(Request $request): JsonResponse
    {
        if ($err = $this->auth($request)) return $err;

        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:1,7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        // Find the teacher for this student
        $teacherId = \DB::table('teacher_students')
            ->where('student_id', $data['student_id'])
            ->value('teacher_id') ?? 1;

        $slot = LessonSchedule::create([
            'teacher_id' => $teacherId,
            'student_id' => $data['student_id'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
        ]);

        $slot->load('student:id,name');

        return response()->json([
            'message' => 'Slot created',
            'slot' => [
                'id' => $slot->id,
                'student_id' => $slot->student_id,
                'student_name' => $slot->student?->name,
                'day_of_week' => $slot->day_of_week,
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => $slot->end_time ? substr($slot->end_time, 0, 5) : null,
            ],
        ], 201);
    }

    /**
     * PUT /api/schedule/{id} — update a slot.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($err = $this->auth($request)) return $err;

        $slot = LessonSchedule::findOrFail($id);

        $data = $request->validate([
            'day_of_week' => 'sometimes|integer|between:1,7',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'is_active' => 'sometimes|boolean',
        ]);

        $slot->update($data);
        $slot->load('student:id,name');

        return response()->json([
            'message' => 'Slot updated',
            'slot' => [
                'id' => $slot->id,
                'student_id' => $slot->student_id,
                'student_name' => $slot->student?->name,
                'day_of_week' => $slot->day_of_week,
                'start_time' => substr($slot->start_time, 0, 5),
                'end_time' => $slot->end_time ? substr($slot->end_time, 0, 5) : null,
                'is_active' => $slot->is_active,
            ],
        ]);
    }

    /**
     * DELETE /api/schedule/{id} — delete a slot.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->auth($request)) return $err;

        $slot = LessonSchedule::findOrFail($id);
        $slot->delete();

        return response()->json(['message' => 'Slot deleted']);
    }

    /**
     * GET /api/students/search?name=... — search students by name.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        if ($err = $this->auth($request)) return $err;

        $name = $request->query('name', '');

        if (mb_strlen($name) < 2) {
            return response()->json(['students' => []]);
        }

        $students = User::where('role', 'student')
            ->where('name', 'LIKE', '%' . $name . '%')
            ->select('id', 'name')
            ->limit(20)
            ->get();

        return response()->json(['students' => $students]);
    }
}
