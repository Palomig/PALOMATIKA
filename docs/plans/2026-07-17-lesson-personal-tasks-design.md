# Урок — персональные задания

> Дата: 2026-07-17. Одобрено (общая таблица, пометка «для Имя»).

## Задача

Учитель во время урока (и в черновике) может добавить задание не всем, а конкретному
ученику. Персональную задачу видит и решает только этот ученик.

## Данные

`lesson_session_tasks.assigned_student_id` nullable FK users (cascade on delete).
- `null` — задача для всех участников (как сейчас).
- `id` — персональная, только для этого ученика.

## Сервис

- `addTask(session, bank, refs, ?int $assignedStudentId = null)` — сохраняет assigned_student_id.
- `taskVisibleTo(LessonSessionTask $task, int $studentId): bool` — null-assigned или assigned == student.
- `submitAnswer` — добавить проверку `taskVisibleTo` (ученик не решает чужую персональную).

## Эндпоинты

- Teacher `POST /lessons/{id}/tasks` — доп. поле `assigned_student_id` (nullable, integer).
  Валидация: если задан — должен быть участником сессии, иначе 422.
- Teacher `state()` serializeTask: добавить `assigned_student_id` + `assigned_name`.
- Student `state()`: отдавать только задачи, видимые ученику (null + свои).
  Нумерация у ученика — последовательная по порядку (position может иметь «дыры»).

## UI

- **Picker (lesson-prep):** над picker'ом селектор «Кому: Всем классу / <участник>».
  `onPickerAdd` шлёт выбранный `assigned_student_id` с каждой задачей. Селектор — в
  lesson-prep (picker-партиал общий с ДЗ, его не трогаем).
- **Live-грид:** персональная задача — обычная колонка с бейджем «для Имя»;
  ячейки НЕ-назначенных учеников — серый «·» (не к ним). Ячейка назначенного — как обычно.
- **Список задач:** у персональной — бейдж «для Имя».

## Тесты

- Unit: addTask сохраняет assigned; taskVisibleTo; submitAnswer 403/DomainException для чужой персональной.
- Feature teacher: addTask с assigned_student_id участника ок; не-участник → 422; state отдаёт assigned_student_id/assigned_name.
- Feature student: state отдаёт общие + свои персональные, не чужие; submit чужой персональной → 422.
