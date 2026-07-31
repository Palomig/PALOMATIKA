# Банки заданий — карта всех 3 направлений

> Дата скана: 2026-05-03. Цифры по объёму получены прохождением по `storage/app/tasks/`.

## Принципы (общие для всех 3 направлений)

1. **Источник истины** — JSON в `storage/app/tasks/`. Никаких захардкоженных данных в контроллерах.
2. **Номера = номера в PDF.** Нельзя менять для красоты.
3. **SVG, не PNG.** PDF-изображения в `docs/oge_data/images/` и `docs/ege_data/images/` — только референс.
4. **`*_geometry.json`** — это **источник** для геометрии. `topic_XX.json` со встроенными SVG **генерируется** через `php artisan svg:bake {id}` (или `svg:bake-ege`). Вручную `topic_XX.json` для геометрических задач **не редактируем**.
5. **TaskDataService / EgeTaskDataService / VprTaskDataService** — единственный путь чтения банков из кода.

> **Выбор задач в урок/ДЗ** — единая точка входа: общий drill-down picker `resources/views/pwa/_shared/task-picker.blade.php` (класс → полоски навыков/тем с «1 примером» → уровень/блок → задачи). Опции отдаёт `LessonTaskPickerService` (`/lessons/picker-options`), выбранные `{bank, refs}` резолвит `TaskBankResolver`. Банк скрыт за классом (7/8 → `alg-skill`, 9 ОГЭ → `oge`).

## ОГЭ — `storage/app/tasks/`

**18 файлов** (17 с заданиями + 1 пустой), 4 геометрических `*_geometry.json`. Топики 1–5 не существуют (это устные задания в реальном ОГЭ, которые мы не покрываем).

| Topic | Tasks | Type(s) | Геометрия | Заметки |
|---|---|---|---|---|
| 06 | 210 | (mixed, без поля `type`) | — | старый формат, без явного `type` |
| 07 | 127 | choice / fraction_choice / interval_choice / sqrt_choice / comparison и др. | — | очень разнообразный — 22 типа |
| 08 | 366 | (mixed) | — | старый формат, без явного `type`, **самый крупный** |
| 09 | 206 | word_problem, expression | — | |
| 10 | 262 | word_problem | — | |
| 11 | 63 | matching, matching_signs, graph_statements, matching_4 | — | |
| 12 | 88 | word_problem | — | |
| 13 | 166 | choice, expression | — | |
| 14 | 50 | word_problem | — | |
| 15 | 105 | geometry | ✅ `topic_15_geometry.json` | |
| 16 | 126 | geometry | ✅ `topic_16_geometry.json` | |
| 17 | 173 | geometry | ✅ `topic_17_geometry.json` | |
| 18 | 156 | grid_image, grid_image_with_question | ✅ `topic_18_geometry.json` | |
| 19 | 0 | statements | — | пусто, скелет |
| 20 | 134 | word_problem | — | |
| 21 | 132 | word_problem | — | |
| 23 | 120 | geometry | — | (топики 22, 24, 25 не существуют) |

**Итого:** ~2484 задач, 17 топиков с контентом.

**Допустимые `type`** (из CLAUDE.md): `expression`, `choice`, `simple_choice`, `fraction_choice`, `interval_choice`, `matching`, `matching_signs`, `word_problem`, `geometry`, `grid`, `statements`. Реально в данных встречаются и более узкие подтипы (см. topic_07).

## ЕГЭ — `storage/app/tasks/ege/`

**18 файлов** (нет topic_03), 1 геометрический `topic_01_geometry.json`.

| Topic | Tasks | Type | Заметки |
|---|---|---|---|
| 01 | 216 | geometry | планиметрия, есть `topic_01_geometry.json` |
| 02 | 52 | vector | векторы |
| 04 | 76 | probability | теория вероятностей |
| 05 | 58 | probability | продолжение |
| 06 | 160 | equation | уравнения |
| 07 | 200 | expression | выражения |
| 08 | 180 | expression | выражения |
| 09 | 52 | word_problem | текстовая |
| 10 | 56 | graph_reading | чтение графиков |
| 11 | 52 | applied_problem | прикладная задача |
| 12 | 96 | expression | |
| 13 | 72 | equation_ab | уравнение с параметрами |
| 14 | 56 | geometry_3d | стереометрия |
| 15 | 84 | inequality | неравенства |
| 16 | 48 | word_problem | |
| 17 | 72 | word_problem | |
| 18 | 108 | word_problem | |
| 19 | 52 | word_problem | |

**Итого:** ~1690 задач, 18 топиков. Topic 03 — пропуск (видимо устная часть).

**Файлы JSON содержат поле `exam_type: "ege"`.** Структура совместима с ОГЭ, но `meta.color` обычно `blue`.

## Алгебра / Геометрия — `storage/app/tasks/alg/` и `storage/app/tasks/geom/`

Скелет под наполнение (создан 2026-05-04). Темы определяются **динамически** — по существующим `topic_NN.json` в директории класса. Каждая тема имеет свою `meta.title/description/color` внутри JSON.

| Раздел | Класс(ы) | Директория | Маршруты |
|---|---|---|---|
| Алгебра | 5, 6, 7, 8 | `storage/app/tasks/alg/grade_{N}/` | `/alg-topics`, `/alg-topics/{grade}/{id}` |
| Геометрия | 7, 8, 9 | `storage/app/tasks/geom/grade_{N}/` | `/geom-topics`, `/geom-topics/{grade}/{id}` |

**Сервисы:** `App\Services\AlgTaskDataService`, `App\Services\GeomTaskDataService` — сканируют директорию и возвращают только реально существующие топики.

**Контроллеры:** `AlgTopicController`, `GeomTopicController` (`teacher,admin`).

**Views:** `resources/views/alg-topics/{index,show}.blade.php`, `resources/views/geom-topics/{index,show}.blade.php` — копируют визуальный стиль `topics/` и `vpr-topics/`.

Чтобы добавить тему — положить JSON со структурой как в банке ОГЭ (поля `topic_id`, `meta`, `blocks` → `zadaniya` → `tasks`), затем `php artisan cache:clear`.

## ВПР — `storage/app/tasks/vpr/grade_{N}/`

**4 класса × 18 топиков = 72 файла.**

| Класс | Tasks (всего) | Статус | Заметки |
|---|---|---|---|
| **5** | ~270 | заполнен | топик 18 пустой, остальные 10–73 задачи. Типы: word_problem, expression |
| **6** | ~198 | заполнен | топик 18 пустой, остальные ~11 задач каждый |
| **7** | 0 | **скелет** | все 18 файлов пустые — структура есть, контента нет |
| **8** | 0 | **скелет** | то же самое |

Скрипт парсинга sdamgia в работе: `scripts/download-sdamgia-vpr5-pdfs.mjs` (и тест) — пользователь сейчас собирает контент 5 класса.

**Routes ВПР:**
- Учитель: `/vpr-topics`, `/vpr-topics/{grade}/{id}`, `/api/vpr-topics/{grade}/{topicId}` (доступ teacher/admin)
- Ученик (PWA): `student.palomatika.ru/vpr/...` через `Pwa/VprController` — home, taskDatabase, startMini, startFull, test, results
- Учитель view-as: можно «посмотреть как ученик 5/6/7/8 класса» через сессию `view_as_vpr_grade`

**Сервисы ВПР:**
- `VprTaskDataService` — чтение JSON
- `VprVariantBuilderService` — построение варианта с учётом класса
- `VprVariantPoolService` — пул вариантов

## Структура JSON (общая)

```json
{
  "topic_id": "06",
  "exam_type": "oge" | "ege",        // для ВПР — без поля
  "meta": {                           // не у всех ОГЭ-топиков
    "title": "...",
    "description": "...",
    "color": "blue"
  },
  "blocks": [{
    "number": 1,
    "title": "ФИПИ",
    "zadaniya": [{
      "number": 1,
      "instruction": "...",
      "type": "expression",            // отсутствует в legacy topic_06/08
      "tasks": [
        { "id": 1, "expression": "...", "answer": "0.9", "image": null }
      ]
    }]
  }]
}
```

**Особенности:**
- `topic_06.json` и `topic_08.json` (ОГЭ) — старый формат без поля `type` в zadaniya. При работе с ними учитывать, что классификация типа выводится из контекста.
- `topic_19.json` (ОГЭ, statements) — пустой (0 tasks), но с `type: statements` — задание-скелет под будущее наполнение.

## После любых изменений в JSON

```bash
php artisan cache:clear
# Если правил *_geometry.json:
php artisan svg:bake {id}        # для ОГЭ
php artisan svg:bake-ege {id}    # для ЕГЭ
```

## Связь с DB

JSON-банк — источник для **runtime**. В DB сохраняются:
- `tasks` (6163) — материализованные «пазловые» задачи (с навыками, мастерством)
- `task_skills`, `task_steps`, `step_blocks` — для пазл-механики
- В вариантах (`oge_variants`, `oge_variant_pool_tasks`) ссылка на JSON через `topic_id` + `zadaniya number` + `task id`

## Когда хочешь подробности

- **Структура и типы** → скилл `oge-tasks` или `ege-tasks`
- **Геометрия и SVG** → скилл `geometry-svg`
- **Реальное состояние данных в DB** → MCP `palomatika-db`
