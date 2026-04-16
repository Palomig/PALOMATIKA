<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Runs every minute via scheduler.
 * Fetches each teacher's Evrium schedule and sends a push
 * notification if a lesson starts within the next 10 minutes.
 */
class SendLessonNotifications extends Command
{
    protected $signature   = 'lessons:notify';
    protected $description = 'Send push notifications for upcoming lessons (runs every minute)';

    public function __construct(private readonly WebPushService $push)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now           = now();
        $windowMinutes = 10; // notify N minutes before lesson

        // Find teachers who have push subscriptions
        $teacherIds = PushSubscription::query()
            ->where('lesson_notify', true)
            ->distinct()
            ->pluck('user_id');

        if ($teacherIds->isEmpty()) {
            return self::SUCCESS;
        }

        $teachers = User::whereIn('id', $teacherIds)
            ->where('role', 'teacher')
            ->get(['id', 'name']);

        foreach ($teachers as $teacher) {
            $this->notifyTeacher($teacher, $now, $windowMinutes);
        }

        return self::SUCCESS;
    }

    private function notifyTeacher(User $teacher, \Carbon\Carbon $now, int $windowMinutes): void
    {
        $slots = $this->fetchEvriumSchedule($teacher->id);
        if (empty($slots)) {
            return;
        }

        $dow       = (int) $now->format('N');
        $todaySlots = array_filter($slots, fn($s) => ($s['day'] ?? 0) === $dow);

        foreach ($todaySlots as $slot) {
            $timeStart = (string) ($slot['time_start'] ?? '');
            if ($timeStart === '') continue;

            [$hours, $minutes] = array_pad(explode(':', $timeStart), 2, '0');
            $lessonStart = $now->copy()->startOfDay()->setTime((int) $hours, (int) $minutes);

            $diffMin = (int) $now->diffInMinutes($lessonStart, false);

            // Notify when 5–15 min window (we run every minute, so we check for a range)
            if ($diffMin < 5 || $diffMin > 15) {
                continue;
            }

            // De-duplicate: only send once per (teacher, date, slot)
            $cacheKey = "lesson_notify:{$teacher->id}:{$now->toDateString()}:{$timeStart}";
            if (Cache::has($cacheKey)) {
                continue;
            }
            Cache::put($cacheKey, 1, now()->addHours(2));

            $students     = $slot['students'] ?? [];
            $studentCount = count($students);
            $names        = array_slice($students, 0, 3);
            $nameStr      = implode(', ', $names) . ($studentCount > 3 ? ' +' . ($studentCount - 3) : '');

            $this->push->notifyUser($teacher->id, [
                'title' => "Урок через {$diffMin} мин",
                'body'  => "{$timeStart}" . ($nameStr ? " · {$nameStr}" : ''),
                'url'   => '/lessons',
                'tag'   => "lesson-{$timeStart}",
            ]);

            Log::info("SendLessonNotifications: notified teacher {$teacher->id} about lesson at {$timeStart}");
        }
    }

    private function fetchEvriumSchedule(int $teacherId): array
    {
        $apiUrl = 'https://xn--b1ammoq0d.xn--p1ai/zarplata/api/external.php';
        $apiKey = '15da8c6b7eed43fd5f3afae70a9f792516fb49957127e2ae';

        try {
            $response = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->timeout(5)
                ->get($apiUrl, ['action' => 'schedule', 'teacher_id' => 1]);

            if (!$response->ok()) return [];
            return $response->json('data') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
