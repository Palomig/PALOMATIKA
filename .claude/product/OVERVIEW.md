# Palomatika — карта продукта

> Это knowledge base для агента: что есть в продукте, как связано, статус (prod/dev). Обновляется по мере значимых изменений. Источник истины — код, эта карта — навигация.
> **Дата последнего полного скана:** 2026-05-07 (после редизайна — эпик #44).

## Что это за продукт

EdTech SaaS для подготовки к школьным экзаменам по математике в России. Три обособленных направления:

| Направление | Класс | Что это |
|---|---|---|
| **ОГЭ** | 9 | Основной государственный экзамен |
| **ЕГЭ** | 11 | Единый государственный экзамен |
| **ВПР** | 5–8 | Всероссийская проверочная работа |

**Концепция:** банк заданий + конструктор ДЗ для учителей. Ученики бесплатно решают задачи; монетизация (когда наступит) — через учителей и репетиторов.

> **Что было раньше (выпилено в эпике #44, май 2026):** концепция «обучение через пазлы» (модели Task/TaskStep/StepBlock и связанные `step_blocks` ~27к строк), Duolingo-стиль геймификация (badges/leagues/duels/streaks/gifts/challenges), биллинг учеников (subscriptions/payouts), AI-помощник (был только колонкой), партнёрская программа в её 30%-варианте. Отложено отдельным решением: Telegram Stars (`star_transactions`, `tg_premium_until`, премиум-модалка в PWA).

**Production:** https://palomatika.ru (Timeweb)

## Топология доменов

| Домен | Кто пользуется | Что там |
|---|---|---|
| `palomatika.ru` | учителя, админы, гости | лендинг, teacher-админка (`topics`, `vpr-topics`, `ege`), kanban/roadmap, materials, board |
| `student.palomatika.ru` | ученики (PWA) | dashboard, mini, part2, tasks-part1, homework, history, practice, ege-app, vpr |
| `teacher.palomatika.ru` | учителя (PWA) | dashboard, students, lessons, homework, variants, referrals |
| `parent.palomatika.ru` | родители | child dashboard, child-homework |

ОГЭ-генератор (`/oge/*`) на главном домене **редиректит** на `student.palomatika.ru` — миграция в PWA продолжается (текущая ветка `claude/pwa-migration`).

## Технический стек

- **Backend:** Laravel 10 LTS, PHP 8.2+
- **DB:** MySQL 8.0
- **Frontend:** Tailwind CSS 3 (CDN) + Alpine.js 3 (CDN). **Никаких сборщиков.**
- **PWA:** Service Worker + Web Manifest, multi-domain
- **Auth:** email/password, Telegram Login, Telegram Mini App, Google OAuth
- **Деплой:** ветки `claude/*` → авто-merge в `main` → Timeweb. Post-deploy: `php artisan deploy:refresh`. Подробности — скилл `deploy-ops`.

## Роли пользователей

| Роль | DB column | Доступ |
|---|---|---|
| `student` | `users.role` | PWA student, решает задачи, отслеживает прогресс |
| `teacher` | `users.role` | PWA teacher + `palomatika.ru/topics`/`/vpr-topics`/`/ege`, создаёт ДЗ, видит учеников |
| `admin` | `users.role` | всё то же что учитель + полный доступ |
| `parent` | `users.role` | parent.palomatika.ru, видит ДЗ ребёнка |

Учителя ↔ ученики связаны через `teacher_students` (100 строк на проде).

## Карта доменов продукта

> Каждый блок — модуль продукта. Файлы детализации в `modules/` создаются по мере необходимости.

### 1. Банки заданий (фундамент)
- **ОГЭ:** 17 топиков (06–21, 23) с заданиями, ~2.5к задач — `storage/app/tasks/topic_*.json`
- **ЕГЭ:** 18 топиков (01–19 кроме 03) — `storage/app/tasks/ege/topic_*.json`
- **ВПР:** 4 класса × 18 топиков (5–6 заполнены, 7–8 пустые скелеты) — `storage/app/tasks/vpr/grade_{N}/topic_*.json`
- Подробности → [modules/task-banks/_overview.md](modules/task-banks/_overview.md)
- Скиллы для работы с данными: `oge-tasks`, `ege-tasks`, `geometry-svg`

### 2. Варианты (Variants)
Генерация экзаменационных вариантов из банка задач. Хеш-ссылки (`/oge/{hash}`).

| Сервис | Назначение |
|---|---|
| `OgeVariantBuilderService` | детерминированный билд ОГЭ-варианта по хешу |
| `EgeVariantBuilderService` | то же для ЕГЭ |
| `VprVariantBuilderService` | то же для ВПР (с учётом класса 5–8) |
| `MiniVariantService` | мини-вариант (быстрая практика) |
| `AdaptiveVariantService` | адаптивная генерация под слабые навыки |
| `OgeVariantPoolService` | пул предсобранных вариантов |

DB: `oge_variants` (~161), `oge_variant_pool` (~139), `oge_variant_pool_tasks` (~978), `curated_variant_tasks`.

### 3. Попытки (Attempts) — единый стек на `oge_attempts`
Жизненный цикл попытки решения: старт → ответы → submit → скоринг. Используется и для ОГЭ, и для ЕГЭ, и для ВПР через одну модель `OgeAttempt`.

| Таблица | Rows на проде | Что |
|---|---|---|
| `oge_attempts` | ~538 | основная попытка |
| `oge_attempt_answers` | ~1855 | ответы по задачам |
| `oge_attempt_events` | ~4293 | focus/blur/visibility события |
| `oge_attempt_scorings` | ~2787 | результаты скоринга |
| `oge_attempt_task_details` | ~2745 | детали по задачам |
| `oge_attempt_task_timings` | ~46 | тайминги |

Сервисы: `OgeAttemptService`, `OgeAttemptSuspicionService`, `TaskAnswerResolver`.

> Старая модель `Attempt` + таблицы `attempts`/`attempt_steps`/`step_block_selections` (часть пазлового стека) дропнуты в #44/#46.

### 4. Домашка (Homework) — **в активной разработке**
Активные типы: `topic_photo_practice` (фото-решений) и `full_variant` (mini-variant). На проде: 2 ДЗ, 10 photo-practice задач, 0 сабмишнов. Подробности → [modules/homework.md](modules/homework.md).

### 5. Practice / Mini-Games
Мини-игры (уравнения, графики) с лидербордом. Лидерборд имеет разрезы **Все / Школа / Класс / Группа** (без лиг/бейджей/стриков). DB: `practice_game_runs` (~56), сервисы `PracticeGameService`, `PracticeGraphRenderer`, `PracticeLeaderboardService`. Контроллер `Pwa/PracticeController`.

### 6. Skills & Mastery
Навыки и трекинг владения темами. DB: `skills` (60), `student_topic_mastery` (~1228), `user_skills`. Сервис `StudentAnalyticsService`. Привязка `task_skills` (со старыми задачами) дропнута вместе с пазлами.

### 7. Уроки и расписание
DB: `lesson_schedule`, `lesson_attendance` (обе 0 на проде — фича в dev). Интеграция с **Evrium** (внешний API расписания, см. memory/reference_evrium_schedule.md).

### 8. Группы / классы
DB: `student_groups`, `student_group_members` (0 на проде). Учителя пока работают со списком учеников через `teacher_students`.

### 9. Реферальная программа
`referral_clicks` (0), модель `ReferralClick`. Используется только для отслеживания статистики переходов. Учительские выплаты (`teacher_payouts`) дропнуты в #44/#49.

### 10. Учебные материалы (Jarvis)
`jarvis_materials` (~40 на проде) — статьи/материалы с категориями. Контроллер `JarvisMaterialPageController`. Маршруты `/materials`, `/materials/{slug}`.

### 11. Аудит и аналитика
- `audit_events` — ~3735 строк (активно используется)
- `task_answer_overrides` (5) + `task_answer_override_logs` (6) — учительские правки эталонных ответов
- Сервис `AuditLogger`, `TaskAnswerProvenanceService`

### 12. Bug reports
`bug_reports` (~9 на проде). Модель `BugReport`, контроллер `Pwa/BugReportController`.

### 13. Telegram-интеграция
- Telegram Login + Telegram Mini App (старый Mini App **выпилен** в пользу PWA — коммит `e931901`)
- `telegram_auth_tokens`, `MiniAppAuthController`, `TelegramMiniAppAuthService`
- **Telegram Stars / премиум** (`star_transactions`, `users.tg_premium_until`/`tg_trial_used`/`star_balance`, премиум-модалка в PWA) — **отложено отдельным решением** в #44; код жив на проде, но фича не используется (0 транзакций).

### 14. Parent App
Отдельный родительский фронт на `parent.palomatika.ru`. DB: `parent_student` (1 на проде). Контроллеры `ParentAppController`, `ParentAuthController`.

### 15. Kanban / Roadmap / Архитектура (внутренние страницы)
`/kanban`, `/roadmap`, `/forstas` — для команды. Источник: `.claude/tasks.json` (legacy in-app kanban). Контроллер `BoardController`.

> Параллельно — **локальный Agent Board** (`http://127.0.0.1:4310`, SQLite в `agent-board/data/`) для координации Claude/Codex по задачам этого проекта. Источник истины при вопросах «что на доске/что сделано».

## Что было выпилено в редизайне (#44)

Эпик #44 (май 2026, 8 подзадач) полностью завершён:

| Подзадача | Что снесено |
|---|---|
| #46 пазлы | модели Task/TaskStep/StepBlock/StepBlockSelection/PuzzleTemplate/Topic/Attempt/AttemptStep, контроллеры Api/TaskController/TopicController/HomeworkController, команды GeneratePuzzles/GenerateOgeTasks; таблицы topics/tasks/task_skills/task_steps/step_blocks/puzzle_templates/attempts/attempt_steps/step_block_selections/homework_tasks; колонка homeworks.topic_id |
| #47 геймификация | модели Badge/UserBadge/League/LeagueParticipant/Duel/UserStreak/UserDailyStat/UserGift/Challenge/ChallengeTeam, команда premium:gift, сидеры BadgeSeeder/LeagueSeeder; таблицы badges/user_badges/leagues/league_participants/duels/duel_tasks/challenges/challenge_teams/challenge_team_members/user_streaks/user_daily_stats/user_gifts |
| #49 биллинг | модели Subscription/PayoutItem/TeacherPayout; таблицы subscriptions/payout_items/teacher_payouts; колонки users.subscription_plan/subscription_ends_at/trial_ends_at |
| #50 AI-помощник | колонка users.has_ai_addon (сам код AI-помощника отсутствовал ещё до эпика) |
| #51 маркетинг | мёртвые ссылки в nav-student, обновлён board/architecture |

Все миграции односторонние с inline JSON-бэкапом в `storage/app/backups/redesign-44-{puzzles,gamification,billing}/`. План: [docs/plans/2026-05-05-redesign-direction.md](../../docs/plans/2026-05-05-redesign-direction.md).

## Глоссарий

| Термин | Что значит |
|---|---|
| **топик** (topic) | номер задания на ОГЭ/ЕГЭ или тема в ВПР. Например, ОГЭ topic_15 = задание №15 на экзамене (геометрия) |
| **задание** (zadaniya) | блок внутри топика с одной формулировкой и набором задач |
| **задача** (task) | конкретное условие с ответом (в JSON-банке, не в БД) |
| **вариант** (variant) | сборка задач для имитации экзамена |
| **попытка** (attempt) | сессия ученика по решению варианта/мини-варианта/ДЗ |
| **навык** (skill) | абстрактный умение, привязан к ученикам через `user_skills`/`student_topic_mastery` |
| **mastery** | уровень владения темой/навыком у ученика |

## Что важно знать про текущее состояние

- **Активная миграция:** ветка `claude/pwa-migration` — UI постепенно переносится в PWA-поддомены.
- **ОГЭ-генератор** на главном домене редиректит в PWA, но контроллер `TestPdfController` (4400+ строк) ещё жив с legacy-роутами `/test/oge/*`.
- **Старый Telegram Mini App выпилен** (`e931901`), вся логика в PWA.
- **Пазлы и геймификация полностью выпилены** в редизайне #44 (май 2026).
- **Биллинг учеников выпилен**, монетизация теперь только через учителей и только в будущем.
- **TG Stars отложен** — код и таблица `star_transactions` живы, фактических транзакций нет.
- **Главный фокус:** photo-practice ДЗ + practice mini-games + Evrium интеграция.

## Как пользоваться этой картой

1. Перед задачей — прочитай этот файл (он всегда подгружается через CLAUDE.md).
2. Если задача про конкретный домен — открой `modules/{name}.md` (если файла нет — не хватает knowledge, скажи об этом и предложи создать).
3. Для деталей кода (сервисы, JSON-структура, SVG) — соответствующий скилл (`oge-tasks`, `ege-tasks`, `geometry-svg`, `deploy-ops`).
4. Для prod-данных в реальном времени — MCP `palomatika-db`.
