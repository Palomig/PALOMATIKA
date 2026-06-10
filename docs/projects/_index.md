# База знаний по проектам Стаса

> Портфолио проектов: что это, статус, стек, где живёт. Источник истины — код в репозиториях; эта база — навигация (задача #68 на Agent Board).
> **Дата последнего обновления:** 2026-06-10.

## Обзор

| Проект | Статус | Что это | Стек | Заметка |
|---|---|---|---|---|
| [Palomatika](../../.claude/product/OVERVIEW.md) | ✅ прод + актив. разработка | EdTech SaaS: банк заданий ОГЭ/ЕГЭ/ВПР + конструктор ДЗ | Laravel 10, PHP 8.2, MySQL, Alpine/Tailwind | Полная карта продукта — в `.claude/product/OVERVIEW.md` |
| [Evrium](evrium.md) | ✅ прод | Геометрия (7–9 кл) + CRM репетитора + Zarplata (зарплаты учителей) | PHP 8, MySQL, Bootstrap 5, vanilla JS | `эвриум.рф`, Timeweb. Расписание интегрируется с Palomatika |
| [dota2-predictor](dota2-predictor.md) | 🟡 в разработке | Python ML — предсказание матчей Dota 2 | Python, ML | ⚠️ нет на dev-сервере, детали не собраны |
| [claude-telegram-bot](claude-telegram-bot.md) | ❌ не работает | Telegram-бот поверх Claude | — | Выдаёт ошибки, разобраться позже |
| [telegram-bot](telegram-bot.md) | ⏸ устарел | Старая версия TG-бота | — | Заменён на claude-telegram-bot |
| [CRM-repetit](crm-repetit.md) | 🧪 тестовая | «test version of website» (GitHub Palomig/CRM-repetit) | — | Тестовый/стейджинг-вариант сайта |

Статусы: ✅ продакшен · 🟡 в разработке · 🧪 тест · ⏸ устарел · ❌ не работает.

## Связь между проектами

- **Palomatika ↔ Evrium:** расписание уроков Palomatika берётся из Evrium по `users.evrium_teacher_id` (Стас=1, Руслан=2), изоляция per-teacher. См. `.claude/product/OVERVIEW.md` §7 и memory `palomatika-evrium-teacher-mapping`.
- **Обе платформы** на Timeweb с авто-деплоем `claude/** → main → FTP` через GitHub Actions.

## Инфраструктура

- **GitHub (Palomig):** `PALOMATIKA`, `evrium`, `CRM-repetit`, `VBoxHardenedLoader` (форк).
- **Dev-сервер:** AdminVPS `78.17.28.40` / `palomig.com` — НЕ прод, периодически зависает (мониторинг + earlyoom).
- **Agent Board:** `http://78.17.28.40:4310/` — координация задач Claude/Codex.

## TODO базы знаний

- [ ] Собрать детали по dota2-predictor (репозиторий, как запускается, датасет, модель).
- [ ] Разобраться с ошибками claude-telegram-bot и задокументировать.
- [ ] Уточнить назначение CRM-repetit (тест чего именно).
</content>
