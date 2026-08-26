---
name: deploy-ops
description: "Use when deploying, managing CI/CD, clearing caches, running artisan commands on production, or working with the deploy webhook. Covers deploy workflow, auto-merge, and post-deploy automation."
---

# Deploy & Operations

## Хостинг

**Хостинг:** Timeweb (cw95865.tmweb.ru)
**Путь на сервере:** /home/c/cw95865/OGE/
**Production URL:** https://cw95865.tmweb.ru

## Как код попадает на прод (схема с 2026-08-26)

```
claude/**  ──авто-мёрж──►  develop        песочница, на прод НЕ едет
                              │
   (Стас сказал «готово»)     │  scripts/promote.sh claude/<фича>
                              ▼
                            main  ──FTP+MD5──►  прод
```

**`main` == прод.** Ветка попадает в `main` только через `scripts/promote.sh`,
то есть только по решению Стаса.

**`develop` в `main` не мержится НИКОГДА.** Это интеграционная песочница: влить
её в main значит утащить на прод всю незрелую работу разом — ровно та схема, от
которой ушли. Выпускается всегда ветка конкретной фичи.

**Ветку фичи ответвляй от `main`, не от `develop`:**
```bash
git fetch origin
git worktree add ../palomatika-<фича> -b claude/<фича> origin/main
```
Если ответвить от `develop`, ветка потащит в `main` чужую незрелую работу.

### Выпуск на прод

```bash
scripts/promote.sh claude/<фича> --dry-run   # что поедет: коммиты, файлы, миграции
scripts/promote.sh claude/<фича>             # мёрж в main + доставка
scripts/promote.sh claude/<фича> --no-deploy # только влить в main
```
Мёрж делается в отдельном временном worktree — рабочее дерево не трогается.

### Почему так (2026-08-26)

Раньше `claude/**` авто-мержились сразу в `main`, а `main` деплоится — фича
становилась релизной в момент первого пуша. Генератор печатных вариантов месяц
провисел в `main` незалитым; чтобы срочно выкатить фикс слияния аккаунтов,
пришлось деплоить точечно (`--base`/`--rev`) и откатывать маркер руками. После
этого никто уже не знал, что реально лежит на проде.

### Доставка

```bash
scripts/deploy-prod.sh              # от маркера на проде до origin/main
scripts/deploy-prod.sh --dry-run    # только показать, что поедет
scripts/deploy-prod.sh --base <sha> # если маркера ещё нет или он врёт
scripts/deploy-prod.sh --allow-dirty  # разрешить незакоммиченные правки
```

**Пуш в `claude/*` НЕ означает, что код на проде.** Автодеплой по FTP из Actions
убран 2026-08-07: он отваливался по таймауту, причём первый шаг мог помечаться
зелёным без реального трансфера (так `resources/views/learn/minus-factoring.blade.php`
из мёржа 04.08 на прод не попал вовсе и обнаружился только сверкой хешей).

Скрипт: считает изменённые рантайм-файлы → заливает по FTP **только те, чей MD5
на проде не совпал** (3 попытки) → сверяет каждый после заливки → обновляет
маркер `storage/app/deployed-commit.txt` → зовёт `deploy:refresh`. При любом
несошедшемся файле маркер не двигается и `deploy:refresh` не зовётся — «залито»
всегда значит «проверено».

### Стоп-кран: дерево обязано быть деплоимой ревизией

Скрипт заливает файлы **из рабочего дерева** (`curl -T`), а список берёт из
`git diff`. Отставший чекаут молча выложит на прод старые версии файлов, и
MD5-сверка не поможет — она сверяет прод с тем, что залили, а не с ревизией.
26.08.2026 главный чекаут отставал на 51 коммит: деплой из него откатил бы прод
на месяц назад. Скрипт теперь сам отказывается работать, если `HEAD` не равен
деплоимой ревизии или в дереве есть незакоммиченные правки.

### Сверка «что реально на проде»

Маркер может врать (его двигают руками при точечных выкатках). Честная проверка —
MD5 через FTP против локальной ревизии:

```bash
set -a; . /home/dev/.agent-secrets/timeweb.env; set +a
curl -s --ftp-pasv -u "cw95865:$TMW_PSW" ftp://cw95865.tmweb.ru/OGE/<path> | md5sum
md5sum <path>
```
Пачкой — `xargs -P5`: ~90 файлов за пару минут.

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
