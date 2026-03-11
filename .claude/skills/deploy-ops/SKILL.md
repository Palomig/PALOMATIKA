---
name: deploy-ops
description: "Use when deploying, managing CI/CD, clearing caches, running artisan commands on production, or working with the deploy webhook. Covers deploy workflow, auto-merge, and post-deploy automation."
---

# Deploy & Operations

## Хостинг

**Хостинг:** Timeweb (cw95865.tmweb.ru)
**Путь на сервере:** /home/c/cw95865/OGE/
**Production URL:** https://cw95865.tmweb.ru

## Автоматический CI/CD

1. **Auto-merge:** Ветки `claude/*` автоматически мержатся в `main` после push
2. **Auto-deploy:** После merge в `main` — автоматический FTP deploy
3. **Post-deploy refresh:** Webhook вызывает `deploy:refresh`

**ВАЖНО:** Любые изменения в ветках `claude/*` попадут на production автоматически!

## Post-deploy команда: `deploy:refresh`

```bash
php artisan deploy:refresh              # Полный refresh
php artisan deploy:refresh --skip-svg   # Пропустить SVG
php artisan deploy:refresh --force      # Принудительно все SVG
php artisan deploy:refresh --no-cache   # Не прогревать кэши
```

Что делает:
- Очищает все кэши Laravel (config, route, view, cache)
- Перегенерирует SVG где `*_geometry.json` новее `*.json`
- В production — прогревает кэши

## Webhook

`POST /api/deploy/refresh` — вызывается GitHub Actions после FTP deploy.
Требует заголовок `X-Deploy-Secret`.

## Ручной деплой

```bash
cd /home/c/cw95865/OGE
git pull origin main
php artisan migrate --force
php artisan deploy:refresh
```

## Авто-bake при разработке

В **local** environment `TaskDataService` автоматически запускает `svg:bake`, если `geometry.json` новее основного JSON.

## Production API (MCP)

Все эндпоинты требуют заголовок `X-Deploy-Secret`.

| Метод | URL | Описание |
|-------|-----|----------|
| `POST` | `/api/deploy/query` | Read-only SQL |
| `GET` | `/api/deploy/tables` | Список таблиц |
| `POST` | `/api/deploy/artisan` | Artisan-команда |
| `POST` | `/api/deploy/refresh` | Deploy refresh |
| `GET` | `/api/deploy/commands` | Список команд |

## Разрешённые artisan-команды

`deploy:refresh`, `migrate`, `migrate:status`, `cache:clear`, `config:clear`, `route:clear`, `view:clear`, `config:cache`, `route:cache`, `svg:bake`, `svg:bake-ege`, `pool:sync`, `pool:flush`, `oge:rescore-attempts`, `oge:backfill-answers`, `tasks:add-status`, `tasks:set-status`, `task-statuses:import`, `audit:prune`, `assets:audit-semantic-svg`

## Очистка кэша

```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

## Ключевые файлы

| Файл | Назначение |
|------|------------|
| `.mcp.json` | Конфигурация MCP серверов |
| `mcp-servers/palomatika-db/index.js` | MCP сервер для production БД |
| `app/Http/Controllers/Api/DeployController.php` | API контроллер |
| `.github/workflows/auto-merge.yml` | CI/CD workflow |
