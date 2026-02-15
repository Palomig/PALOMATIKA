# OGE Assessment Workflow Design

**Date:** 2026-02-15  
**Status:** Approved for implementation

## Goal
Implement an OGE workflow where teachers generate variants, students solve via mobile, all answer revisions are recorded, and teachers get private correctness/time analytics.

## Scope
- Teacher-only generator access for `/oge` and `/test/oge`
- Student-only solving access to generated variant pages (`/oge/{hash}`, `/test/oge/{hash}`)
- One attempt per student per variant
- Event-sourcing with hybrid projections
- Teacher read-only cross-teacher visibility in hierarchy `Teacher -> Variants -> Results`
- Student groups (many-to-many): one student in multiple groups
- Telegram sending button (group for common variant, direct for personal variant)

## Access model
- Roles: `teacher`, `student`, `admin`
- Teachers can edit only their own entities.
- Teachers can view other teachers' data in read-only mode.
- Students can access only their own attempts and only solve pages.
- Students never see correctness.

## Product flow
1. Teacher opens generator, chooses task filters, creates/sends variant.
2. Student scans QR / opens link and authenticates via Telegram or VK.
3. Student solves tasks on mobile:
   - Enters answer
   - Presses `OK` to commit version
   - Can press `Edit` to continue editing
4. Student presses `Finish` to submit final answers.
5. Teacher sees live/summary table by variant with answers, correctness, task-level time.

## Hybrid event-sourcing architecture
### Immutable event log (source of truth)
`oge_attempt_events`
- `attempt_started`
- `task_focused`
- `task_blurred`
- `answer_committed`
- `heartbeat`
- `attempt_submitted`

### Projections (fast read models)
- `oge_attempt_answers` (latest answer, commits count per task)
- `oge_attempt_task_timings` (active milliseconds, focus count)
- `oge_attempt_scorings` (teacher-only correctness)

Benefits:
- Full historical behavior for future ML/analytics
- Fast teacher and student UX from projection tables
- Clean separation of ingestion vs read rendering

## Data model
- `oge_variants` (owner teacher, hash, title, config)
- `oge_attempts` (variant+student unique, status, start/submit timestamps)
- `oge_attempt_events`
- `oge_attempt_answers`
- `oge_attempt_task_timings`
- `oge_attempt_scorings`
- `student_groups` + `student_group_user` (M:N)

## Teacher interfaces
- Teachers index (all teachers)
- Teacher variants index
- Variant results table:
  - rows: students
  - columns: tasks 6-19
  - values: answer, correctness, per-task time
  - status: active/submitted

## Student interfaces
- Mobile-first variant page
- Per-task controls: input + `OK` + `Edit`
- Global `Finish` button
- Submission lock after finish

## Privacy rules
- Correctness hidden from students
- Correctness visible to teachers only

## Telegram integration
- Send variant link to group (common variant)
- Send variant link to selected student (personal variant)
- Reuse existing Telegram bot transport

## Analytics foundation
Event-derived features (phase 1):
- time to first commit per task
- commits count per task
- total active time per task
- answer change rate
- instability index (many edits + wrong final)

These features are sufficient to bootstrap weak-topic detection and personalized variant generation logic.
