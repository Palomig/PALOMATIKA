---
glob: "storage/app/tasks/ege/*.json"
---

# Правила при работе с заданиями ЕГЭ

## ЕГЭ полностью обособлен от ОГЭ
Разные директории, роуты, контроллеры, целевая аудитория (11 класс).

## Структура JSON (обязательные поля)
```json
{
  "topic_id": "01",
  "exam_type": "ege",
  "meta": { "title": "...", "description": "...", "color": "blue" },
  "blocks": [{
    "number": 1,
    "title": "ФИПИ",
    "zadaniya": [{
      "number": 1,
      "instruction": "...",
      "type": "expression",
      "tasks": [{ "id": 1, "expression": "...", "answer": "0.9", "image": null }]
    }]
  }]
}
```

## Критические ограничения
- Номера заданий = номерам в PDF — **НЕЛЬЗЯ менять**
- `*_geometry.json` для ЕГЭ — не редактировать вручную: регенерировать через `php artisan svg:bake-ege {id}`

## После изменений
```bash
php artisan cache:clear
# Если геометрия:
php artisan svg:bake-ege {id}
```

Для полной документации — используй скилл `ege-tasks`.
