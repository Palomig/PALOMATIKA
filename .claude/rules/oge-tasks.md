---
glob: "storage/app/tasks/topic_??.json"
---

# Правила при работе с заданиями ОГЭ

## Критические ограничения
- Номера заданий (`number`) = номерам в PDF — **НЕЛЬЗЯ менять**
- Текст условий и ответы — **точно как в источнике PDF**
- `topic_15.json`, `topic_16.json` — **НЕ редактировать вручную**: они генерируются через `php artisan svg:bake {id}` из `*_geometry.json`

## Структура JSON (обязательные поля)
```json
{
  "topic_id": "06",
  "blocks": [{
    "number": 1,
    "title": "ФИПИ",
    "zadaniya": [{
      "number": 1,
      "instruction": "...",
      "type": "expression",
      "tasks": [{ "id": 1, "expression": "...", "answer": "0.9" }]
    }]
  }]
}
```

## Допустимые типы (type)
`expression`, `choice`, `simple_choice`, `fraction_choice`, `interval_choice`, `matching`, `matching_signs`, `word_problem`, `geometry`, `grid`, `statements`

## После изменений
```bash
php artisan cache:clear
```

Для полной документации — используй скилл `oge-tasks`.
