---
name: ege-tasks
description: "Use when working with EGE (ЕГЭ) tasks — creating/editing EGE JSON files, EGE-specific SVG baking, or EGE task display. Covers EGE architecture, file structure, and task types."
---

# ЕГЭ — Структура и архитектура

## Обособленность от ОГЭ

ЕГЭ и ОГЭ — **полностью обособленные направления**: разные директории, роуты, инструменты проверки, целевая аудитория (9 класс vs 11 класс).

## Структура файлов ЕГЭ

```
storage/app/tasks/ege/
├── topic_01.json              # Задание 1
├── topic_01_geometry.json     # Геометрия (если есть)
├── topic_02.json
└── ...

public/images/tasks/ege/
├── 01/
├── 02/
└── ...

docs/ege_data/images/          # Референсы ЕГЭ
```

## Структура JSON для ЕГЭ

```json
{
  "topic_id": "01",
  "exam_type": "ege",
  "meta": {
    "title": "Задание 1 — Вычисления",
    "description": "Простейшие текстовые задачи",
    "color": "blue"
  },
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
        "answer": "0.9",
        "image": null
      }]
    }]
  }]
}
```

## Типы заданий ЕГЭ

| Номер | Тема | Тип |
|-------|------|-----|
| 01 | Простейшие текстовые задачи | `word_problem` |
| 02 | Чтение графиков и диаграмм | `graph_reading` |
| 03 | Квадратная решётка | `grid` |
| 04-05 | Вероятности | `probability` |
| 06 | Простейшие уравнения | `equation` |
| 07 | Вычисление значений функций | `expression` |
| 08 | Производные и первообразные | `derivative` |
| 09 | Преобразование выражений | `expression` |
| 10 | Задачи с прикладным содержанием | `applied_problem` |
| 11 | Текстовые задачи | `word_problem` |
| 12 | Наибольшее/наименьшее значение | `optimization` |
| 13 | Планиметрия | `geometry` |
| 14 | Стереометрия | `geometry_3d` |
| 15 | Неравенства | `inequality` |
| 16 | Экономическая задача | `economics` |
| 17 | Планиметрическая задача | `geometry` |
| 18 | Параметры | `parametric` |
| 19 | Числа и их свойства | `number_theory` |

## Команды

```bash
php artisan svg:bake-ege 13      # SVG для геометрического задания ЕГЭ
php artisan cache:clear
php artisan deploy:refresh
```

## Инструмент проверки

```blade
@include('components.task-review-tool-ege', ['topicId' => '01'])
```

Фиолетовая цветовая схема, localStorage ключ: `palomatika_reviews_ege_topic_{id}`

## Роуты ЕГЭ

```php
Route::get('/ege', [EgeController::class, 'index']);
Route::get('/ege/{id}', [EgeController::class, 'show']);
Route::get('/ege-variant', [EgeController::class, 'generator']);
Route::get('/ege-variant/{hash}', [EgeController::class, 'showVariant']);
```

## Процесс создания заданий из PDF

1. PDF файлы в `pdf-sources/ege-trainer/`
2. Извлечь текст, изображения → `docs/ege_data/images/`
3. Создать JSON: `storage/app/tasks/ege/topic_XX.json`
4. Для геометрии: создать `*_geometry.json` → `php artisan svg:bake-ege XX`
5. Для PNG: `public/images/tasks/ege/{topic}/`
6. Проверить на `/ege/{id}` с `task-review-tool-ege`

## Статус базы ЕГЭ

Все задания 01-19: ⏳ Ожидают

## Чек-лист

- [ ] Номер соответствует PDF
- [ ] Текст условия точно как в PDF
- [ ] Ответ правильный
- [ ] Изображение привязано (если есть)
- [ ] Для геометрии: SVG создан
- [ ] Тип задания корректный
- [ ] JSON валидный
