# Урок — отслеживание активности ученика

> Дата: 2026-07-17. Автор задачи: Стас. Статус: одобрено (Live + итог).

## Задача

На странице урока записывать, сворачивал ли ученик приложение / уходил со страницы,
как долго был на странице и сколько отсутствовал — все интервалы. Учитель видит
в реальном времени статус (на странице / отошёл) и итог (сколько раз отходил,
время вне страницы, время на странице).

## Модель данных

Таблица `lesson_activity_intervals`:
- `lesson_session_id`, `student_id` (FK, cascade)
- `kind` ENUM(`present`,`away`)
- `started_at` timestamp
- `ended_at` timestamp nullable (null = интервал идёт сейчас)
- `updated_at` — последний heartbeat (для present), детект «молча закрыл вкладку»
- индекс `(lesson_session_id, student_id, started_at)`

Инвариант: у пары (session, student) максимум один открытый интервал (`ended_at IS NULL`).
Непрерывный таймлайн present/away от входа до конца урока.

## Клиент (страница урока ученика)

- `init()` → ping `{visible:true}` (открывает present).
- `visibilitychange`: hidden → `{visible:false}`; visible → `{visible:true}` (мгновенно).
- Heartbeat каждые 10 сек, только если `visibilityState==='visible'` → `{visible:true}`
  (продлевает present, детект силент-дропа).
- `pagehide` → `navigator.sendBeacon('/lessons/{id}/activity', {visible:false})`.

## Сервер

`POST /lessons/{id}/activity {visible: bool}` (student, участник; разрешён под lesson-lock).
Время ставит сервер (не доверяем клиенту).

`LessonSessionService::recordActivity(session, student, visible)`:
- desired = visible ? present : away
- open = последний интервал с `ended_at IS NULL`
- open?.kind === desired → bump `updated_at` (heartbeat)
- иначе → закрыть open (`ended_at = now`), создать новый интервал desired
- нет open → создать новый интервал desired

`LessonSessionService::activitySummary(session): array` (по student_id):
- `now_ref` = session ended ? ends_at : now()
- STALE = 25 сек
- currentState: open.kind==='away' → away; present → (updated_at ≥ now-STALE ? present : away); нет open → gone
- present_seconds = Σ present (started → ended ?? (stale ? updated_at : now_ref))
- away_seconds = Σ away (started → ended ?? now_ref)
- away_count = число away-интервалов
- (все длительности ≥ 0)

## UI учителя

В teacher `state()` в participants добавить `activity: {state, away_count, away_seconds, present_seconds}`.
В lesson-prep: у участника в live-гриде и в чипах — 🟢 на странице / 🔴 отошёл
+ «отходил N раз · вне X мин · на странице Y мин».

## Тесты

- Unit `LessonSessionServiceTest`: recordActivity открывает present; hidden закрывает present+открывает away;
  повторный visible закрывает away+открывает present; heartbeat не плодит интервалы; summary считает
  present/away/count/current; stale-present → currently away.
- Feature `TeacherLessonControllerTest`: state отдаёт activity по участнику.
- Feature `StudentLessonControllerTest`: POST activity 200 участнику, 403 не-участнику, 422 без visible.

## Не в скоупе (потом)

Визуальный таймлайн-бар присутствия после урока (интервалы уже пишутся — достаточно данных).
