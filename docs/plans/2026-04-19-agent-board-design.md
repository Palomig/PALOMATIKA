# Agent Board Design

## Goal

Собрать отдельный локальный веб-инструмент на сервере с канбан-доской задач, живой историей завершенных работ и справочниками агентов, MCP-серверов и скиллов с короткими ID.

## Decision

Используем отдельное приложение на `Node.js + Express + SQLite + SSE` без встраивания в существующие продукты.

Ключевые решения:
- приложение работает по IP сервера на отдельном порту
- авторизация упрощенная: один общий пароль
- активные задачи живут на доске с 3 колонками
- завершенные задачи уезжают в отдельную страницу `Completed`
- обновления доски и истории приходят в реальном времени через `Server-Sent Events`
- Джарвис и агенты интегрируются через локальный HTTP API

## Why

Для внутреннего инструмента важнее быстрый запуск, прозрачная эксплуатация и предсказуемая интеграция с агентами, чем тяжелый фронтенд-стек. `SQLite` убирает отдельную инфраструктуру БД. `SSE` достаточно для однонаправленных live-обновлений и проще `WebSocket`, потому что интерфейсу нужно в основном получать события о создании, переносе и завершении задач.

## Scope

- отдельное приложение вне существующих проектов `Palomatika` и `Evrium`
- login-экран с единым паролем
- основная доска с колонками `Без агента`, `Claude`, `Codex`
- drag-and-drop карточек между колонками
- плашка проекта `Palomatika` или `Evrium` в карточке и истории
- отдельная страница `Completed` с широкими горизонтальными блоками
- справочники `Agents`, `MCP Servers`, `Skills` с короткими ID для ссылок в чате
- API для Джарвиса на создание задач
- API для агентов на публикацию завершения работы и summary
- live-обновления интерфейса через `SSE`

## Out Of Scope

- многопользовательские роли и полноценные аккаунты
- сложная админка для ручного редактирования справочников
- интеграция с внешними облачными сервисами
- прямое изменение SQLite третьими системами в обход API

## User Flows

### 1. Создание задачи через Джарвиса

1. Джарвис получает команду от пользователя.
2. Джарвис отправляет `POST /api/tasks`.
3. Сервер создает задачу в колонке `unassigned`.
4. Доска получает событие `task_created` и обновляется без перезагрузки.

### 2. Назначение задачи агенту

1. Пользователь открывает доску.
2. Перетаскивает карточку из `Без агента` в `Claude` или `Codex`.
3. Сервер сохраняет новое положение карточки.
4. Все открытые клиенты получают событие `task_moved`.

### 3. Завершение работы

1. Агент или пользователь завершает задачу.
2. Задача удаляется из активной доски.
3. Сервер создает запись в `completed_tasks` с summary и использованными MCP/skills.
4. Все открытые клиенты получают событие `task_completed`.
5. На странице `Completed` появляется новый горизонтальный блок.

## Architecture

Приложение состоит из одного сервиса `Express`, который:
- отдает HTML, CSS и клиентский JavaScript
- обслуживает JSON API
- держит `SSE` endpoint для подписки на события
- пишет и читает данные из `SQLite`

Логические модули:
- `auth`: password gate и session/cookie
- `board`: чтение, создание, перенос и завершение задач
- `completed`: история завершенных работ
- `registry`: справочники агентов, MCP-серверов и скиллов
- `events`: fan-out событий в подписанные браузеры
- `integrations`: endpoints для Джарвиса и автопостинга завершений

## Pages

### `/login`

Минимальный экран авторизации с одним полем пароля и кнопкой входа.

### `/`

Главная доска:
- хедер с навигацией `Board` / `Completed`
- индикатор live-соединения
- 3 колонки канбана
- карточки с title, description, project badge, task ID, created time, кнопкой `Завершить`
- боковая панель со справочниками `Agents`, `MCP Servers`, `Skills`

Визуальные акценты:
- `Claude`: оранжевый
- `Codex`: черно-белый
- `Без агента`: нейтральный

### `/completed`

История завершенных работ в виде вертикальной ленты широких горизонтальных блоков. Каждый блок показывает:
- task ID
- title
- description
- project
- completed by
- summary
- список MCP IDs
- список skill IDs
- created time
- completed time

## Data Model

### `tasks`

- `id`
- `title`
- `description`
- `project` (`palomatika` | `evrium`)
- `column_key` (`unassigned` | `claude` | `codex`)
- `created_at`
- `updated_at`

### `completed_tasks`

- `id`
- `task_id`
- `title`
- `description`
- `project`
- `completed_by` (`claude` | `codex` | `manual`)
- `summary`
- `used_mcp_ids` (JSON array)
- `used_skill_ids` (JSON array)
- `created_at`
- `completed_at`

### `agents`

- `id`
- `name`
- `color`
- `kind`
- `is_active`

### `mcp_servers`

- `id`
- `name`
- `description`
- `is_active`

### `skills`

- `id`
- `name`
- `description`
- `is_active`

## API

### Auth

- `POST /api/login`
- `POST /api/logout`

### Board

- `GET /api/tasks`
- `POST /api/tasks`
- `PATCH /api/tasks/:id/move`
- `POST /api/tasks/:id/complete`

### Completed

- `GET /api/completed`
- `POST /api/completions`

### Registry

- `GET /api/registry`

### Events

- `GET /api/events`

## API Contracts

### `POST /api/tasks`

Input:
- `title`
- `description`
- `project`

Behavior:
- always creates task in `unassigned`
- emits `task_created`

### `PATCH /api/tasks/:id/move`

Input:
- `column_key`

Behavior:
- updates active task column
- emits `task_moved`

### `POST /api/tasks/:id/complete`

Input:
- optional `summary`
- optional `used_mcp_ids`
- optional `used_skill_ids`

Behavior:
- removes task from active board
- inserts record into `completed_tasks`
- emits `task_completed`

### `POST /api/completions`

Input:
- `task_id`
- `completed_by`
- `summary`
- `used_mcp_ids`
- `used_skill_ids`

Behavior:
- validates task exists
- validates referenced MCP/skill IDs
- archives task into completed history
- emits `task_completed`

## Real-Time Events

SSE события:
- `task_created`
- `task_moved`
- `task_completed`
- `registry_updated`

Клиент автоматически переподключается после разрыва соединения и повторно загружает текущее состояние, если пропустил события.

## Security

- общий пароль задается через `.env`
- session хранится в безопасной cookie
- API для Джарвиса и агентов защищается отдельным shared secret
- приложение предполагается внутренним, но не должно хардкодить секреты в репозиторий

## Error Handling

- неизвестный `project` возвращает `400`
- неизвестные `mcp_id` или `skill_id` возвращают `400`
- завершение несуществующей или уже закрытой задачи возвращает `404` или `409`
- при сбое `SSE` фронт переподключается автоматически
- при `SQLite busy` запись повторяется ограниченное число раз

## Testing

Нужны:
- auth smoke test
- task creation test
- move test
- manual completion test
- completion API test
- registry response test
- SSE smoke/integration test для базовых событий
- ручная browser-проверка drag-and-drop и live-обновлений

## Open Notes

- справочники будут поддерживаться через код и seed-данные, а не через сложную UI-админку
- столбец доски является назначением агента
- история должна хранить исполнителя независимо от текущего состояния справочников
