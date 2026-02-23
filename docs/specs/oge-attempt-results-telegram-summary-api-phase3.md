# OGE Attempt Results Telegram Summary API (Phase 3)

Scope: Telegram/bot-friendly read API that adapts Phase 2 teacher result payloads into a compact summary plus canonical web links.

## Endpoint

### `GET /api/oge/attempts/{attempt}/telegram-summary`

- Auth: variant owner `teacher` or `admin`
- Purpose: stable bot-consumable summary contract for sending result notifications/messages
- Source of truth: internally builds from Phase 2 `buildAttemptResultPayload()` (web UI remains canonical renderer)

## Response shape

```json
{
  "success": true,
  "contract": {
    "name": "oge_attempt_telegram_result_summary",
    "version": 1
  },
  "attempt": {
    "id": 123,
    "status": "scored",
    "variant_id": 55,
    "variant_hash": "abcd1234",
    "is_custom": false,
    "student_id": 9,
    "student": { "id": 9, "name": "Student", "email": "student@example.com" }
  },
  "summary": {
    "tasks_total": 14,
    "answered_count": 14,
    "unanswered_count": 0,
    "correct_count": 10,
    "incorrect_count": 4,
    "unchecked_count": 0,
    "total_active_ms": 22000,
    "away_ms_total": 0,
    "duration_ms": 660000
  },
  "telegram": {
    "message_text": "OGE Results\n...",
    "task_statuses": [
      { "task_number": 6, "status": "correct", "code": "+" },
      { "task_number": 7, "status": "incorrect", "code": "-" }
    ],
    "links": {
      "variant_results_url": "https://app/.../teacher/oge/variants/55/results",
      "attempt_results_url": "https://app/.../teacher/oge/variants/55/results?attempt=123#attempt-123"
    }
  }
}
```

## Status code mapping (compact task list)

- `correct` -> `+`
- `incorrect` -> `-`
- `unchecked` -> `?`
- `unanswered` -> `.`

## Canonical rendering

- Telegram output must stay lightweight (summary + deep links)
- Full task table/details remain in `teacher.oge.results` web UI
- `attempt_results_url` uses row anchor `#attempt-{id}` for direct navigation
