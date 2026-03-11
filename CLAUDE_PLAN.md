# ТЗ: Исправление архитектурных проблем пайплайна данных мини-аппа

**Дата:** 2026-03-07
**Статус:** Утверждено к реализации
**Область:** JSON-данные -> генерация варианта -> отображение -> скоринг

---

## Оглавление

1. [Контекст и диагностика](#1-контекст-и-диагностика)
2. [Аудит данных](#2-аудит-данных)
3. [Фаза P0 — Критические уязвимости и телеметрия](#3-фаза-p0)
4. [Фаза P1 — Единый нормализатор и каноническая схема ответов](#4-фаза-p1)
5. [Фаза P1-DATA — Нормализация базы заданий](#5-фаза-p1-data)
6. [Фаза P1.5 — Замороженный снапшот для скоринга](#6-фаза-p15)
7. [Фаза P2 — Стабильные ID опций, shuffle, очистка legacy](#7-фаза-p2)
8. [Критерии приемки по фазам](#8-критерии-приемки)
9. [Порядок деплоя и откат](#9-порядок-деплоя)

---

## 1. Контекст и диагностика

### 1.1 Суть проблемы

Мини-апп Telegram для подготовки к ОГЭ использует единую базу заданий (JSON-файлы) для генерации вариантов. Обнаружены системные проблемы:

1. **Утечка правильных ответов на клиент** — поля `correct_answer`, `task.answer`, `selected_statements[*].is_true` передаются в JavaScript
2. **Трёхслойное угадывание ответа** — JSON, нормализатор, `TaskAnswerResolver` независимо пытаются определить правильный ответ
3. **Семантическая перегрузка поля `answer`** — 5 разных смыслов в одном поле
4. **Двойная нормализация** — `OgeVariantPoolService::normalizeTaskForMiniApp()` и `MiniAppController::test()` дублируют логику
5. **Скоринг из живых данных** — при проверке ответа корректный ответ пересчитывается из текущего JSON, а не из замороженного снапшота
6. **Позиционно-зависимые ответы без перемешивания** — ответ "2" означает "вторая опция в массиве", при любом изменении порядка скоринг ломается

### 1.2 Корневая причина

Поле `answer` в JSON-файлах имеет **5 разных семантик** в зависимости от топика и типа задания:

| Семантика | Пример | Топики |
|-----------|--------|--------|
| Числовое значение (строка) | `"0.9"`, `"-3"` | 06, 08, 09, 10, 12, 14, 15, 16, 17 |
| 1-based индекс как int | `2` (означает options[1]) | 07 (58 заданий) |
| 1-based индекс как string | `"2"` (означает options[1]) | 07 (61 задание), 13 (67 заданий) |
| Код для matching | `"1342"` | 11 (37 заданий) |
| Отсутствует | `null` / не задано | 11 (26), 13 (54), 18 (75) — итого 155 заданий |

`TaskAnswerResolver` пытается угадать семантику в runtime через каскад из 8 fallback-ов, что приводит к молчаливым ошибкам.

### 1.3 Затронутые файлы

| Файл | Роль | Проблемы |
|------|------|----------|
| `app/Services/TaskAnswerResolver.php` | Разрешение + проверка ответов | 8-level fallback, `eval()`, молчаливый возврат `'1'` |
| `app/Services/OgeVariantPoolService.php` | Генерация мини-вариантов | Первый слой нормализации, `normalizeTaskForMiniApp()` |
| `app/Services/OgeVariantBuilderService.php` | Генерация полных вариантов | `array_rand()` не детерминистичен в PHP 8.2+ |
| `app/Services/OgeAttemptService.php` | Жизненный цикл попытки | Скоринг из живых данных, `firstOrCreate` race condition |
| `app/Http/Controllers/MiniAppController.php` | Контроллер мини-аппа | Второй слой нормализации, неполная очистка ответов |
| `resources/views/miniapp/test.blade.php` | Фронтенд мини-аппа | `normalizedOptions()` с 8 fallback-ами, утечка ответов |
| `app/Services/TaskDataService.php` | Доступ к JSON-данным | Центральный сервис, кэширование |
| `storage/app/tasks/topic_*.json` | База заданий | Расслоение данных, 5 семантик `answer` |

---

## 2. Аудит данных

### 2.1 Статистика по топикам

| Топик | Всего задач | Production | Есть answer | Int index | Str index | Str value | Нет answer | Основные типы |
|-------|-------------|------------|-------------|-----------|-----------|-----------|------------|---------------|
| 06 | 210 | 0 | 210 | 0 | 0 | 210 | 0 | expression |
| 07 | 127 | 77 | 127 | 58 | 61 | 8 | 0 | between_fractions, choice, simple_choice, fraction_choice, interval_choice, segment_choice |
| 08 | 366 | 0 | 366 | 0 | 0 | 366 | 0 | expression, sqrt_choice, power_choice |
| 09 | 206 | 0 | 206 | 0 | 0 | 206 | 0 | equation |
| 10 | 262 | 0 | 262 | 0 | 0 | 262 | 0 | word_problem |
| 11 | 63 | 36 | 37 | 0 | 0 | 37 | 26 | matching, matching_signs |
| 12 | 88 | 0 | 88 | 0 | 0 | 88 | 0 | formula |
| 13 | 166 | 0 | 112 | 0 | 67 | 45 | 54 | comparison, sqrt_choice, interval_choice |
| 14 | 50 | 0 | 50 | 0 | 0 | 50 | 0 | word_problem, sequence |
| 15 | 105 | 0 | 105 | 0 | 0 | 105 | 0 | geometry |
| 16 | 135 | 0 | 135 | 0 | 0 | 135 | 0 | geometry |
| 17 | 173 | 0 | 173 | 0 | 0 | 173 | 0 | geometry |
| 18 | 156 | 0 | 81 | 0 | 0 | 81 | 75 | grid, area |
| 19 | 0* | 0 | 0 | 0 | 0 | 0 | 0 | statements |

*Тема 19 использует `statements` (массив утверждений), поле answer не применяется.

### 2.2 Ключевые проблемы данных

1. **Топик 07** — смешаны `int` и `string` для одного смысла (1-based index). Примеры: `"answer": 2` vs `"answer": "2"`.
2. **Топик 13** — 54 задания без answer. Для choice-типов `TaskAnswerResolver` молча возвращает `'1'` (первая опция).
3. **Топик 11** — 26 matching-заданий без answer. Ответ вычисляется в `OgeVariantBuilderService::buildMatchingCorrectAnswer()` из `options[0]` конвенции.
4. **Топик 18** — 75 заданий без answer (тип grid/area). Для них answer можно восстановить из структуры данных или PDF.
5. **Топики 07, 13** — index-based ответы означают порядковый номер опции. При любом изменении порядка опций ответ становится неверным.

---

## 3. Фаза P0 — Критические уязвимости и телеметрия

**Цель:** Закрыть утечку ответов на клиент. Добавить наблюдаемость для fallback-ов резолвера.
**Срок:** 1-2 дня
**Риск деплоя:** Низкий (аддитивные изменения + удаление полей из payload)

### P0.1 — Серверный санитайзер ответов (DTO)

**Файл:** `app/Services/MiniAppTaskSanitizer.php` (новый)

**Что делает:** Единая точка очистки задания перед отправкой клиенту. Вызывается **один раз** на сервере, после всех нормализаций.

**Удаляемые поля:**

```php
class MiniAppTaskSanitizer
{
    private const FIELDS_TO_STRIP = [
        'correct_answer',
        'answer',
        'is_true',
    ];

    public function sanitize(array $task): array
    {
        // Верхний уровень
        foreach (self::FIELDS_TO_STRIP as $field) {
            unset($task[$field]);
        }

        // Вложенный task.answer (legacy формат)
        if (isset($task['task']) && is_array($task['task'])) {
            unset($task['task']['answer']);
        }

        // selected_statements[*].is_true
        if (isset($task['selected_statements']) && is_array($task['selected_statements'])) {
            foreach ($task['selected_statements'] as &$stmt) {
                unset($stmt['is_true']);
            }
            unset($stmt);
        }

        // statements[*].is_true (полный массив)
        if (isset($task['statements']) && is_array($task['statements'])) {
            foreach ($task['statements'] as &$stmt) {
                unset($stmt['is_true']);
            }
            unset($stmt);
        }

        return $task;
    }
}
```

**Точка вызова:** В `MiniAppController::test()`, **после** нормализации, **перед** передачей в Blade:

```php
// После нормализации задач
$sanitizer = app(MiniAppTaskSanitizer::class);
$tasks = array_map(fn($t) => $sanitizer->sanitize($t), $tasks);
```

**Удалить из Blade:** Текущую неполную очистку в `test.blade.php` (строки 846-851).

**Критерий приемки:**
- В DevTools Network tab поля `correct_answer`, `answer`, `is_true` отсутствуют в JSON payload
- Все существующие тесты проходят
- Скоринг продолжает работать (он берёт ответы из серверного `config_json`, а не из клиентского payload)

### P0.2 — Телеметрия fallback-ов TaskAnswerResolver

**Файл:** `app/Services/TaskAnswerResolver.php` (модификация)

**Что делает:** Логирует каждый случай использования fallback-ов для определения правильного ответа.

**Реализация:**

```php
// В resolveFromTaskAndZadanie() — добавить метку метода разрешения
private function resolveFromTaskAndZadanie(array $task, array $zadanie): array
{
    // ... существующая логика ...

    // Перед каждым return добавить:
    return [
        'answer' => $answer,
        'method' => 'explicit_correct',  // или 'task_answer', 'options_first', 'fallback_1', etc.
    ];
}

// Новый публичный метод с логированием:
public function resolveWithTelemetry(array $task, array $zadanie, array $context = []): string
{
    $result = $this->resolveFromTaskAndZadanie($task, $zadanie);

    if (!in_array($result['method'], ['explicit_correct', 'task_answer', 'zadanie_answer'])) {
        Log::channel('answer_resolver')->info('Fallback used', [
            'method' => $result['method'],
            'topic_id' => $context['topic_id'] ?? null,
            'task_id' => $context['task_id'] ?? null,
            'type' => $task['type'] ?? $zadanie['type'] ?? null,
        ]);
    }

    return $result['answer'];
}
```

**Лог-канал:** Добавить в `config/logging.php`:

```php
'answer_resolver' => [
    'driver' => 'daily',
    'path' => storage_path('logs/answer-resolver.log'),
    'days' => 14,
],
```

**Критерий приемки:**
- Лог `answer-resolver.log` содержит записи при использовании fallback
- Каждая запись включает: метод разрешения, topic_id, task_id, тип задания
- Нет записей для заданий с явным `correct` или `answer` полем

### P0.3 — Guard-валидатор для production-заданий

**Файл:** `app/Console/Commands/ValidateProductionTasks.php` (новый)

**Что делает:** Artisan-команда для проверки заданий на наличие обязательных полей. Запускается в CI и перед деплоем.

**Проверки:**

| Правило | Описание |
|---------|----------|
| `answer_present` | Для choice-типов: поле `answer` или `correct` обязательно |
| `answer_type_consistent` | `answer` должен быть string (не int) |
| `options_present` | Для choice-типов: массив `options` непустой |
| `options_count` | Количество опций >= 2 |
| `answer_in_range` | Если answer = index, то index <= count(options) |
| `matching_answer` | Для matching: answer состоит из цифр, длина = кол-во графиков |
| `statements_have_is_true` | Для statements: каждый statement имеет `is_true` |

**Команда:**

```bash
php artisan tasks:validate --production-only
php artisan tasks:validate --topic=07
php artisan tasks:validate --fix-types  # Автоматически конвертирует int -> string
```

**Вывод:**

```
Topic 07: 77 production tasks
  WARN: 58 tasks have int answer (should be string)
  OK: all tasks have answer field
  OK: all options arrays non-empty

Topic 13: 0 production tasks (skipped)

Summary: 2 warnings, 0 errors
```

**Критерий приемки:**
- Команда завершается с exit code 0 если нет ошибок, 1 если есть
- Флаг `--fix-types` конвертирует int answer в string и сохраняет JSON
- В GitHub Actions добавлен шаг `php artisan tasks:validate --production-only`

---

## 4. Фаза P1 — Единый нормализатор и каноническая схема ответов

**Цель:** Единая точка нормализации задания. Типозависимая каноническая схема ответов.
**Срок:** 3-5 дней
**Риск деплоя:** Средний (замена двух нормализаторов одним)
**Зависимости:** P0 завершена

### P1.1 — MiniAppTaskNormalizer (единая точка входа)

**Файл:** `app/Services/MiniAppTaskNormalizer.php` (новый)

**Что делает:** Заменяет оба текущих нормализатора:
- `OgeVariantPoolService::normalizeTaskForMiniApp()` (строки 334-394)
- `MiniAppController::test()` (строки 466-516)

Единая точка преобразования сырых данных из `TaskDataService` / `OgeVariantBuilderService` в формат для фронтенда.

**Контракт (входящий формат -> исходящий формат):**

```php
class MiniAppTaskNormalizer
{
    /**
     * Нормализует задание из любого источника в единый формат.
     *
     * Входящий формат (любой из):
     * - Сырой из TaskDataService: {task: {expression, answer, ...}, options: [...]}
     * - Из OgeVariantBuilderService: {task: {...}, topic_id, task_number, correct_answer}
     * - Из OgeVariantPoolService: {text, expression, options, type, ...}
     *
     * Исходящий формат (всегда):
     * - text: string          -- текст задания
     * - expression: ?string   -- LaTeX выражение (если есть)
     * - options: ?array       -- варианты ответа (если есть)
     * - type: string          -- тип задания
     * - topic_id: string      -- ID топика
     * - task_number: int      -- номер задания в ОГЭ
     * - svg: ?string          -- SVG изображение (если есть)
     * - image: ?string        -- путь к изображению (если есть)
     * - options_render_mode: ?string -- режим рендера опций
     */
    public function normalize(array $raw, string $topicId): array
    {
        // 1. Поднять вложенные поля из raw['task'] на верхний уровень
        // 2. Определить text из text / expression / task.text / task.expression
        // 3. Определить options из options / task.options / zadanie.options
        // 4. Определить type из type / task.type / zadanie.type
        // 5. Установить topic_id, task_number
        // 6. Сохранить svg, image, options_render_mode
        // 7. НЕ включать answer/correct_answer (это забота скоринга)
    }
}
```

**Точки вызова (замена):**

1. `OgeVariantPoolService::generateVariantTasks()` — после получения задач вызывать `$normalizer->normalize()` вместо `normalizeTaskForMiniApp()`
2. `MiniAppController::test()` — убрать дублирующую нормализацию (строки 466-516), вызывать `$normalizer->normalize()` один раз

**Важно:** `normalizeTaskForMiniApp()` в `OgeVariantPoolService` не удаляется сразу, а помечается `@deprecated` и делегирует в `MiniAppTaskNormalizer`. Удаление — в P2.

**Критерий приемки:**
- Один и тот же JSON из TaskDataService даёт идентичный результат независимо от точки входа
- Фронтенд `normalizedOptions()` получает опции из единого поля `options` без fallback-цепочки
- Существующие E2E-тесты мини-аппа проходят

### P1.2 — Каноническая схема ответов (типозависимая)

**Файл:** `app/Services/CanonicalAnswerSchema.php` (новый)

**Что делает:** Определяет каноническую семантику поля `answer` для каждого типа задания. Используется при:
- Записи `correct_answer` в `config_json` при старте попытки
- Скоринге при проверке ответа
- Валидации данных (P0.3)

**Схема:**

```php
class CanonicalAnswerSchema
{
    /**
     * Типы, где answer = строковое значение ответа (не индекс).
     * Пример: "0.9", "-3", "12.5"
     */
    private const VALUE_TYPES = [
        'expression', 'equation', 'formula', 'word_problem',
        'sequence', 'geometry', 'grid', 'area',
    ];

    /**
     * Типы, где answer = 1-based string индекс выбранной опции.
     * Пример: "2" означает options[1]
     */
    private const INDEX_TYPES = [
        'choice', 'simple_choice', 'fraction_choice',
        'interval_choice', 'between_fractions', 'segment_choice',
        'fraction_options', 'decimal_choice', 'sqrt_choice',
        'sqrt_interval', 'sqrt_segment', 'sqrt_options',
        'comparison', 'power_choice', 'compare_fractions',
        'false_statements', 'ordering', 'point_value',
        'fraction_point', 'count_integers',
        'negative_segment', 'negative_interval',
    ];

    /**
     * Типы, где answer = код соответствия (конкатенация цифр).
     * Пример: "1342" означает задача1->формула1, задача2->формула3, ...
     */
    private const MATCHING_TYPES = [
        'matching', 'matching_signs',
    ];

    /**
     * Типы, где answer вычисляется из statements[*].is_true.
     * Пример: "13" означает утверждения 1 и 3 верны
     */
    private const STATEMENT_TYPES = [
        'statements',
    ];

    public function getAnswerSemantic(string $type): string
    {
        if (in_array($type, self::VALUE_TYPES, true)) return 'value';
        if (in_array($type, self::INDEX_TYPES, true)) return 'index';
        if (in_array($type, self::MATCHING_TYPES, true)) return 'matching';
        if (in_array($type, self::STATEMENT_TYPES, true)) return 'statement';
        return 'value'; // default safe fallback
    }

    /**
     * Нормализует answer в каноническую форму на основе типа.
     */
    public function canonicalize(mixed $answer, string $type): ?string
    {
        if ($answer === null || $answer === '') return null;

        $semantic = $this->getAnswerSemantic($type);

        return match ($semantic) {
            'value' => TaskAnswerResolver::normalize((string) $answer),
            'index' => (string) (int) $answer,  // "2" -> "2", 2 -> "2"
            'matching' => preg_replace('/[^0-9]/', '', (string) $answer),
            'statement' => preg_replace('/[^0-9]/', '', (string) $answer),
        };
    }
}
```

**Интеграция с TaskAnswerResolver:**

Заменить 8-level fallback на прямое обращение к канонической схеме:

```php
// Было (8 fallback-ов):
$answer = $task['correct'] ?? $task['answer'] ?? $zadanie['answer'] ?? ...

// Стало:
$schema = app(CanonicalAnswerSchema::class);
$type = $task['type'] ?? $zadanie['type'] ?? '';
$semantic = $schema->getAnswerSemantic($type);

$rawAnswer = match ($semantic) {
    'index' => $task['answer'] ?? $zadanie['answer'] ?? null,
    'value' => $task['answer'] ?? $zadanie['answer'] ?? null,
    'matching' => $this->resolveMatchingAnswer($task),
    'statement' => $this->resolveStatementAnswer($task),
};

$answer = $schema->canonicalize($rawAnswer, $type);

if ($answer === null) {
    // Телеметрия из P0.2 вместо молчаливого fallback
    Log::channel('answer_resolver')->warning('No answer found', [...]);
    return null; // Не возвращаем '1' молча!
}
```

**Критерий приемки:**
- `TaskAnswerResolver` не использует fallback-каскад; каждый тип имеет явный путь разрешения
- Молчаливый возврат `'1'` для choice-типов без answer заменён на `null` + warning в лог
- Для всех 155 заданий без answer в логе появляется warning (а не молчаливый `'1'`)

### P1.3 — Упрощение фронтенда normalizedOptions()

**Файл:** `resources/views/miniapp/test.blade.php` (модификация)

**Что делает:** После внедрения `MiniAppTaskNormalizer` фронтенд получает опции в едином поле. Заменяем цепочку из 8 fallback-ов одним обращением.

**Было (строки 916-951):**
```javascript
normalizedOptions() {
    const task = this.currentTask;
    const candidates = [
        task?.options,
        task?.task?.options,
        task?.zadanie?.options,
        // ... ещё 5 вариантов
    ];
    // ...
}
```

**Стало:**
```javascript
normalizedOptions() {
    return this.currentTask?.options ?? [];
}
```

**Критерий приемки:**
- `normalizedOptions()` содержит одну строку
- Все типы заданий корректно отображают опции в мини-аппе

---

## 5. Фаза P1-DATA — Нормализация базы заданий

**Цель:** Привести JSON-данные к единому формату. Заполнить пропущенные ответы.
**Срок:** 2-3 дня
**Риск деплоя:** Средний (изменение данных требует регрессионного тестирования)
**Зависимости:** P0.3 (валидатор) для верификации результатов

### P1-DATA.1 — Топик 07: int -> string конверсия

**Файлы:** `storage/app/tasks/topic_07.json`

**Проблема:** 58 заданий имеют `"answer": 2` (int) вместо `"answer": "2"` (string). PHP `===` сравнение ломается при mixed types.

**Действие:** Запустить `php artisan tasks:validate --topic=07 --fix-types`

Или скрипт:

```php
$data = json_decode(file_get_contents(storage_path('app/tasks/topic_07.json')), true);

foreach ($data['blocks'] as &$block) {
    foreach ($block['zadaniya'] as &$zadanie) {
        foreach ($zadanie['tasks'] ?? [] as &$task) {
            if (isset($task['answer']) && is_int($task['answer'])) {
                $task['answer'] = (string) $task['answer'];
            }
        }
    }
}

file_put_contents(
    storage_path('app/tasks/topic_07.json'),
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);
```

**Критерий приемки:**
- Все `answer` в topic_07.json -- строки
- `php artisan tasks:validate --topic=07` -- 0 warnings про int type

### P1-DATA.2 — Топик 13: заполнение пропущенных ответов

**Файлы:** `storage/app/tasks/topic_13.json`

**Проблема:** 54 задания без поля `answer`. Для choice-типов (comparison, sqrt_choice, interval_choice и т.д.) это означает, что `TaskAnswerResolver` молча возвращает `'1'`.

**Действие:**

1. Для каждого задания без answer определить правильный ответ из:
   - PDF-источника (`pdf-sources/oge-trainer/ОГЭ 2026 Задание No13 (трен).pdf`)
   - Контекста задания (для comparison: сравнить значения; для interval_choice: решить неравенство)
2. Записать answer как string index (`"1"`, `"2"`, `"3"`, `"4"`)
3. Прогнать `php artisan tasks:validate --topic=13`

**Формат работы:**

```
Блок X, Задание Y, Задача Z:
- Тип: comparison
- Опции: ["\\sqrt{50}", "7.1", "7\\frac{1}{14}"]
- Правильный ответ: "2" (7.1 -- наименьшее)
- Источник: PDF стр. N, задание M
```

**Критерий приемки:**
- Все 166 заданий topic_13 имеют поле `answer` (string)
- `php artisan tasks:validate --topic=13` -- 0 ошибок
- Выборочная проверка 10 заданий по PDF-источнику -- все ответы верны

### P1-DATA.3 — Топик 18: заполнение пропущенных ответов

**Файлы:** `storage/app/tasks/topic_18.json`

**Проблема:** 75 заданий без answer. Тип grid/area -- ответ всегда числовое значение.

**Действие:** Аналогично P1-DATA.2:
1. Определить правильный ответ из PDF-источника
2. Записать answer как string value (`"12"`, `"4.5"`)
3. Прогнать валидатор

**Критерий приемки:**
- Все 156 заданий topic_18 имеют `answer` (string)
- Валидатор проходит без ошибок

### P1-DATA.4 — Топик 11: верификация matching ответов

**Файлы:** `storage/app/tasks/topic_11.json`

**Проблема:** 26 matching-заданий без answer. Ответ вычисляется в runtime из конвенции `options[0] = правильная формула`.

**Действие:**

1. Для каждого matching-задания вычислить ответ из `options[0]` конвенции
2. Записать computed answer в поле `answer` задания
3. Это сделает данные самодостаточными -- скоринг не будет зависеть от runtime-вычисления

**Формат:**

```json
{
    "type": "matching",
    "tasks": [
        {"text": "y = x^2 + 1", "options": ["Б", "А", "В"]},
        {"text": "y = -x^2 + 1", "options": ["В", "Б", "А"]}
    ],
    "formulas": ["А -- ...", "Б -- ...", "В -- ..."],
    "answer": "213"
}
```

**Критерий приемки:**
- Все 63 задания topic_11 имеют `answer`
- Значение answer совпадает с вычисленным из `options[0]` конвенции

### P1-DATA.5 — Проверка всех production-заданий

**Действие:**

1. Запустить `php artisan tasks:validate --production-only`
2. Исправить все найденные проблемы
3. Сохранить отчёт валидации

**Критерий приемки:**
- `php artisan tasks:validate --production-only` -- exit code 0
- Все production-задания проходят все проверки из P0.3

---

## 6. Фаза P1.5 — Замороженный снапшот для скоринга

**Цель:** Скоринг использует замороженные данные из момента старта попытки, а не текущий JSON.
**Срок:** 2-3 дня
**Риск деплоя:** Средний (изменение схемы БД)
**Зависимости:** P1 завершена (каноническая схема ответов)

### P1.5.1 — Заморозка correct_answer_map при старте попытки

**Файлы:**
- `app/Services/OgeAttemptService.php` (модификация)
- `database/migrations/XXXX_add_frozen_answers_to_oge_attempts.php` (новый)

**Что делает:** При старте попытки сохраняет `correct_answer_map` в отдельное поле `frozen_answers_json`, которое НЕ зависит от текущего состояния JSON-файлов.

**Миграция:**

```php
Schema::table('oge_attempts', function (Blueprint $table) {
    $table->json('frozen_answers_json')->nullable()->after('config_json');
});
```

**Логика в startAttempt():**

```php
public function startAttempt(User $student, string $hash, array $deviceMeta): array
{
    // ... существующая логика ...

    // После создания попытки -- заморозить ответы
    $correctAnswers = $this->buildCorrectAnswerMap($variant, $attempt);
    $attempt->update(['frozen_answers_json' => $correctAnswers]);

    return [$variant, $attempt];
}
```

**Логика в scoreAttempt():**

```php
// Было:
$correctAnswers = $this->getCorrectAnswerMap($attempt);
// Это вызывает buildVariantMaps() -> build() -> пересчёт из текущего JSON

// Стало:
$correctAnswers = $attempt->frozen_answers_json;
if (empty($correctAnswers)) {
    // Fallback для старых попыток без frozen_answers
    Log::warning('Scoring from live data (no frozen answers)', ['attempt_id' => $attempt->id]);
    $correctAnswers = $this->getCorrectAnswerMap($attempt);
}
```

**Критерий приемки:**
- Новые попытки имеют `frozen_answers_json` при старте
- Скоринг использует `frozen_answers_json`, а не пересчёт из текущего JSON
- Старые попытки (без frozen_answers) продолжают работать через fallback
- Изменение JSON-файла ПОСЛЕ старта попытки НЕ влияет на скоринг

### P1.5.2 — Гарантированное сохранение tasks в config_json

**Файлы:** `app/Services/OgeAttemptService.php` (модификация)

**Проблема:** `config_json['tasks']` иногда не сохраняется (зависит от пути генерации). При скоринге вызывается `build()` заново -- не гарантирован тот же результат.

**Действие:** Гарантировать что `config_json['tasks']` **всегда** заполняется при старте попытки:

```php
// В startAttempt(), после build():
$variantPayload = $this->variantBuilder->build($hash, $selectedZadaniya);

$attempt->update([
    'config_json' => [
        'tasks' => $variantPayload['tasks'],
        'zadaniya' => $selectedZadaniya,
    ],
    'frozen_answers_json' => $this->extractCorrectAnswers($variantPayload['tasks']),
]);
```

**Критерий приемки:**
- Все новые попытки имеют `config_json['tasks']` заполненным
- `buildVariantMaps()` использует `config_json['tasks']` без вызова `build()`

---

## 7. Фаза P2 — Стабильные ID опций, shuffle, очистка legacy

**Цель:** Ответы не зависят от позиции в массиве. Очистка технического долга.
**Срок:** 5-7 дней
**Риск деплоя:** Высокий (изменение формата данных и логики выбора ответа)
**Зависимости:** P1 + P1.5 завершены

### P2.1 — Стабильные ID для опций

**Файлы:**
- `storage/app/tasks/topic_*.json` (все с опциями)
- `app/Services/MiniAppTaskNormalizer.php` (модификация)

**Что делает:** Каждая опция получает стабильный ID, не зависящий от позиции.

**Формат данных (до):**

```json
{
    "options": ["(-2; 3)", "[-2; 3]", "(-2; 3]", "[-2; 3)"],
    "answer": "2"
}
```

**Формат данных (после):**

```json
{
    "options": [
        {"id": "a", "value": "(-2; 3)"},
        {"id": "b", "value": "[-2; 3]"},
        {"id": "c", "value": "(-2; 3]"},
        {"id": "d", "value": "[-2; 3)"}
    ],
    "answer": "b"
}
```

**Миграция данных:**

Artisan-команда `php artisan tasks:add-option-ids` которая:
1. Для каждого задания с options-массивом из простых строк
2. Конвертирует в массив объектов с `id` (a, b, c, d, ...)
3. Конвертирует `answer` из числового индекса в буквенный ID
4. Сохраняет JSON

**Обратная совместимость:** `MiniAppTaskNormalizer` принимает оба формата:
```php
// Если options[0] -- строка (старый формат), обернуть в объекты
// Если options[0] -- объект с id (новый формат), использовать как есть
```

### P2.2 — Перемешивание опций (shuffle)

**Файлы:**
- `app/Services/MiniAppTaskNormalizer.php` (модификация)
- `resources/views/miniapp/test.blade.php` (модификация)

**Что делает:** Перемешивает опции при отправке клиенту. Ответ привязан к `id`, а не к позиции.

```php
// В MiniAppTaskNormalizer::normalize():
if (!empty($task['options']) && $this->isShuffleableType($task['type'])) {
    shuffle($task['options']);
}
```

**Фронтенд:**
```javascript
selectOption(optionId) {
    // Было: this.answers[taskNum] = String(idx + 1)
    // Стало:
    this.answers[taskNum] = optionId;  // "b", не "2"
}
```

**Скоринг:** `TaskAnswerResolver::check()` сравнивает `submittedAnswer` (буквенный ID) с `correctAnswer` (буквенный ID) -- прямое строковое сравнение.

### P2.3 — Замена eval() на безопасный вычислитель

**Файл:** `app/Services/TaskAnswerResolver.php` (модификация)

**Проблема:** `eval('return ' . $converted . ';')` для вычисления математических выражений. Даже с санитизацией -- это injection vector.

**Решение:** Заменить на `symfony/expression-language` или простой калькулятор:

```php
private function evaluateMathExpression(string $expr): ?float
{
    // Разрешённые символы: цифры, +-*/, скобки, точка, пробел
    if (!preg_match('/^[\d+\-*\/().\s]+$/', $expr)) {
        return null;
    }

    try {
        $language = new ExpressionLanguage();
        return (float) $language->evaluate($expr);
    } catch (\Throwable) {
        return null;
    }
}
```

### P2.4 — Исправление array_rand() для детерминированности

**Файл:** `app/Services/OgeVariantBuilderService.php` (модификация)

**Проблема:** `array_rand()` в PHP 8.2+ не использует `mt_rand()`, поэтому `mt_srand($seed)` не влияет на результат. Варианты не детерминированы.

**Решение:**

```php
// Было:
$randomZadanie = $zadaniyaList[array_rand($zadaniyaList)];

// Стало:
$randomIndex = mt_rand(0, count($zadaniyaList) - 1);
$randomZadanie = $zadaniyaList[$randomIndex];
```

Аналогично для `array_rand($allStatements, 3)`:

```php
// Было:
$keys = array_rand($allStatements, 3);

// Стало:
$keys = [];
$available = array_keys($allStatements);
for ($i = 0; $i < 3 && !empty($available); $i++) {
    $idx = mt_rand(0, count($available) - 1);
    $keys[] = $available[$idx];
    array_splice($available, $idx, 1);
}
sort($keys);
```

### P2.5 — Race condition в startAttempt()

**Файл:** `app/Services/OgeAttemptService.php` (модификация)

**Проблема:** `firstOrCreate` без unique DB constraint на `(variant_id, student_id, status='active')`.

**Решение:**

1. Миграция: добавить partial unique index (MySQL 8.0 -- через generated column)

2. Обернуть `firstOrCreate` в DB transaction:

```php
return DB::transaction(function () use ($student, $hash, $deviceMeta) {
    $existing = OgeAttempt::where('variant_id', $variant->id)
        ->where('student_id', $student->id)
        ->where('status', 'active')
        ->lockForUpdate()
        ->first();

    if ($existing) {
        return [$variant, $existing];
    }

    $attempt = OgeAttempt::create([...]);
    return [$variant, $attempt];
});
```

### P2.6 — Удаление legacy кода

**Файлы:**
- `app/Services/OgeVariantPoolService.php` -- удалить `normalizeTaskForMiniApp()` (заменён на `MiniAppTaskNormalizer`)
- `app/Http/Controllers/MiniAppController.php` -- удалить дублирующую нормализацию (строки 466-516)
- `resources/views/miniapp/test.blade.php` -- удалить неполную очистку ответов (строки 846-851), убрать fallback-цепочку в `normalizedOptions()`

---

## 8. Критерии приемки по фазам

### P0 (готовность к production)

| # | Критерий | Проверка |
|---|----------|----------|
| 1 | В DevTools нет `correct_answer`, `answer`, `is_true` | Вручную через Network tab |
| 2 | Лог `answer-resolver.log` наполняется | `tail -f storage/logs/answer-resolver.log` |
| 3 | Валидатор проходит для production-заданий | `php artisan tasks:validate --production-only` |
| 4 | Существующие тесты проходят | `php artisan test` |
| 5 | Скоринг работает корректно | Пройти тестовый вариант, проверить баллы |

### P1 (единый нормализатор)

| # | Критерий | Проверка |
|---|----------|----------|
| 1 | Один нормализатор для всех путей | Grep по `normalizeTaskForMiniApp` -- только в deprecated wrapper |
| 2 | `normalizedOptions()` -- одна строка | Визуальная проверка кода |
| 3 | Каноническая схема покрывает все типы | `php artisan tasks:validate --all-topics` |
| 4 | Нет молчаливых fallback-ов `'1'` | Grep по `return '1'` в TaskAnswerResolver |

### P1-DATA (данные)

| # | Критерий | Проверка |
|---|----------|----------|
| 1 | Topic 07: все answer -- string | `php artisan tasks:validate --topic=07` |
| 2 | Topic 13: 0 заданий без answer | `php artisan tasks:validate --topic=13` |
| 3 | Topic 18: 0 заданий без answer | `php artisan tasks:validate --topic=18` |
| 4 | Topic 11: 0 заданий без answer | `php artisan tasks:validate --topic=11` |
| 5 | Выборочная проверка по PDF | 10 заданий из каждого исправленного топика |

### P1.5 (замороженный снапшот)

| # | Критерий | Проверка |
|---|----------|----------|
| 1 | Новые попытки имеют `frozen_answers_json` | SQL query |
| 2 | Изменение JSON не влияет на скоринг | Начать попытку, изменить JSON, submit, проверить баллы |
| 3 | Старые попытки -- fallback работает | Проверить скоринг для попытки без frozen_answers |

### P2 (стабильные ID)

| # | Критерий | Проверка |
|---|----------|----------|
| 1 | Опции перемешиваются | Открыть задание дважды, порядок разный |
| 2 | Ответ привязан к ID, не к позиции | Submit ответ, проверить корректность скоринга |
| 3 | Нет `eval()` в коде | `grep -r 'eval(' app/` -- 0 результатов |
| 4 | Варианты детерминированы | Одинаковый hash -> одинаковые задания |
| 5 | Нет дублей активных попыток | DB constraint не позволяет |

---

## 9. Порядок деплоя и откат

### Порядок

```
P0.1 (санитайзер)     -+
P0.2 (телеметрия)      +-- Деплой #1 (безопасный)
P0.3 (валидатор)       -+

P1-DATA.1 (topic 07)  -+
P1-DATA.2 (topic 13)   +-- Деплой #2 (данные)
P1-DATA.3 (topic 18)   |   Запускается ПОСЛЕ P0.3 для верификации
P1-DATA.4 (topic 11)  -+

P1.1 (нормализатор)   -+
P1.2 (каноническая)    +-- Деплой #3 (рефакторинг)
P1.3 (фронтенд)       -+

P1.5.1 (frozen)        -+-- Деплой #4 (миграция БД)
P1.5.2 (config_json)   -+

P2.1 (option IDs)      -+
P2.2 (shuffle)          |
P2.3 (eval)             +-- Деплой #5 (breaking changes)
P2.4 (array_rand)       |
P2.5 (race condition)   |
P2.6 (legacy cleanup)  -+
```

### Откат

| Деплой | Стратегия отката |
|--------|------------------|
| #1 | Revert commit. Без побочных эффектов. |
| #2 | `git checkout HEAD~1 -- storage/app/tasks/topic_*.json`. Данные откатываются. |
| #3 | Revert commit. Deprecated wrapper обеспечивает обратную совместимость. |
| #4 | Миграция `down()` убирает колонку. Fallback для старых попыток работает. |
| #5 | **СЛОЖНЫЙ ОТКАТ.** Revert кода + откат JSON к старому формату опций. Maintenance window. |

### Feature flags (рекомендуется для P2)

```php
// config/features.php
return [
    'stable_option_ids' => env('FEATURE_STABLE_OPTION_IDS', false),
    'shuffle_options' => env('FEATURE_SHUFFLE_OPTIONS', false),
];

// В нормализаторе:
if (config('features.shuffle_options')) {
    shuffle($task['options']);
}
```

---

## Приложение A: Карта файлов и зависимостей

```
                    +---------------------+
                    |  topic_XX.json      |
                    |  (единый источник)  |
                    +---------+-----------+
                              |
                    +---------v-----------+
                    |  TaskDataService    |
                    |  (кэш + доступ)    |
                    +---------+-----------+
                              |
              +---------------+---------------+
              |               |               |
    +---------v------+ +-----v------+ +------v---------+
    | VariantBuilder | | PoolService| | MiniAppCtrl    |
    | (полные ОГЭ)   | | (мини)     | | (контроллер)   |
    +---------+------+ +-----+------+ +------+---------+
              |               |               |
              +---------------+---------------+
                              |
                    +---------v-----------+
                    | MiniAppTask         |  <-- P1.1 NEW
                    | Normalizer          |
                    +---------+-----------+
                              |
                    +---------v-----------+
                    | MiniAppTask         |  <-- P0.1 NEW
                    | Sanitizer           |
                    +---------+-----------+
                              |
                    +---------v-----------+
                    |  Frontend (Blade)   |
                    |  (без ответов!)     |
                    +---------------------+

    Скоринг:
    +------------------+     +------------------+
    | frozen_answers   | --> | TaskAnswer       |
    | (из attempt DB)  |     | Resolver         |
    +------------------+     | + Canonical      |
                             |   Schema         |
                             +------------------+
```

## Приложение B: Глоссарий

| Термин | Определение |
|--------|-------------|
| `answer` | Правильный ответ на задание. Семантика зависит от типа (значение, индекс, код). |
| `correct_answer` | Вычисленный правильный ответ, добавляемый при генерации варианта. |
| `frozen_answers_json` | Замороженная карта {task_number -> correct_answer} на момент старта попытки. |
| `options` | Массив вариантов ответа для choice-типов. |
| `options[0]` конвенция | Соглашение, что первый элемент options = правильный вариант (для matching). |
| `config_json` | JSON-конфигурация попытки в БД, включает задачи и zadaniya. |
| `normalizeTaskForMiniApp()` | Legacy-нормализатор в PoolService (заменяется на MiniAppTaskNormalizer). |
| `TaskAnswerResolver` | Сервис разрешения и проверки ответов. |
| `CanonicalAnswerSchema` | Типозависимая схема семантики ответов (P1.2). |
| `MiniAppTaskSanitizer` | Серверная очистка задания перед отправкой клиенту (P0.1). |
| `MiniAppTaskNormalizer` | Единый нормализатор формата задания (P1.1). |
