# ТЗ: Semantic SVG Pipeline для OGE-ассетов (без blanket PNG->SVG tracing)

## 1. Цель

Внедрить стратегию семантического SVG-рендеринга для математических учебных материалов PALOMATIKA, при которой:

- геометрия и числовые прямые рендерятся как **semantic SVG** (data-driven / renderer-driven),
- текст, формулы и варианты с формулами остаются **текстом / KaTeX**,
- растровые PNG используются только как **референс / fallback** в явно разрешённых случаях,
- рендер опций подчиняется **явно заданной модальности**, а не эвристикам.

## 2. Обязательные правила (policy)

1. **Запрещено использовать generic PNG→SVG tracing как дефолт** для math education assets.
2. **Геометрия / number-line** должны рендериться через semantic SVG (renderer + данные), а не через трассировку PNG.
3. **Текст и формулы** должны оставаться текстом/KaTeX; запрещено превращать их в traced outlines.
4. **PNG-ассеты** допустимы только как reference/fallback в явно разрешённых местах.
5. **Модальность рендера опций должна быть явной** (`text_options` / `visual_options`); запрещена эвристическая auto-конверсия plain text опций в SVG без явного режима.

## 3. Термины и категории ассетов

- `semantic-svg`: SVG, построенный из структурированных данных (`svg_type`, renderer, pre-baked SVG).
- `text/katex`: текст, формулы, выражения, варианты ответов, отображаемые как текст/KaTeX.
- `raster-reference`: PNG/JPG, используемые как референс или временный fallback.
- `risky runtime PNG usage`: runtime-рендер PNG из `public/images/tasks/*` в пользовательских вьюхах без явной политики/пометки fallback.

## 4. Данные: явная модальность рендера опций

### 4.1 Поля

Вводится/стандартизируется поле:

- `options_render_mode` (уровень `zadanie` и/или `task`)

Допустимые значения:

- `text_options` — рендерить варианты ответа только как текст/KaTeX
- `visual_options` — рендерить варианты ответа как визуальные элементы (например, interval SVG)

### 4.2 Приоритет

1. `task.options_render_mode`
2. `zadanie.options_render_mode`
3. fallback policy (только для legacy-тем вне rollout)

### 4.3 Phase 1 rollout

Для тем OGE `07` и `13`:

- по умолчанию нормализовать `options_render_mode` в `text_options` для заданий с вариантами ответа, если поле не задано явно;
- не выполнять авто-переключение в `visual_options` по эвристике.

Это обеспечивает backward compatibility по данным (JSON не обязательно переписывать сразу), но делает поведение рендера предсказуемым.

## 5. Архитектура решения

### 5.1 Central policy (single source of truth)

Создать централизованный helper/service для решения по модальности опций:

- нормализация `options_render_mode` в данных тем;
- валидация допустимых значений режима;
- решение `shouldRenderIntervalSvg(...)`;
- (legacy-only) эвристика распознавания interval-like options для тем вне rollout.

Требование: **вьюхи не содержат ad-hoc эвристик** вида `isIntervalOption()` / `allOptionsAreIntervals()`.

### 5.2 Рендереры / вьюхи

- `tasks.types.choice` и связанные OGE views используют central policy.
- Рендер number-line/geometry остаётся semantic SVG.
- `visual_options` используется только при явном задании режима.

## 6. Политика PNG

### 6.1 Разрешено

- PNG как reference/fallback для исторических/неперенесённых заданий.
- PNG как временное решение до появления semantic renderer.

### 6.2 Запрещено

- blanket PNG->SVG tracing для всех задач темы;
- трассировка текста/формул в контуры;
- скрытая runtime-подмена текстовых опций на SVG по эвристике.

## 7. Аудит ассетов (Phase 1)

Добавить lightweight artisan-команду аудита, которая:

- классифицирует текущие JSON-ассеты по категориям:
  - `semantic-svg`
  - `text/katex`
  - `raster-reference`
- находит и флагирует `risky runtime PNG usage` в коде (views/php), где PNG рендерится напрямую в runtime.

Команда должна поддерживать человекочитаемый отчёт и JSON-режим (для CI/анализа).

## 8. Phase 1 (в этой поставке)

### 8.1 Scope

1. Ввести central policy service для модальности опций.
2. Нормализовать `options_render_mode` для OGE тем `07`/`13` (минимально инвазивно, без массовой миграции JSON).
3. Заменить ad-hoc эвристику во вьюхах на вызов policy service.
4. Добавить автотесты:
   - plain-text interval-like options не конвертируются в SVG без явного `visual_options`;
   - при `visual_options` interval SVG рендерится;
   - данные темы 07 нормализуются с явным `text_options`.
5. Добавить artisan-аудит ассетов.

### 8.2 Non-goals (Phase 1)

- Полная миграция всех тем на explicit `options_render_mode` в JSON-файлах.
- Удаление всех PNG ассетов.
- Переписывание legacy debug-страниц `test/topic07` и `test/topic13`.
- Автоматическая генерация semantic SVG для всех типов задач темы 13.

## 9. Требования к совместимости

- Изменения должны быть backward compatible.
- Для тем вне `07/13` допускается legacy fallback-эвристика (до отдельного rollout).
- Существующие данные и рендер number-line/geometry не должны ломаться.

## 10. Критерии приёмки

1. В `tasks.types.choice` нет локальной эвристики выбора interval SVG; используется central policy.
2. Для темы 07 interval-like text options без явного режима рендерятся как текст.
3. `visual_options` явно включает interval SVG.
4. Для темы 13 текстовые варианты/формулы не превращаются в interval SVG без явного режима.
5. Доступна artisan-команда аудита ассетов с классификацией и флагами risky PNG runtime usage.
6. Целевые тесты проходят.

## 11. Следующие фазы (после Phase 1)

- Phase 2: явная разметка `options_render_mode` в JSON/генераторах для всех релевантных тем.
- Phase 3: расширение semantic renderers (геометрия/графики/спец.визуализации) и сокращение raster fallback.
- Phase 4: CI-проверки policy (например, запрет новых risky runtime PNG-path без allowlist).
