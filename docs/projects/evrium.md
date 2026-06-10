# Evrium

> Тройная платформа: обучающая геометрия + CRM репетитора + система зарплат учителей.
> **Репозиторий:** `Palomig/evrium` · **Прод:** https://эвриум.рф/ (Timeweb) · **Источник:** `evrium/CLAUDE.md` (обновлён 2025-11-17).

## Что это — три интегрированных системы

### 1. Геометрия (корень сайта)
- Обучающий сайт по геометрии 7–9 класс по учебнику Атанасяна.
- Интерактивные SVG-визуализации, навигация по главам, редактор упражнений, документация.
- Стек: PHP + vanilla JS + Bootstrap 5. Точка входа `index.php`.
- Контент: 12 глав (3 класса), 100+ тем, 300+ интерактивных задач. Вся учебная программа — в `config.php` (~96KB).
- URL: `https://эвриум.рф/`.

### 2. CRM (управление репетиторством) — `/crm/`
- CRM для репетиторов: ученики, уроки, платежи, трекинг навыков.
- CRUD учеников, учёт уроков, финансы, REST API, PDF-отчёты.
- Стек: PHP 8 + MySQL + Bootstrap 5. Вход: `crm/login.php`, URL `https://эвриум.рф/crm/`.
- БД: 8 таблиц (+2 view, +3 триггера), 10+ REST-эндпоинтов, 5 отслеживаемых навыков.
- Роли: Teacher, SuperAdmin.

### 3. Zarplata (зарплаты учителей) — `/zarplata/`
- Автоматический расчёт зарплат и расписание для учителей.
- Шаблонное расписание, формулы выплат, учёт посещаемости, (план) интеграция с Telegram-ботом.
- Стек: PHP 8 + MySQL + Material Design (тёмная тема) + vanilla JS, шрифт Montserrat. Вход `zarplata/login.php`, URL `https://эвриум.рф/zarplata/`.
- БД: 10 таблиц (`users`, `teachers`, `students`, `payment_formulas`, `lessons_template`, `lessons_instance`, `attendance_log`, `payments`, `audit_log`, `settings`), 2 view (`teacher_stats`, `lessons_stats`), 2 триггера (авторасчёт выплаты при завершении урока, аудит посещаемости).
- Типы формул выплат: `min_plus_per`, `fixed`, `expression`.
- Роли: Admin, Owner.
- `zarplata/bot/` — Telegram-бот (запланирован).

## Прочее
- `android-app/` — Android-приложение (Gradle-проект).
- Директории по предметам в корне: `matematika`, `fizika`, `informatika`, `ege`, `oge`, `oge-zadachi` и др. (лендинги/контент).

## Деплой
- GitHub Actions: push в `claude/**` → авто-merge в `main` (`auto-merge.yml`) → деплой на Timeweb по FTP (`deploy-timeweb.yml`). PR вручную создавать не нужно. Подробности — `evrium/DEPLOYMENT.md`.

## Связь с Palomatika
- Расписание уроков Palomatika тянется из Evrium по `users.evrium_teacher_id` (Стас=1, Руслан=2), изоляция per-teacher. См. [_index](_index.md) и `.claude/product/OVERVIEW.md` §7.
</content>
