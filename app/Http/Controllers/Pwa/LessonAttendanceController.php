<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use App\Models\LessonAttendance;
use App\Models\PushSubscription;
use App\Models\TeacherStudent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LessonAttendanceController extends Controller
{
    /**
     * GET /lessons/attendance?date=2026-04-16&time_start=15:00
     * Returns attendance marks for a lesson slot.
     */
    public function load(Request $request): JsonResponse
    {
        $date      = $request->query('date', now()->toDateString());
        $timeStart = $request->query('time_start');

        if (!$timeStart) {
            return response()->json(['error' => 'time_start required'], 422);
        }

        $records = LessonAttendance::where('lesson_date', $date)
            ->where('start_time', $timeStart)
            ->get(['student_id', 'status']);

        $map = $records->pluck('status', 'student_id');

        return response()->json(['attendance' => $map]);
    }

    /**
     * POST /lessons/attendance
     * Body: { date, time_start, student_id, status: present|absent }
     */
    public function save(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'date'       => 'required|date',
            'time_start' => 'required|string|max:10',
            'student_id' => 'required|integer',
            'status'     => 'required|in:present,absent',
        ]);

        // Verify the student belongs to this teacher
        $isLinked = TeacherStudent::where('teacher_id', $user->id)
            ->where('student_id', $data['student_id'])
            ->exists();

        if (!$isLinked) {
            return response()->json(['error' => 'Student not linked'], 403);
        }

        LessonAttendance::updateOrCreate(
            [
                'student_id'  => $data['student_id'],
                'lesson_date' => $data['date'],
                'start_time'  => $data['time_start'],
            ],
            [
                'status' => $data['status'],
                'source' => 'pwa',
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * POST /push/subscribe
     * Body: { subscription: { endpoint, keys: { p256dh, auth } } }
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'subscription.endpoint'      => 'required|string|max:2000',
            'subscription.keys.p256dh'   => 'required|string|max:500',
            'subscription.keys.auth'     => 'required|string|max:200',
        ]);

        $sub = $data['subscription'];

        PushSubscription::updateOrCreate(
            ['user_id' => $user->id, 'endpoint' => $sub['endpoint']],
            [
                'p256dh'       => $sub['keys']['p256dh'],
                'auth'         => $sub['keys']['auth'],
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
                'lesson_notify' => true,
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /push/subscribe
     * Body: { endpoint }
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(['endpoint' => 'required|string|max:2000']);

        PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * POST /lessons/report
     * Sends a Telegram message to the teacher with today's absence summary.
     * Body: { date? } (defaults to today)
     */
    public function sendDailyReport(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $date = $request->input('date', now()->toDateString());

        // Only works if teacher logged in via Telegram
        $chatId = $this->resolveTeacherChatId($user);

        if (!$chatId) {
            return response()->json([
                'ok'      => false,
                'message' => 'Telegram аккаунт не привязан. Войдите через Telegram чтобы получать отчёты.',
            ]);
        }

        $teacherId = $user->id;

        // Load all students of this teacher
        $relations = TeacherStudent::where('teacher_id', $teacherId)
            ->with('student:id,name')
            ->get();

        if ($relations->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Нет привязанных учеников.']);
        }

        $studentIds = $relations->pluck('student_id')->filter()->values();

        // Load today's attendance
        $attendance = LessonAttendance::where('lesson_date', $date)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'start_time', 'status']);

        // Group by student
        $byStudent = $attendance->groupBy('student_id');

        // Build alias map
        $aliasMap = [];
        $evriumMap = [];
        foreach ($relations as $rel) {
            $aliasMap[$rel->student_id]  = $rel->student_alias ?? $rel->student?->name ?? 'Ученик #' . $rel->student_id;
            $evriumMap[$rel->student_id] = $rel->evrium_name;
        }

        // Find absent students (those who have at least one absent record and no present for the same slot)
        $absentEntries = $attendance->where('status', 'absent');
        $presentEntries = $attendance->where('status', 'present');

        $absentList = [];
        foreach ($absentEntries as $entry) {
            // Check if same student was present at another time today
            $wasPresent = $presentEntries->where('student_id', $entry->student_id)->isNotEmpty();
            $name  = $aliasMap[$entry->student_id] ?? 'Ученик #' . $entry->student_id;
            $time  = $entry->start_time;
            $absentList[] = "— {$name} ({$time})";
        }

        $dateFormatted = \Carbon\Carbon::parse($date)->locale('ru')->isoFormat('D MMMM');

        if (empty($absentList)) {
            $text = "✅ <b>Отчёт за {$dateFormatted}</b>\n\nВсе ученики присутствовали на уроках!";
        } else {
            $absentText = implode("\n", array_unique($absentList));
            $text = "📋 <b>Отчёт за {$dateFormatted}</b>\n\n<b>Не пришли:</b>\n{$absentText}";
        }

        $sent = $this->sendTelegram($chatId, $text);

        if (!$sent) {
            return response()->json(['ok' => false, 'message' => 'Ошибка отправки в Telegram.']);
        }

        return response()->json(['ok' => true, 'message' => 'Отчёт отправлен в Telegram!']);
    }

    private function resolveTeacherChatId(User $user): ?string
    {
        // Direct Telegram login
        if ($user->oauth_provider === 'telegram' && $user->oauth_id) {
            return (string) $user->oauth_id;
        }

        return null;
    }

    private function sendTelegram(string $chatId, string $text): bool
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            Log::warning('LessonAttendanceController: TELEGRAM_BOT_TOKEN not set');
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->ok();
        } catch (\Throwable $e) {
            Log::error('LessonAttendanceController: Telegram send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
