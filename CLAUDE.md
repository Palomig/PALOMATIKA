# PALOMATIKA - EdTech Platform for OGE, EGE & VPR Math Preparation

## О проекте

**PALOMATIKA** — EdTech SaaS платформа для подготовки к школьным экзаменам по математике:
- **ОГЭ** — 9 класс (Основной Государственный Экзамен)
- **ЕГЭ** — 11 класс (Единый Государственный Экзамен)
- **ВПР** — 5–8 класс (Всероссийская Проверочная Работа)

**ВАЖНО:** ОГЭ, ЕГЭ и ВПР — это **три обособленных направления**, они не пересекаются.

**Концепция:** банк заданий + конструктор ДЗ для учителей. Ученики решают задачи бесплатно; будущая монетизация — через учителей и репетиторов. Старая концепция «обучения через пазлы» и Duolingo-стиль геймификация выпилены в эпике #44 (май 2026).

**Production URL:** https://palomatika.ru (Timeweb hosting). PWA на поддоменах: `student.palomatika.ru`, `teacher.palomatika.ru`, `parent.palomatika.ru`.

---

## 📍 Карта продукта

Полная карта проекта (домены, модули, статус, glossary) — в [.claude/product/OVERVIEW.md](.claude/product/OVERVIEW.md). **Читай при старте любой нетривиальной задачи.**

Детализация по модулям — в `.claude/product/modules/`:
- [task-banks/_overview.md](.claude/product/modules/task-banks/_overview.md) — все 3 банка заданий с цифрами
- [homework.md](.claude/product/modules/homework.md) — домашка (в активной разработке)

При значимых изменениях кода — обновляй соответствующий модуль (это часть definition of done). Если нужного модуля нет — создай.

---

## КРИТИЧЕСКИ ВАЖНЫЕ ПРИНЦИПЫ

### 1. SVG — основа проекта
- **НИКОГДА** не использовать PNG/JPEG из PDF
- **ВСЕГДА** создавать собственные SVG
- PDF изображения (`docs/oge_data/images/`, `docs/ege_data/images/`) — **только референс**

### 2. Единый источник данных
- ОГЭ: `storage/app/tasks/topic_{id}.json` (геометрия: `*_geometry.json`)
- ЕГЭ: `storage/app/tasks/ege/topic_{id}.json`
- ВПР: `storage/app/tasks/vpr/grade_{N}/topic_{id}.json` (классы 5–8)
- `TaskDataService` / `EgeTaskDataService` / `VprTaskDataService` — единственные сервисы для доступа к данным
- Никаких захардкоженных данных в контроллерах или views

### 3. Номера заданий = номера в PDF
- НЕЛЬЗЯ менять номера для "красоты"
- Текст и ответы — точно как в источнике

---

## Технический стек

| Технология | Версия | Назначение |
|------------|--------|------------|
| PHP | 8.2+ | Backend |
| Laravel | 10 LTS | Framework |
| MySQL | 8.0 | База данных |
| Tailwind CSS | 3.x (CDN) | Стили |
| Alpine.js | 3.x (CDN) | Интерактивность |

**Без сборщиков** (Vite/Webpack). Всё через CDN.

---

## Ключевые пути

| Путь | Назначение |
|------|------------|
| `app/Services/TaskDataService.php` | Центральный сервис доступа к заданиям |
| `app/Services/GeometrySvgRenderer.php` | Рендерер SVG из geometry данных |
| `app/Services/OgeVariantBuilderService.php` | Детерминированный генератор вариантов |
| `app/Services/OgeAttemptService.php` | Жизненный цикл попыток + скоринг |
| `app/Services/TaskAnswerResolver.php` | Нормализация и проверка ответов |
| `app/Http/Controllers/TestPdfController.php` | Mega-контроллер (legacy, 4400+ строк) |
| `storage/app/tasks/` | JSON файлы заданий ОГЭ |
| `storage/app/tasks/ege/` | JSON файлы заданий ЕГЭ |
| `public/images/tasks/` | Изображения к заданиям |
| `.claude/tasks.json` | Kanban задачи проекта |

---

## Роли пользователей

| Роль | Описание |
|------|----------|
| `student` | Решает задачи (PWA на `student.palomatika.ru`) |
| `teacher` | Создаёт ДЗ, отслеживает учеников (PWA на `teacher.palomatika.ru` + админка на главном домене) |
| `admin` | Полный доступ |
| `parent` | Видит ДЗ ребёнка (`parent.palomatika.ru`) |

---

## Деплой (кратко)

- Ветки `claude/*` **автоматически** мержатся в `main` и деплоятся на production
- Post-deploy: `php artisan deploy:refresh` (очистка кэшей + SVG регенерация)
- **Подробности:** используй скилл `deploy-ops`

---

## Production доступ (MCP)

MCP сервер `palomatika-db` (настроен в `.mcp.json`):
- `list_tables` — список таблиц с row counts
- `query` — read-only SQL
- `describe_table` — структура таблицы
- `run_artisan` — artisan-команда из whitelist

---

## Скиллы для детальной информации

Детальная документация вынесена в скиллы, которые загружаются **только когда нужны**:

| Скилл | Когда использовать |
|-------|--------------------|
| `geometry-svg` | Работа с SVG для геометрии: создание, редактирование, svg:bake, geometry.json |
| `oge-tasks` | Работа с заданиями ОГЭ: JSON структура, TaskDataService, типы заданий |
| `ege-tasks` | Работа с заданиями ЕГЭ: структура, типы, процесс создания |
| `deploy-ops` | Деплой, CI/CD, кэши, artisan на production, webhook |

---

## Kanban

Основная доска для координации работы Claude/Codex — **Agent Board** на `http://127.0.0.1:4310` (SQLite в `agent-board/data/`). Сюда смотреть на вопросы «что на доске / что сделано» и здесь обновлять статусы задач при работе.

Внутрипроектный legacy-канбан в `.claude/tasks.json` показывается на страницах `/kanban`, `/roadmap`, `/forstas` (контроллер `BoardController`) — это in-app artifact для команды, не место для трекинга текущей работы агента.
