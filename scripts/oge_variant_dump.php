<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\OgeAttempt;
use App\Models\OgeVariant;
use Carbon\Carbon;

$variantId = (int)($argv[1] ?? 0);
if ($variantId <= 0) {
    fwrite(STDERR, "Usage: php scripts/oge_variant_dump.php <variant_id>\n");
    exit(1);
}

$todayStart = Carbon::today();
$todayEnd = Carbon::tomorrow();

$variant = OgeVariant::with('ownerTeacher:id,name,email')->find($variantId);
if (!$variant) {
    echo json_encode(['error' => "variant {$variantId} not found"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

$attempts = OgeAttempt::where('variant_id', $variantId)
    ->with(['student:id,name,email', 'answers', 'taskTimings', 'scorings', 'events'])
    ->orderByDesc('created_at')
    ->get();

$report = [
    'generated_at' => now()->toIso8601String(),
    'variant' => [
        'id' => $variant->id,
        'hash' => $variant->hash,
        'owner_teacher_id' => $variant->owner_teacher_id,
        'owner_teacher' => $variant->ownerTeacher ? [
            'name' => $variant->ownerTeacher->name,
            'email' => $variant->ownerTeacher->email,
        ] : null,
        'created_at' => optional($variant->created_at)?->toIso8601String(),
    ],
    'summary' => [
        'attempts_total' => $attempts->count(),
        'attempts_today' => $attempts->filter(fn ($a) => $a->created_at && $a->created_at->between($todayStart, $todayEnd, true))->count(),
        'submitted_total' => $attempts->where('status', 'submitted')->count(),
        'active_total' => $attempts->where('status', 'active')->count(),
    ],
    'attempts' => $attempts->map(function ($a) use ($todayStart, $todayEnd) {
        $answers = $a->answers->keyBy('task_number');
        $timings = $a->taskTimings->keyBy('task_number');
        $scorings = $a->scorings->keyBy('task_number');

        $tabAwayEvents = $a->events->where('event_type', 'tab_away');
        $tabAwayMsFromEvents = $tabAwayEvents->sum(fn ($e) => (int) data_get($e->payload_json, 'away_ms', 0));
        $awayMsTotalMeta = (int) data_get($a->device_meta, 'away_ms_total', 0);

        $tasks = [];
        for ($task = 6; $task <= 19; $task++) {
            $tasks[] = [
                'task_number' => $task,
                'answer' => optional($answers->get($task))->current_answer,
                'active_ms' => (int) (optional($timings->get($task))->active_ms ?? 0),
                'focus_count' => (int) (optional($timings->get($task))->focus_count ?? 0),
                'is_correct' => optional($scorings->get($task))->is_correct,
                'correct_answer' => optional($scorings->get($task))->correct_answer,
            ];
        }

        return [
            'attempt_id' => $a->id,
            'student' => [
                'id' => $a->student_id,
                'name' => optional($a->student)->name,
                'email' => optional($a->student)->email,
            ],
            'status' => $a->status,
            'is_today' => $a->created_at ? $a->created_at->between($todayStart, $todayEnd, true) : false,
            'created_at' => optional($a->created_at)?->toIso8601String(),
            'updated_at' => optional($a->updated_at)?->toIso8601String(),
            'submitted_at' => optional($a->submitted_at)?->toIso8601String(),
            'total_active_ms' => (int) $a->taskTimings->sum('active_ms'),
            'away_ms_total_meta' => $awayMsTotalMeta,
            'away_ms_total_events' => (int) $tabAwayMsFromEvents,
            'events_count' => $a->events->count(),
            'tab_away_events_count' => $tabAwayEvents->count(),
            'tasks' => $tasks,
        ];
    })->values(),
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
