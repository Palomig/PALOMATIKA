# ТЗ: Исправление багов мини-аппа Telegram

**Дата:** 2026-03-08
**Контекст:** Сегодня была проведена серия рефакторингов (p0 → p1 → p2), которые решили часть архитектурных проблем (frozen answers, stable option IDs, safe math parser, deterministic selection). Данное ТЗ покрывает **оставшиеся** баги, которые не были затронуты.

---

## Баг 1 — `test()`: попытка со статусом `scored` не редиректит на результаты

**Критичность:** 🔴 Критичный
**Файл:** `app/Http/Controllers/MiniAppController.php`, строка 454
**Симптом:** Ученик может повторно открыть уже завершённый и проверенный тест как активный, вместо того чтобы увидеть результаты.

**Причина:**
```php
// Текущий код — проверяет только 'submitted'
if ($attempt->status === 'submitted') {
    return redirect('/tg/results/' . $attempt->id);
}
```

Но `OgeAttemptService::submitAttempt()` (строка 234) сразу после сабмита переводит попытку в `scored`. Переход `submitted → scored` происходит синхронно в том же запросе, поэтому к моменту следующего визита попытка уже `scored`, а не `submitted`.

Также не обрабатывается статус `error` — если скоринг упал, попытка зависнет в `error` и тоже откроется как активный тест.

**Исправление:**
```php
if (in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
    return redirect('/tg/results/' . $attempt->id);
}
```

**Почему `error` тоже включён:** Попытка с `error` уже имеет `submitted_at` и финальные ответы. Показывать тест заново бессмысленно — лучше показать результаты (пусть и с неполным скорингом), чем позволить переписать ответы.

**Проверка:** `results()` уже корректно обрабатывает `['submitted', 'scored']` (строка 522). Нужно добавить туда `'error'` тоже.

---

## Баг 2 — `dashboard()`: «последняя попытка» пропадает после скоринга

**Критичность:** 🔴 Критичный
**Файл:** `app/Http/Controllers/MiniAppController.php`, строка 253-256
**Симптом:** Виджет «последний результат» на дашборде показывает пустое состояние, хотя ученик только что завершил тест.

**Причина:**
```php
// Текущий код — ищет только 'submitted'
$lastAttempt = OgeAttempt::where('student_id', $user->id)
    ->where('status', 'submitted')
    ->orderByDesc('submitted_at')
    ->first();
```

Попытка к этому моменту уже `scored`.

**Исправление:**
```php
$lastAttempt = OgeAttempt::where('student_id', $user->id)
    ->whereIn('status', ['submitted', 'scored'])
    ->orderByDesc('submitted_at')
    ->first();
```

---

## Баг 3 — `tutor()`: та же проблема с «последней попыткой»

**Критичность:** 🟡 Средний
**Файл:** `app/Http/Controllers/MiniAppController.php`, строка 601-604
**Симптом:** Страница тьютора не видит свежий результат, слабые темы не показываются.

**Причина:** Идентична багу 2 — фильтр `where('status', 'submitted')`.

**Исправление:**
```php
$lastAttempt = OgeAttempt::where('student_id', $user->id)
    ->whereIn('status', ['submitted', 'scored'])
    ->orderByDesc('submitted_at')
    ->first();
```

---

## Баг 4 — `computeWeakTopics()`: слабые темы считаются без scored-попыток

**Критичность:** 🟡 Средний
**Файл:** `app/Http/Controllers/MiniAppController.php`, строка 1040
**Симптом:** Статистика по слабым темам неполная — не учитывает данные из scored-попыток.

**Причина:**
```php
->where('a.status', 'submitted')
```

**Исправление:**
```php
->whereIn('a.status', ['submitted', 'scored'])
```

---

## Баг 5 — `results()`: не принимает попытки со статусом `error`

**Критичность:** 🟡 Средний
**Файл:** `app/Http/Controllers/MiniAppController.php`, строка 522
**Симптом:** Если скоринг упал (exception в `scoreAttempt`), попытка получает статус `error`. При попытке открыть результаты — редирект обратно на тест. На тесте (после фикса бага 1) — редирект на результаты. Бесконечный цикл редиректов.

**Текущий код:**
```php
if (!in_array($attempt->status, ['submitted', 'scored'], true)) {
    return redirect('/tg/test/' . $attempt->id);
}
```

**Исправление:**
```php
if (!in_array($attempt->status, ['submitted', 'scored', 'error'], true)) {
    return redirect('/tg/test/' . $attempt->id);
}
```

При статусе `error` показать результаты с пометкой, что скоринг неполный (если scorings есть — показать их; если нет — показать ответы без проверки).

---

## Баг 6 — `TaskAnswerResolver`: «угадывание» правильного ответа по первому варианту

**Критичность:** 🟠 Важный (тихие ошибки проверки)
**Файл:** `app/Services/TaskAnswerResolver.php`, строки 83-87, 100-114
**Симптом:** Задание без явного ответа в JSON проверяется как будто правильный ответ — первый вариант. Ученик может получить «неверно» на правильный ответ или «верно» на неправильный.

**Текущие fallback-и (в порядке срабатывания):**

| Строки | Fallback | Риск |
|--------|----------|------|
| 83-87 | choice-тип без ответа → `return '1'` (первый вариант) | Высокий — если опции перемешаны, «1» больше не первый |
| 100-106 | `task['options'][0]` → первый элемент массива | Средний — берёт текст опции вместо индекса |
| 108-114 | `zadanie['options'][0]` → то же для уровня задания | Средний |
| 116-118 | `task['expression']` → пытается вычислить выражение | Низкий — может дать неверный результат для нематематических выражений |

**Исправление:**

1. Все fallback-и, которые «угадывают» ответ, должны возвращать `null` вместо подставного значения.
2. `null`-ответ в `persistScoringRow()` уже обрабатывается как `is_correct = null` (не проверено), что корректно.
3. Оставить телеметрию (logFallback) — но перевести её с info на warning, чтобы отслеживать задания без ответов.
4. Единственный легитимный fallback — `task['correct']` как явный индекс (строки 79-81, 90-98). Его оставить.

**Конкретные изменения:**

```php
// Строки 83-87: УДАЛИТЬ fallback на '1'
if (!empty($task['options']) || !empty($zadanie['options'])) {
    $this->logFallback('choice_default_first', $zadanie, $task);
    return '1';  // ← УДАЛИТЬ, заменить на return null
}

// Строки 100-106: УДАЛИТЬ fallback на первую опцию
if (!empty($task['options']) && is_array($task['options'])) {
    $first = $task['options'][0] ?? null;
    // ← УДАЛИТЬ весь блок

// Строки 108-114: УДАЛИТЬ fallback на первую опцию задания
if (!empty($zadanie['options']) && is_array($zadanie['options'])) {
    // ← УДАЛИТЬ весь блок
```

**Предварительная проверка:** Перед удалением — запустить `php artisan tasks:validate` (добавлен в коммите `27bda16`), чтобы убедиться что у всех заданий в production JSON-файлах есть явные ответы. Задания без ответов нужно дополнить вручную.

---

## Баг 7 — Рассинхронизация `canonical_option_id` и `frozen_answers_json`

**Критичность:** 🟠 Важный (потенциальные ложные «неверно»)
**Файлы:**
- `app/Services/MiniAppTaskCanonicalizer.php`, строки 67-69
- `app/Services/OgeAttemptService.php`, строка 551-567 (`ensureFrozenAnswerSnapshot`)
- `resources/views/miniapp/test.blade.php`, строки 913-916

**Симптом:** Ученик выбирает правильный вариант, но результат показывает «неверно».

**Механизм:**

1. `ensureFrozenAnswerSnapshot()` фиксирует правильные ответы на этапе `startAttempt()`.
2. Для choice-задач frozen answer может быть `"1"` (индекс из JSON).
3. `MiniAppTaskCanonicalizer` преобразует опции, присваивая stable id (`"a"`, `"b"`, ...`).
4. В тесте `optionAnswerValue()` отправляет `opt.id` (буква `"a"`).
5. При проверке: `frozen = "1"`, `userAnswer = "a"` → `isCorrect("a", "1")` → **false**.

**Когда это воспроизводится:** Только если JSON задания хранит ответ как числовой индекс (`"answer": "1"`), а stable option ids ещё не были прописаны в JSON. Сегодняшние коммиты `7ca4bfc` и `7ecfcb4` добавили stable ids для тем 07 и 11, но остальные темы могут быть затронуты.

**Исправление (два варианта, выбрать один):**

**Вариант A (рекомендуемый):** `ensureFrozenAnswerSnapshot()` должен фиксировать ответы **после** canonical-нормализации. То есть frozen answer для choice должен быть `"a"` (stable id), а не `"1"` (индекс).

```php
private function ensureFrozenAnswerSnapshot(OgeAttempt $attempt): void
{
    // ... existing check ...

    $correctMap = $this->getCorrectAnswerMap($attempt);

    // Normalize choice answers to stable option ids
    $canonicalizer = app(MiniAppTaskCanonicalizer::class);
    // Apply same canonicalization that test() applies
    foreach ($correctMap as $tn => &$answer) {
        // resolve canonical_option_id if applicable
    }

    $attempt->forceFill(['frozen_answers_json' => $correctMap])->save();
}
```

**Вариант B:** В `isCorrect()` нормализовать оба значения через один и тот же маппинг. Но это сложнее и более хрупко.

**Предварительная проверка:** Запустить `php artisan tasks:add-option-ids --dry-run` для всех тем, чтобы убедиться что stable ids прописаны везде.

---

## Баг 8 — Statements: нестабильный набор утверждений при отсутствии `selected_statements`

**Критичность:** 🟢 Низкий (edge case)
**Файлы:**
- `app/Services/OgeVariantBuilderService.php` — формирует `selected_statements`
- `app/Services/MiniAppTaskCanonicalizer.php`, строка 34 — fallback на `task['statements']`

**Симптом:** Если задание типа `statements` попало в вариант без зафиксированных `selected_statements` (например, из legacy-кэша или ручного custom variant), canonicalizer берёт `task['statements']` — полный массив всех утверждений. При этом ответ вычисляется из всех утверждений, а не из выбранных трёх.

**Исправление:**

В `MiniAppTaskCanonicalizer::normalizeForUi()`, если `selected_statements` отсутствует, а `statements` содержит больше 3 элементов — логировать warning и не формировать ответ (вернуть `canonical_answer = null`).

```php
if (($task['type'] ?? '') === 'statements') {
    $statements = $task['selected_statements'] ?? null;
    if ($statements === null) {
        // Fallback: use raw statements only if exactly 3 (pre-selected)
        $raw = $task['statements'] ?? [];
        if (count($raw) <= 3) {
            $statements = $raw;
        } else {
            Log::warning('Statements task without selected_statements', [
                'task_number' => $task['task_number'] ?? null,
            ]);
            $statements = [];
        }
    }
}
```

---

## Сводная таблица

| # | Баг | Файл | Строка | Критичность | Сложность |
|---|-----|------|--------|-------------|-----------|
| 1 | `test()` не редиректит scored/error | MiniAppController | 454 | 🔴 | Тривиальная |
| 2 | `dashboard()` не видит scored | MiniAppController | 254 | 🔴 | Тривиальная |
| 3 | `tutor()` не видит scored | MiniAppController | 602 | 🟡 | Тривиальная |
| 4 | `computeWeakTopics()` не видит scored | MiniAppController | 1040 | 🟡 | Тривиальная |
| 5 | `results()` не принимает error | MiniAppController | 522 | 🟡 | Тривиальная |
| 6 | Угадывание ответа по первому варианту | TaskAnswerResolver | 83-114 | 🟠 | Средняя |
| 7 | canonical_option_id vs frozen_answers | OgeAttemptService + Canonicalizer | — | 🟠 | Средняя |
| 8 | Нестабильные statements без selected | MiniAppTaskCanonicalizer | 34 | 🟢 | Низкая |

---

## Порядок исправления

### Этап 1 — Статусы (баги 1-5)
**Время:** ~15 минут
**Риск:** Минимальный — добавление допустимых статусов не ломает существующую логику.

1. `test()` строка 454 → `in_array($attempt->status, ['submitted', 'scored', 'error'], true)`
2. `dashboard()` строка 254 → `whereIn('status', ['submitted', 'scored'])`
3. `tutor()` строка 602 → `whereIn('status', ['submitted', 'scored'])`
4. `computeWeakTopics()` строка 1040 → `whereIn('a.status', ['submitted', 'scored'])`
5. `results()` строка 522 → добавить `'error'` в массив

### Этап 2 — Fallback-ответы (баг 6)
**Время:** ~30 минут
**Зависимость:** Сначала запустить `php artisan tasks:validate` и убедиться что все задания имеют явные ответы.

1. Удалить три fallback-блока в `TaskAnswerResolver`
2. Заменить на `return null` с warning-логом
3. Прогнать тесты

### Этап 3 — Frozen answers sync (баг 7)
**Время:** ~1 час
**Зависимость:** Нужно решить, какой вариант (A или B) реализовывать. Рекомендуется A.

1. Добавить canonical-нормализацию в `ensureFrozenAnswerSnapshot()`
2. Запустить `php artisan tasks:add-option-ids` для всех тем
3. Написать тест: создать попытку, проверить что frozen answer совпадает с тем, что отправляет клиент

### Этап 4 — Statements edge case (баг 8)
**Время:** ~15 минут
**Риск:** Минимальный.

1. Добавить проверку count в canonicalizer
2. Добавить warning-лог

---

## Тестирование

### Ручная проверка (после этапа 1)
1. Решить тест → убедиться что `/tg/test/{id}` редиректит на результаты
2. Открыть дашборд → виджет «последний результат» показывает данные
3. Открыть тьютора → слабые темы отображаются
4. В БД: `UPDATE oge_attempts SET status='error' WHERE id=X` → `/tg/results/X` открывается

### Автоматические тесты
- Существующий тест `OgeAttemptServiceTest` покрывает scoring flow
- Добавить тест: `test_test_page_redirects_for_scored_attempt`
- Добавить тест: `test_dashboard_shows_scored_attempt_as_last`
- Добавить тест: `test_answer_resolver_returns_null_without_explicit_answer`
