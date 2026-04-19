# Agent Board

Локальная kanban-доска для распределения задач между `Claude` и `Codex` с историей выполненных работ, live-обновлениями и API для Джарвиса.

## Quick Start

```bash
cd agent-board
cp .env.example .env
npm install
npm run dev
```

Приложение поднимется на `http://<server-ip>:4310` по умолчанию.

## Environment

- `PORT` — порт HTTP-сервера
- `APP_PASSWORD` — общий пароль для веб-интерфейса
- `INTEGRATION_SECRET` — секрет для API Джарвиса и агентских completion-запросов
- `DB_PATH` — путь до SQLite файла
- `COOKIE_NAME` — имя cookie сессии
- `REPO_ROOT` — корень репозитория для чтения `.mcp.json`
- `CODEX_HOME` — корень Codex-конфига для сканирования skills/plugins

## API

### Create Task

```bash
curl -X POST http://127.0.0.1:4310/api/tasks \
  -H "Content-Type: application/json" \
  -H "X-Agent-Board-Secret: change-me" \
  -d '{
    "title": "Добавить onboarding экран",
    "description": "Собрать первую версию",
    "project": "palomatika"
  }'
```

### Complete Task

```bash
curl -X POST http://127.0.0.1:4310/api/completions \
  -H "Content-Type: application/json" \
  -H "X-Agent-Board-Secret: change-me" \
  -d '{
    "task_id": 1,
    "completed_by": "codex",
    "summary": "Собран MVP доски и completion-ленты",
    "used_mcp_ids": ["mcp-palomatika-db"],
    "used_skill_ids": ["skill-brainstorming", "skill-writing-plans"]
  }'
```

## Test

```bash
cd agent-board
npm test
```
