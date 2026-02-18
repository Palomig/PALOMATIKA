<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function log(array $data): ?AuditEvent
    {
        try {
            return AuditEvent::create([
                'occurred_at' => $data['occurred_at'] ?? now(),
                'event_type' => (string) ($data['event_type'] ?? 'unknown'),
                'category' => (string) ($data['category'] ?? 'system'),
                'severity' => (string) ($data['severity'] ?? 'info'),
                'actor_user_id' => $data['actor_user_id'] ?? null,
                'actor_role' => $data['actor_role'] ?? null,
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => isset($data['subject_id']) ? (string) $data['subject_id'] : null,
                'request_id' => $data['request_id'] ?? null,
                'ip' => $data['ip'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'payload_json' => is_array($data['payload_json'] ?? null) ? $data['payload_json'] : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLogger failed to persist event', [
                'event_type' => $data['event_type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
