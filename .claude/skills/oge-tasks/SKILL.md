---
name: oge-tasks
description: "Use when working with OGE task data — creating/editing JSON task files, working with TaskDataService, adding new topics, or debugging task display. Covers OGE task architecture, JSON structure, and task types."
---

# OGE Task Architecture

## Единый источник данных (Single Source of Truth)

Все задания хранятся в JSON-файлах и управляются через `TaskDataService`.

```
storage/app/tasks/
├── topic_06.json    # Дроби и степени
├── topic_07.json    # Числа, координатная прямая
├── topic_08.json    # Квадратные корни
├── ...
└── topic_19.json    # Геом. высказывания
```

## TaskDataService — Центральный сервис

**Файл:** `app/Services/TaskDataService.php`

```php
$service = app(TaskDataService::class);
$blocks = $service->getBlocks('06');
$meta = $service->getTopicMeta('06');       // ['title', 'color', ...]
$stats = $service->getTopicStats('06');     // ['blocks', 'zadaniya', 'tasks']
$tasks = $service->getRandomTasks('06', count: 3);
$service->saveTopicData('06', $data);
$service->clearCache();
```

## Структура JSON-файла темы

```json
{
  "topic_id": "06",
  "blocks": [{
    "number": 1,
    "title": "ФИПИ",
    "zadaniya": [{
      "number": 1,
      "instruction": "Найдите значение выражения",
      "type": "expression",
      "tasks": [{
        "id": 1,
        "expression": "\\frac{3}{4} \\cdot \\frac{6}{5}",
        "answer": "0.9"
      }]
    }]
  }]
}
```

## Типы заданий (type)

| Тип | Описание | Темы |
|-----|----------|------|
| `expression` | Вычисление выражения | 06, 08 |
| `choice` | Выбор из вариантов | 07 |
| `simple_choice` | Простой выбор | 07 |
| `fraction_choice` | Выбор дроби | 07 |
| `interval_choice` | Выбор интервала | 07 |
| `matching` | Соответствие графиков | 11 |
| `matching_signs` | Соответствие знаков | 11 |
| `word_problem` | Текстовая задача | 10, 14 |
| `geometry` | Геометрия | 15, 16, 17 |
| `grid` | Клетчатая бумага | 18 |
| `statements` | Анализ утверждений | 19 |

## Статус базы заданий

| Тема | Готово? | Примечание |
|------|---------|------------|
| 06 Дроби и степени | ✅ | |
| 07 Числа, координатная прямая | ✅ | |
| 08 Квадратные корни | ✅ | |
| 09 Уравнения | ✅ | |
| 10 Теория вероятностей | ✅ | |
| 11 Графики функций | ✅ | SVG графики |
| 12 Расчёты по формулам | ✅ | |
| 13 Неравенства | ✅ | |
| 14 Прогрессии | ✅ | |
| 15 Треугольники | ✅ | Static SVG |
| 16 Окружность | ✅ | Static SVG |
| 17 Четырёхугольники | ✅ | |
| 18 Фигуры на клетчатой бумаге | ✅ | |
| 19 Анализ геом. высказываний | ✅ | |
| 01-05 Комплексные | ⏳ | |
| 20-25 | ⏳ | |

## Роуты

```php
// Унифицированные
Route::get('/topics', [TopicController::class, 'index']);
Route::get('/topics/{id}', [TopicController::class, 'show']);

// Legacy (обратная совместимость)
Route::get('/test', [TestPdfController::class, 'index']);
Route::get('/test/{id}', [TestPdfController::class, 'topic']);

// Генератор вариантов ОГЭ
Route::get('/oge', [TestPdfController::class, 'ogeGenerator']);
Route::get('/oge/{hash}', [TestPdfController::class, 'showOgeVariant']);
```

## Генератор вариантов ОГЭ

- `/oge` — страница генератора
- `/oge/{hash}` — вариант (10-символьный хэш, детерминированный через `mt_srand(crc32(hash))`)

## Views

```
resources/views/
├── topics/show.blade.php           # Унифицированная страница темы
├── layouts/topic.blade.php         # Layout для страниц тем
├── components/task-review-tool.blade.php  # Инструмент проверки
└── test/oge-variant.blade.php      # Вариант ОГЭ
```

## KaTeX: Отображение формул

Все дроби автоматически получают `\displaystyle` для крупного отображения.

## Как добавить новые задания

1. Создать/отредактировать `storage/app/tasks/topic_{id}.json`
2. Следовать структуре данных (см. выше)
3. Очистить кэш: `php artisan cache:clear`
4. Для изображений — `public/images/tasks/{topic_id}/`

## ВАЖНОЕ ПРАВИЛО: Соответствие PDF-источникам

1. **Номера заданий ТОЧНО соответствуют PDF** — нельзя менять для "красоты"
2. **Текст заданий = текст из PDF** — формулировки, числа, ответы
3. **Порядок разделов по номерам заданий** — разделы по возрастанию, но номера не менять

## PDF-источники

**Директория:** `pdf-sources/oge-trainer/` (в .gitignore)
**Референсные изображения:** `docs/oge_data/images/`
**Формат имён:** `oge{номер_темы}_p{страница}_img{номер}.png`
