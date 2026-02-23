# OGE Attempt Results/Status API (Phase 2)

Scope: backend API only for attempt status/details and teacher attempt results payloads. This phase does not include topic13 UI changes.

## Endpoints

### `GET /api/oge/attempts/{attempt}/status`

- Auth: `student` owner of attempt or `admin`
- Purpose: resume/status payload for student-facing clients
- Visibility: excludes correctness fields (`is_correct`, `correct_answer`)

Response shape:

```json
{
  "success": true,
  "attempt": {
    "id": 123,
    "status": "active",
    "locked": false,
    "variant_id": 55,
    "variant_hash": "abcd1234",
    "is_custom": false,
    "student_id": 9,
    "started_at": "2026-02-23T12:00:00+00:00",
    "submitted_at": null,
    "last_seen_at": "2026-02-23T12:10:00+00:00"
  },
  "summary": {
    "tasks_total": 14,
    "answered_count": 3,
    "unanswered_count": 11,
    "total_active_ms": 42000,
    "away_ms_total": 5000,
    "duration_ms": null
  },
  "tasks": [
    {
      "task_number": 6,
      "status": "answered",
      "answer": "77",
      "commits_count": 2,
      "is_final": false,
      "first_committed_at": "2026-02-23T12:03:00+00:00",
      "last_committed_at": "2026-02-23T12:04:00+00:00",
      "active_ms": 15000,
      "focus_count": 2,
      "last_focus_at": null,
      "last_heartbeat_at": null
    }
  ]
}
```

Task status values for `/status`:

- `answered`
- `seen`
- `unanswered`

### `GET /api/oge/attempts/{attempt}/result`

- Auth: variant owner `teacher` or `admin`
- Purpose: teacher analytics/result payload for a single attempt
- Visibility: includes correctness fields and summary scoring counts

Response shape (additional result-only fields shown):

```json
{
  "success": true,
  "attempt": {
    "id": 123,
    "status": "scored",
    "locked": true,
    "variant_id": 55,
    "variant_hash": "abcd1234",
    "is_custom": true,
    "student_id": 9,
    "student": { "id": 9, "name": "Student", "email": "student@example.com" },
    "variant": { "id": 55, "hash": "abcd1234", "title": "Variant", "owner_teacher_id": 3 }
  },
  "summary": {
    "tasks_total": 2,
    "answered_count": 2,
    "unanswered_count": 0,
    "correct_count": 1,
    "incorrect_count": 1,
    "unchecked_count": 0,
    "total_active_ms": 22000,
    "away_ms_total": 0,
    "duration_ms": 660000
  },
  "tasks": [
    {
      "task_number": 1,
      "status": "correct",
      "answer": "42",
      "is_correct": true,
      "correct_answer": "42",
      "checked_at": "2026-02-23T12:20:00+00:00"
    },
    {
      "task_number": 6,
      "status": "incorrect",
      "answer": "11",
      "is_correct": false,
      "correct_answer": "99",
      "checked_at": "2026-02-23T12:20:00+00:00"
    }
  ]
}
```

Task status values for `/result`:

- `correct`
- `incorrect`
- `unchecked`
- `unanswered`

## Task Number Resolution

- Generator variants default to task numbers `6..19`
- Custom variants prefer `config_json.custom_task_numbers`
- If present, numbers from `custom_tasks[*].attempt_task_number` are also included
- Any task numbers appearing in answers/timings/scorings projections are merged into the final ordered list

## Notes

- Phase 2 is read-only: no event writes or scoring changes
- Contracts are covered by feature tests for both generator and custom attempts
