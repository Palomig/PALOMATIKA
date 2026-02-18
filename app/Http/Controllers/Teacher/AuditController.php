<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index()
    {
        return view('teacher.audit.index');
    }

    public function events(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        $query = $this->baseQuery();
        $this->applyFilters($query, $filters);

        $perPage = min(max((int) ($filters['per_page'] ?? 50), 10), 200);

        $events = $query
            ->orderByDesc('audit_events.occurred_at')
            ->orderByDesc('audit_events.id')
            ->paginate($perPage);

        return response()->json($events);
    }

    public function show(AuditEvent $event): JsonResponse
    {
        $row = $this->baseQuery()
            ->where('audit_events.id', $event->id)
            ->firstOrFail();

        return response()->json($row);
    }

    public function meta(): JsonResponse
    {
        return response()->json([
            'categories' => AuditEvent::query()->select('category')->distinct()->orderBy('category')->pluck('category')->values(),
            'event_types' => AuditEvent::query()->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type')->values(),
            'severities' => AuditEvent::query()->select('severity')->distinct()->orderBy('severity')->pluck('severity')->values(),
            'roles' => collect(['student', 'teacher', 'admin']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validateFilters($request);

        $query = $this->baseQuery();
        $this->applyFilters($query, $filters);

        $filename = 'audit-events-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'occurred_at', 'event_type', 'category', 'severity', 'actor', 'subject', 'ip', 'summary']);

            $query->orderByDesc('audit_events.occurred_at')
                ->orderByDesc('audit_events.id')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        $summary = json_encode($row->payload_json, JSON_UNESCAPED_UNICODE);
                        fputcsv($out, [
                            $row->id,
                            (string) $row->occurred_at,
                            $row->event_type,
                            $row->category,
                            $row->severity,
                            trim(($row->actor_name ?? '') . ' ' . ($row->actor_email ?? '')),
                            trim(($row->subject_name ?? '') . ' ' . ($row->subject_email ?? '')),
                            $row->ip,
                            mb_substr((string) $summary, 0, 500),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validateFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'event_type' => ['nullable', 'array'],
            'event_type.*' => ['string', 'max:64'],
            'category' => ['nullable', 'array'],
            'category.*' => ['string', 'max:32'],
            'severity' => ['nullable', 'array'],
            'severity.*' => ['string', 'max:16'],
            'actor_role' => ['nullable', 'array'],
            'actor_role.*' => ['string', 'max:32'],
            'actor_query' => ['nullable', 'string', 'max:120'],
            'subject_query' => ['nullable', 'string', 'max:120'],
            'subject_type' => ['nullable', 'string', 'max:64'],
            'subject_id' => ['nullable', 'string', 'max:64'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        $defaultFrom = Carbon::now()->subDays(30)->startOfDay();
        $defaultTo = Carbon::now()->endOfDay();

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : $defaultFrom;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : $defaultTo;

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(90)->startOfDay();
        }

        $validated['from'] = $from;
        $validated['to'] = $to;

        return $validated;
    }

    private function baseQuery(): Builder
    {
        return AuditEvent::query()
            ->leftJoin('users as actors', 'actors.id', '=', 'audit_events.actor_user_id')
            ->leftJoin('users as subjects', function ($join) {
                $join->on('subjects.id', '=', 'audit_events.subject_id')
                    ->where('audit_events.subject_type', '=', 'user');
            })
            ->select([
                'audit_events.*',
                'actors.name as actor_name',
                'actors.email as actor_email',
                'subjects.name as subject_name',
                'subjects.email as subject_email',
            ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->whereBetween('audit_events.occurred_at', [$filters['from'], $filters['to']]);

        if (!empty($filters['event_type'])) {
            $query->whereIn('audit_events.event_type', $filters['event_type']);
        }

        if (!empty($filters['category'])) {
            $query->whereIn('audit_events.category', $filters['category']);
        }

        if (!empty($filters['severity'])) {
            $query->whereIn('audit_events.severity', $filters['severity']);
        }

        if (!empty($filters['actor_role'])) {
            $query->whereIn('audit_events.actor_role', $filters['actor_role']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('audit_events.subject_type', $filters['subject_type']);
        }

        if (!empty($filters['subject_id'])) {
            $query->where('audit_events.subject_id', $filters['subject_id']);
        }

        if (!empty($filters['actor_query'])) {
            $needle = trim((string) $filters['actor_query']);
            $query->where(function ($q) use ($needle) {
                $q->where('actors.name', 'like', "%{$needle}%")
                    ->orWhere('actors.email', 'like', "%{$needle}%");
            });
        }

        if (!empty($filters['subject_query'])) {
            $needle = trim((string) $filters['subject_query']);
            $query->where(function ($q) use ($needle) {
                $q->where('subjects.name', 'like', "%{$needle}%")
                    ->orWhere('subjects.email', 'like', "%{$needle}%");
            });
        }
    }
}
