---
name: deploy-ops
description: "Use when deploying, managing CI/CD, clearing caches, running artisan commands on production, or working with the deploy webhook. Covers deploy workflow, auto-merge, and post-deploy automation."
---

# Deploy & Operations

## Хостинг

**Хостинг:** Timeweb (cw95865.tmweb.ru)
**Путь на сервере:** /home/c/cw95865/OGE/
**Production URL:** https://cw95865.tmweb.ru

## Как код попадает на прод (с 2026-08-07)

1. **Auto-merge:** ветки `claude/*` автоматически мержатся в `main` после push. Это единственное, что делает GitHub Actions.
2. **Доставка — вручную, сразу после пуша:** `scripts/deploy-prod.sh` с dev-VPS.

**Пуш в `claude/*` больше НЕ означает, что код на проде.** Автодеплой по FTP из
Actions убран: он регулярно отваливался по таймауту, причём первый шаг мог
помечаться зелёным без реального трансфера — `main` уезжал вперёд, а прод молча
оставался на старом коде (так, например, `resources/views/learn/minus-factoring.blade.php`
из мёржа 04.08 на прод не попал вовсе и обнаружился только сверкой хешей).

```bash
scripts/deploy-prod.sh              # от маркера на проде до origin/main
scripts/deploy-prod.sh --dry-run    # только показать, что поедет
scripts/deploy-prod.sh --base <sha> # если маркера ещё нет или он врёт
```

Скрипт: считает изменённые рантайм-файлы → заливает по FTP **только те, чей MD5
на проде не совпал** (3 попытки) → сверяет каждый после заливки → обновляет
маркер `storage/app/deployed-commit.txt` → зовёт `deploy:refresh`. При любом
несошедшемся файле маркер не двигается и `deploy:refresh` не зовётся — «залито»
всегда значит «проверено».

Что скрипт НЕ делает: не удаляет с прода файлы, удалённые в репозитории (только
печатает их списком) и не трогает `vendor/`, `tests/`, `docs/`, `*.md`, `.claude/`,
`.github/`.

**Секреты:** `TMW_PSW` — `/home/dev/.agent-secrets/timeweb.env`, `DEPLOY_WEBHOOK_SECRET` — `.mcp.json`.

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

`POST /api/deploy/refresh` — зовётся из `scripts/deploy-prod.sh` после заливки.
Требует заголовок `X-Deploy-Secret`. Он же прогоняет миграции: команды с флагами
(`migrate --force`) whitelist не пропускает.

## Заливка одного файла руками

```bash
set -a; . /home/dev/.agent-secrets/timeweb.env; set +a
curl -T <файл> "ftp://cw95865.tmweb.ru/OGE/<путь>" --user "cw95865:$TMW_PSW" --ftp-create-dirs
curl -s --user "cw95865:$TMW_PSW" ftp://cw95865.tmweb.ru/OGE/<путь> | md5sum   # сверка
```

Тем же FTP читаются логи прода — при разборе инцидента это единственный способ
увидеть, что упало: `.../OGE/storage/logs/laravel.log`, `.../OGE/storage/logs/hw-photos.log`.
Access-логи Timeweb в аккаунт не кладёт, а 419/413 и ошибки валидации в
`laravel.log` не пишутся.

## Деплой с самого сервера (если есть шелл)

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
