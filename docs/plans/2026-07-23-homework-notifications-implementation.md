# Уведомления ученику о домашке — план (Фаза 1)

> REQUIRED SUB-SKILL: executing-plans. Дизайн: `2026-07-23-homework-notifications-design.md`.

**Ветка:** `claude/lesson-v2` (auto-merge → прод). Каталог `/home/dev/palomatika-lesson-v2`.
**Стек:** Laravel 10, Alpine (CDN), phpunit, планировщик уже работает на проде.

---

### Task 1: Сохранять `deadline` в ДЗ (TDD)

**Files:** `app/Http/Controllers/Pwa/TeacherController.php` (`assignHomework` валидация + `assignFromPicker`); тест `tests/Feature/Pwa/PwaHomeworkPhotoPracticeTest.php`.

- Тест: POST assign с `deadline=2026-08-01` + picker_tasks → `Homework::latest()->deadline_at` = эта дата.
- Валидация: `'deadline' => 'nullable|date'`. В `assignFromPicker` перед save: `if ($d = $request->input('deadline')) $homework->deadline_at = $d;`.
- Прогнать весь `PwaHomeworkPhotoPracticeTest`. Commit.

---

### Task 2: Миграции полей уведомлений

**Files:** новые миграции; модели `HomeworkAssignment`, `User`.

- `homework_assignments`: `notified_at` (datetime null), `reminded_at` (datetime null).
- `users`: `homework_popup_shown_on` (date null).
- В `$fillable`/`$casts` добавить поля. `php artisan migrate` (прогнать тест на RefreshDatabase). Commit.

---

### Task 3: `StudentNotifier` (TDD)

**Files:** `app/Services/StudentNotifier.php`; тест `tests/Unit/StudentNotifierTest.php`.

Метод `notify(User $student, string $text, ?string $url = null): bool`.
- Телеграм-ученик (`oauth_provider==='telegram'`, есть `oauth_id`, есть `bot_token`) →
  `Http::timeout(5)->post("https://api.telegram.org/bot{token}/sendMessage", [...])`
  с `chat_id=oauth_id`, `parse_mode=HTML`, `reply_markup` inline-кнопка URL (если `$url`).
  Использовать **фасад `Http`** (не Guzzle напрямую) — для `Http::fake()`.
- Не-телеграм → вернуть `false`, без запроса.
- Исключение/не-2xx → `Log::warning`, вернуть `false` (не бросать).
- Тесты (`Http::fake`): телеграм → один POST на нужный URL с chat_id; google-ученик → `Http::assertNothingSent`; ошибка сети → не падает, `false`.

Commit.

---

### Task 4: Уведомление о новом ДЗ (TDD)

**Files:** `TeacherController::assignFromPicker` (после создания assignments); модель `HomeworkAssignment`; тест в `PwaHomeworkPhotoPracticeTest`.

- После создания assignments: по каждому `notify()`: «📚 Тебе задали домашку: {title}, {N} задач.» (+ «Срок: {d.m}» если есть) + URL `https://student.{base_domain}/homework`. Успех/попытка → `assignment->notified_at = now()`.
- Идемпотентность: слать только где `notified_at === null`.
- Тест (`Http::fake`): назначение телеграм-ученику → отправлен 1 запрос, `notified_at` не null; google-ученику → `notified_at` остаётся null (или ставим, но без запроса — решить: ставим только при попытке телеграма; для не-телеграм оставляем null, поп-ап покроет).
- Прогнать полный homework-набор. Commit.

---

### Task 5: In-app поп-ап раз в день (TDD)

**Files:** `app/Http/View/Composers/HomeworkPopupComposer.php` (new); `app/Providers/AppServiceProvider.php` (bind composer к `layouts.pwa`); партиал `resources/views/pwa/_shared/homework-popup.blade.php` (new) + подключить в `layouts/pwa.blade.php`; тест `tests/Feature/Pwa/HomeworkPopupTest.php`.

- Composer: если `Auth::user()?->role==='student'`, текущий роут НЕ `pwa.student.homework*`/lesson, есть assignment `status != completed`, и `user->homework_popup_shown_on != today()` →
  собрать данные ближайшего по сроку (или новейшего) ДЗ, `View::share('homeworkPopup', [...])`,
  и `user->update(['homework_popup_shown_on' => today()])`. Иначе share `null`.
- Партиал: если `$homeworkPopup` — центральный модал (Alpine `x-data="{open:true}"`): заголовок, «{done}/{total}», кнопки «Перейти к ДЗ» (ссылка на `/homework`) и «Закрыть» (`open=false`). Стиль как `.ns-overlay` из lesson-prep (по центру, оверлей).
- Тесты: студент с невыполненным ДЗ и `shown_on=null` → GET дашборд-страница содержит модал + `shown_on` стал сегодня; повторный GET в тот же день → модала нет; студент без ДЗ → нет модала; на странице `/homework` → нет модала.
- Commit.

---

### Task 6: Крон-напоминания о сроке (TDD)

**Files:** `app/Console/Commands/RemindHomeworkDeadlines.php` (new); `app/Console/Kernel.php` (schedule daily); тест `tests/Feature/RemindHomeworkDeadlinesTest.php`.

- Команда `homework:remind-deadlines`: assignments `status != completed`, `homework.deadline_at` в [сегодня, завтра], `reminded_at` null или < сегодня → `notify()` «⏰ Напоминание: {title} до {d.m}. [Открыть]», ставит `reminded_at=now()`.
- Kernel: `$schedule->command('homework:remind-deadlines')->dailyAt('08:00')` (рядом с grades:promote).
- Тесты: незавершённое с дедлайном завтра → отправлено + `reminded_at` стоит; завершённое → нет; без дедлайна → нет; уже напоминали сегодня → нет.
- Commit.

---

### Task 7: Прогон, деплой, проверка

- Полный прогон затронутых наборов + `StudentNotifierTest` + новые.
- Playwright-проверка поп-апа (как с домашкой: локально на тестовой БД, `/qa/login` студентом, дашборд → модал).
- Push → auto-merge+FTP → `migrate --force` + `deploy:refresh` вебхуком.
- Обновить `.claude/product/modules/homework.md` + память. Commit.

## Заметки

- Не слать телеграм в тестах по-настоящему — `Http::fake()` везде.
- Web push — Фаза 2, не трогаем.
- `base_domain` для ссылок — `config('app.base_domain')`.
