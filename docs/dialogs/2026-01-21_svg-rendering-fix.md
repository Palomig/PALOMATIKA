# Диалог: Исправление SVG рендеринга для OGE генератора

**Дата:** 2026-01-21
**Ветка:** `claude/fix-svg-rendering-TiH9K`

## Контекст

Продолжение работы по синхронизации серверного SVG-рендеринга с клиентским для темы 16 (Окружность).

## Основные задачи

1. Исправить генератор вариантов ОГЭ для использования серверной генерации SVG
2. Синхронизировать `GeometrySvgRenderer` с клиентским рендерингом

## Выполненные изменения

### Коммит 1: `c5acdfc`
**feat: Enable server-side SVG rendering for OGE variant generator**

- Добавлен импорт `GeometrySvgRenderer` в `TestPdfController`
- Добавлено свойство `$svgRenderer` и инъекция через конструктор
- Для геометрических тем (15, 16, 17) вызывается рендеринг SVG
- Добавлен метод `renderGeometrySvgForTask()` для генерации SVG
- Добавлен CSS-класс `.geo-label-bold` в `oge-variant.blade.php`

### Коммит 2: `71b7744`
**fix: Use geometry JSON for topics 15, 16, 17 in OGE generator**

Проблема: `getRandomTasksFromZadanie()` читал из обычного `topic_16.json` (с PNG ссылками), а не из `topic_16_geometry.json` (с данными для SVG рендеринга).

Исправление в `TaskDataService.php`:
```php
// Для геометрических тем используем geometry данные если есть
if (in_array($topicId, ['15', '16', '17']) && $this->geometryDataExists($topicId)) {
    $blocks = $this->getGeometryBlocks($topicId);
} else {
    $blocks = $this->getBlocks($topicId);
}
```

### Коммит 3: `349e957`
**fix: Sync renderCenterOnSide() with client-side rendering**

Проблема: Для задания 78 (center_on_side) серверный рендерер рисовал пунктирную линию OC и точку O, которых нет в клиентской версии.

Изменения в `renderCenterOnSide()`:
- Удалена пунктирная линия OC (радиус)
- Удалена точка и подпись O
- Обновлены координаты окружности: `cx=110, cy=110, r=85`
- Упрощена диаграмма до соответствия клиентской версии

## Обсуждение архитектуры

### Текущая проблема
SVG рендерится динамически при каждом запросе, что создаёт расхождения между версиями.

### Предложенное решение (от пользователя)
SVG должны рендериться **один раз** на сервере и сохраняться:
1. Рендерер прогоняется один раз для всех задач
2. Готовые SVG сохраняются в JSON или как файлы
3. При показе задания берётся готовый SVG

### Варианты реализации
1. **Хранить готовый SVG в JSON** — в поле `task.image` или `task.rendered_svg`
2. **Хранить SVG как файлы** — в `public/images/tasks/16/*.svg`

## Структура файлов

### Изменённые файлы
- `app/Http/Controllers/TestPdfController.php` — добавлен SVG рендеринг
- `app/Services/TaskDataService.php` — использование geometry JSON для тем 15-17
- `app/Services/GeometrySvgRenderer.php` — исправлен `renderCenterOnSide()`
- `resources/views/test/oge-variant.blade.php` — добавлен CSS класс

### Ключевые JSON файлы
- `storage/app/tasks/topic_16.json` — основные данные с PNG ссылками
- `storage/app/tasks/topic_16_geometry.json` — данные для SVG рендеринга (svg_type, geometry, params)

## TODO

- [ ] Решить: динамический рендеринг vs предварительно сгенерированные SVG
- [ ] Если предварительная генерация — создать команду для batch-рендеринга
- [ ] Обновить остальные svg_type методы для синхронизации с клиентом
