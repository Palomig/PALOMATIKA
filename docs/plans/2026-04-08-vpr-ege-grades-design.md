# Design: ВПР (5-8 кл.) + ЕГЭ attempt flow (10-11 кл.) + Автоперевод классов

**Дата:** 2026-04-08  
**Статус:** Approved

---

## Контекст

Palomatika расширяется с 9-го класса (ОГЭ) на все классы 5-11:

| Класс | Экзамен | Дашборд |
|-------|---------|---------|
| 5, 6, 7 | ВПР | новый `vpr-home` |
| 8 | ВПР + ОГЭ | новый `vpr-home` + кнопка к ОГЭ |
| 9 | ОГЭ | существующий (без изменений) |
| 10, 11 | ЕГЭ | новый `ege-home` (как ОГЭ) |
| 12 (выпускники) | — | только история попыток |

---

## 1. Данные задний (JSON)

### Структура файлов

```
storage/app/tasks/
  vpr/
    grade_5/  topic_01.json … topic_18.json
    grade_6/  topic_01.json … topic_18.json
    grade_7/  topic_01.json … topic_18.json
    grade_8/  topic_01.json … topic_18.json
  ege/          (уже есть: topic_01.json … topic_20.json)
```

### Формат VPR JSON (идентичен OGE)

```json
{
  "topic_id": "01",
  "exam_type": "vpr",
  "grade": 5,
  "blocks": [{
    "number": 1,
    "title": "ВПР 2024",
    "zadaniya": [{
      "number": 1,
      "instruction": "...",
      "type": "expression",
      "tasks": [{ "id": 1, "expression": "...", "answer": "12" }]
    }]
  }]
}
```

18 топиков на каждый класс (topic_01–topic_18). Темы различаются по классу.

### Новые сервисы данных

- `VprTaskDataService` — принимает `int $grade`, работает с `storage/app/tasks/vpr/grade_{N}/`. API идентичен `TaskDataService`: `getBlocks()`, `getTopicStats()`, `topicDataExists()`, `getAllTopicsMeta()`.
- `EgeTaskDataService` — уже существует, не меняется.

---

## 2. База данных

### Единственная миграция

Добавить `exam_type` в `oge_variants`:

```php
$table->enum('exam_type', ['oge', 'vpr_5', 'vpr_6', 'vpr_7', 'vpr_8', 'ege'])
      ->default('oge')
      ->after('hash');
```

**Все attempt-таблицы переиспользуются без изменений** (`oge_attempts`, `oge_attempt_answers`, `oge_attempt_task_timings`, `oge_attempt_scorings`, `oge_attempt_events`). Они работают через `variant_id` и не зависят от типа экзамена.

### Индекс

```php
$table->index('exam_type');
```

---

## 3. Сервисы

### Новые

| Сервис | Описание |
|--------|----------|
| `VprTaskDataService` | Доступ к JSON-заданиям ВПР, принимает grade |
| `VprVariantBuilderService` | Детерминированная сборка ВПР-варианта из хэша (аналог `OgeVariantBuilderService`) |
| `VprVariantPoolService` | Пул готовых ВПР-вариантов per grade (аналог `OgeVariantPoolService`) |
| `EgeVariantBuilderService` | Детерминированная сборка ЕГЭ-варианта (аналог OGE) |
| `EgeVariantPoolService` | Пул готовых ЕГЭ-вариантов |

### Переиспользуются без изменений

- `OgeAttemptService` — полный attempt lifecycle (start, commit, submit, score) работает для всех exam_type через variant_id
- `OgeAttemptSuspicionService`, `TaskAnswerResolver`, `MiniVariantService` — без изменений
- `EgeTaskDataService` — уже существует

---

## 4. Автоперевод классов

### Artisan-команда `grades:promote`

```
php artisan grades:promote
```

Логика:
1. `grade_num IN (5,6,7,8,9,10)` → `grade_num + 1`
2. `grade_num = 11` → `grade_num = 12` (выпускник)

Grade 12: доступ только на чтение (история попыток). В middleware проверять `grade_num = 12` и запрещать старт новых вариантов.

### Расписание

В `app/Console/Kernel.php`:

```php
$schedule->command('grades:promote')->yearlyOn(6, 1, '03:00');
```

Запускается **1 июня в 03:00** каждого года.

---

## 5. Онбординг

### Изменения в `Pwa/StudentController@saveOnboarding`

```php
// Было:
'grade_num' => 'required|integer|in:9',
'grade_letter' => 'required|string|in:А,Б,В,Г,Д,К,М',

// Стало:
'grade_num' => 'required|integer|in:5,6,7,8,9,10,11',
'grade_letter' => 'required|string|in:А,Б,В,Г,Д,И,К,М',
```

То же самое в `MiniAppAuthController`.

### UI онбординга

Чипы классов: `[5, 6, 7, 8, 9, 10, 11]`  
Буквы: `А Б В Г Д И К М`

---

## 6. Маршрутизация (PWA)

### Новые роуты

```php
// ВПР
Route::prefix('vpr')->name('vpr.')->group(function () {
    Route::get('/',             [VprController::class, 'home'])   ->name('home');
    Route::post('/start',       [VprController::class, 'start'])  ->name('start');
    Route::get('/test/{attempt}',[VprController::class, 'test'])  ->name('test');
    Route::get('/results/{attempt}', [VprController::class, 'results'])->name('results');
});

// ЕГЭ attempt flow (дополнение к существующим ege.* роутам)
Route::prefix('api/ege-attempt')->group(function () {
    // аналогично api/oge/* attempt routes
});
```

### Логика редиректа в PWA home

В `StudentController@home` (или middleware):

```php
$grade = auth()->user()->grade_num;

if ($grade >= 5 && $grade <= 8)  → redirect vpr.home
if ($grade === 9)                → OGE home (существующий)
if ($grade >= 10 && $grade <= 11)→ redirect ege.home (новый)
if ($grade === 12)               → history-only view
```

---

## 7. Новые контроллеры

| Контроллер | Описание |
|------------|----------|
| `VprController` | Home, start variant, test page, results — аналог StudentController для ВПР |
| `EgeAttemptController` | Start/commit/submit ЕГЭ попыток — аналог `OgeAttemptController` |

---

## 8. Views

| View | Описание |
|------|----------|
| `pwa/student/vpr-home.blade.php` | Дашборд ВПР (дизайн уточняется) |
| `pwa/student/ege-home.blade.php` | Дашборд ЕГЭ (как OGE home) |
| `pwa/student/vpr-test.blade.php` | Решение ВПР-варианта (как test.blade.php) |
| `pwa/student/vpr-results.blade.php` | Результаты ВПР (как results.blade.php) |
| `pwa/student/ege-test.blade.php` | Решение ЕГЭ-варианта |
| `pwa/student/ege-results.blade.php` | Результаты ЕГЭ |

---

## 9. Учитель

Без изменений в логике — в списке учеников добавить отображение `grade_num` (цифра класса) рядом с именем. Фильтрация по классу — опционально в будущем.

---

## Что НЕ входит в scope

- Наполнение JSON-файлов заданиями ВПР (отдельная задача, контент добавляется после)
- Наполнение ЕГЭ-тем полными базами заданий
- Дизайн VPR-дашборда (уточняется в процессе)
- Рейтинги/дуэли для ВПР/ЕГЭ

---

## Порядок реализации (высокоуровневый)

1. Миграция `exam_type` в `oge_variants`
2. `VprTaskDataService` + пустые JSON-заглушки (topic_01–18) для grade 5
3. `VprVariantBuilderService` + `VprVariantPoolService`
4. `VprController` + роуты + views (home, test, results)
5. `EgeVariantBuilderService` + `EgeVariantPoolService` + `EgeAttemptController`
6. PWA grade routing (middleware или в home controller)
7. Онбординг: расширить классы и буквы
8. Artisan-команда `grades:promote` + scheduler
9. Учитель: показ номера класса
10. Наполнение VPR JSON-файлов заданиями (по мере поступления PDF)
