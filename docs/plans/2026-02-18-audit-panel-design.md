# Дизайн: единая панель аудита действий учеников и пользователей

**Дата:** 2026-02-18  
**Статус:** Approved

## Цель

Сделать полноценную панель аудита с доступом для `teacher` и `admin`, где доступны все ключевые логи системы (ОГЭ, авторизация, админские и учительские действия) с фильтрацией и хранением сырых событий 90 дней.

## Ключевые решения

### 1) Единое хранилище событий

Выбран подход с общей таблицей `audit_events` вместо разрозненного чтения из разных источников.

Почему:
- единый API и единые фильтры;
- предсказуемая производительность;
- меньше сложности в UI;
- прозрачная расширяемость по новым типам событий.

### 2) Политика доступа

- Доступ к панели: только `teacher` и `admin`.
- Видимость событий: в первой версии без дополнительного скоупинга по owner (как в текущем teacher review flow).
- Дальнейшее сужение прав можно ввести позже отдельной политикой.

### 3) Срок хранения

- Сырые события храним 90 дней.
- Ежедневная очистка по `occurred_at` через scheduled command.

## Архитектура

### Таблица `audit_events`

Поля:
- `id`
- `occurred_at`
- `event_type` (например `login_success`, `task_focused`, `answer_committed`, `tab_away`, `attempt_submitted`, `admin_answer_updated`)
- `category` (`auth`, `oge`, `admin`, `teacher`, `system`)
- `severity` (`info`, `warning`, `error`)
- `actor_user_id` (nullable)
- `actor_role` (nullable)
- `subject_type` (nullable)
- `subject_id` (nullable)
- `request_id` (nullable)
- `ip` (nullable)
- `user_agent` (nullable)
- `payload_json` (nullable)
- `created_at`, `updated_at`

Индексы:
- `occurred_at`
- `(event_type, occurred_at)`
- `(category, occurred_at)`
- `(actor_user_id, occurred_at)`
- `(subject_type, subject_id, occurred_at)`
- `(severity, occurred_at)`

### Сервис записи

Вводится единый `AuditLogger` (service), через который пишутся события из:
- auth flow;
- OGE attempt flow;
- админских и учительских действий.

## API панели

### `GET /api/audit/events`

Список событий с пагинацией и фильтрами:
- `from`, `to`
- `event_type[]`
- `category[]`
- `severity[]`
- `actor_role[]`
- `actor_user_id` / поиск по `actor_query` (name/email)
- `subject_type`, `subject_id`
- `subject_query` (поиск по ученику)
- `per_page`, `page`

### `GET /api/audit/events/{id}`

Полные детали события, включая `payload_json`.

### `GET /api/audit/meta`

Справочники для фильтров:
- event types
- categories
- severities
- roles

### `GET /api/audit/events/export`

CSV-экспорт текущей выборки по фильтрам.

## UI панели

Новая страница: `GET /teacher/audit`

Функции:
- фильтры: период, тип события, категория, роль, actor, subject, severity;
- пресеты по датам: `Сегодня`, `Вчера`, `7 дней`, `30 дней`, `90 дней`;
- таблица с колонками: время, тип, категория, actor, subject, IP, summary;
- просмотр деталей события в drawer/modal;
- экспорт CSV с теми же фильтрами.

## Производительность и ограничения

- По умолчанию фильтр по последним 30 дням.
- Максимальное окно запроса: 90 дней.
- Серверная пагинация.
- В списке только summary, тяжелый payload отдается в деталях.

## Ретеншн и сопровождение

- Команда: `audit:prune --days=90`.
- Запуск ежедневно по cron/scheduler.
- Логирование результата очистки (сколько записей удалено).

## Тестовая стратегия

### Feature tests
- доступ `teacher/admin`, запрет для `student`;
- фильтры API по дате, типу, роли, actor/subject;
- export отдает корректный CSV по выборке;
- prune удаляет события старше 90 дней.

### Unit tests
- `AuditLogger` корректно формирует и пишет событие;
- нормализация payload и техполей.

## Результат

После внедрения команда сможет централизованно видеть полную историю действий и попыток учеников без ручного доступа к БД, с удобной фильтрацией и контролируемым сроком хранения данных.
