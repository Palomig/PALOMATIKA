#!/usr/bin/env python3
"""
Скрипт генерации контента для тренажёра ОГЭ через Claude API.

ИСПОЛЬЗОВАНИЕ:
1. Установи зависимости: pip install anthropic
2. Установи API ключ: export ANTHROPIC_API_KEY="sk-ant-..."
3. Запусти: python generate_content.py

Скрипт обрабатывает задачи порциями и сохраняет прогресс.
Можно прервать и продолжить — уже обработанные задачи пропускаются.
"""

import json
import os
import time
import re
from pathlib import Path

try:
    import anthropic
except ImportError:
    print("Установи библиотеку: pip install anthropic")
    exit(1)

# Конфигурация
INPUT_FILE = "oge_data/parsed_full.json"
OUTPUT_FILE = "oge_data/enriched_tasks.json"
PROGRESS_FILE = "oge_data/generation_progress.json"
THEORY_FILE = "oge_data/theory_blocks.json"

BATCH_SIZE = 10  # Задач за один запрос
DELAY_BETWEEN_REQUESTS = 1  # Секунд между запросами

# Клиент API
client = anthropic.Anthropic()


def load_data():
    """Загружает распарсенные данные"""
    with open(INPUT_FILE, 'r', encoding='utf-8') as f:
        return json.load(f)


def load_progress():
    """Загружает прогресс генерации"""
    if os.path.exists(PROGRESS_FILE):
        with open(PROGRESS_FILE, 'r', encoding='utf-8') as f:
            return json.load(f)
    return {"processed_task_ids": [], "processed_subtopic_ids": []}


def save_progress(progress):
    """Сохраняет прогресс"""
    with open(PROGRESS_FILE, 'w', encoding='utf-8') as f:
        json.dump(progress, f, ensure_ascii=False, indent=2)


def save_enriched_tasks(tasks):
    """Сохраняет обогащённые задачи"""
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(tasks, f, ensure_ascii=False, indent=2)


def save_theory(theory_blocks):
    """Сохраняет блоки теории"""
    with open(THEORY_FILE, 'w', encoding='utf-8') as f:
        json.dump(theory_blocks, f, ensure_ascii=False, indent=2)


def generate_task_content(tasks_batch):
    """
    Генерирует контент для пачки задач:
    - Правильный ответ
    - 2-3 подсказки
    - Типичные ошибки с объяснениями
    - Пошаговое решение
    """
    
    tasks_text = ""
    for i, task in enumerate(tasks_batch):
        tasks_text += f"""
---
ЗАДАЧА {i+1} (ID: {task['id']}, ОГЭ №{task['oge_number']}):
{task['text']}
---
"""

    prompt = f"""Ты — эксперт по подготовке к ОГЭ по математике. 
Для каждой задачи ниже предоставь:

1. ПРАВИЛЬНЫЙ ОТВЕТ (только число или краткий ответ)
2. ПОДСКАЗКИ (2-3 штуки, от общей к конкретной, НЕ давай ответ в подсказках)
3. ТИПИЧНЫЕ ОШИБКИ (2-3 неправильных ответа, которые часто дают ученики, и почему они ошибаются)
4. РЕШЕНИЕ (пошаговое, понятное для 9-классника)

Формат ответа — строго JSON:
{{
  "tasks": [
    {{
      "id": <ID задачи>,
      "answer": "<ответ>",
      "hints": [
        "Подсказка 1 (общее направление)",
        "Подсказка 2 (более конкретная)",
        "Подсказка 3 (почти ответ, но не ответ)"
      ],
      "common_errors": [
        {{"wrong_answer": "<неправильный ответ>", "explanation": "Почему ученик мог так ответить"}},
        {{"wrong_answer": "<неправильный ответ 2>", "explanation": "Причина ошибки"}}
      ],
      "solution": "Шаг 1: ...\\nШаг 2: ...\\nШаг 3: ...\\nОтвет: ..."
    }}
  ]
}}

ВАЖНО:
- Если задача содержит неполный текст или непонятна — поставь answer: "ТРЕБУЕТ_ПРОВЕРКИ"
- Подсказки должны НАПРАВЛЯТЬ мышление, а не давать готовый ответ
- В типичных ошибках указывай РЕАЛЬНЫЕ ошибки, которые делают ученики
- Решение должно быть понятным для слабого ученика

ЗАДАЧИ:
{tasks_text}

Ответь ТОЛЬКО валидным JSON без дополнительного текста."""

    try:
        response = client.messages.create(
            model="claude-sonnet-4-20250514",
            max_tokens=4000,
            messages=[{"role": "user", "content": prompt}]
        )
        
        # Извлекаем JSON из ответа
        response_text = response.content[0].text
        
        # Пробуем найти JSON
        json_match = re.search(r'\{[\s\S]*\}', response_text)
        if json_match:
            return json.loads(json_match.group())
        else:
            print(f"Не удалось извлечь JSON из ответа")
            return None
            
    except Exception as e:
        print(f"Ошибка API: {e}")
        return None


def generate_theory_block(subtopic, topic_name, sample_tasks):
    """
    Генерирует теоретический блок для подтемы:
    - Краткое объяснение
    - Лайфхак
    - Пример решения
    """
    
    tasks_examples = "\n".join([f"- {t['text'][:200]}" for t in sample_tasks[:3]])
    
    prompt = f"""Ты — репетитор по математике, готовящий учеников к ОГЭ на оценку 3-4.
Твоя задача — объяснить тему ПРОСТО и ПРАКТИЧНО, без глубокой теории.

ТЕМА: {topic_name}
ПОДТЕМА: {subtopic['name']}

Примеры задач по этой подтеме:
{tasks_examples}

Создай обучающий блок в формате JSON:
{{
  "subtopic_id": {subtopic['id']},
  "title": "Краткое название (например: 'Как найти угол по биссектрисе')",
  "content_html": "<p>Краткое объяснение что это и зачем (2-3 предложения)</p><p>Ключевое правило или формула</p>",
  "lifehack_html": "<p><b>Лайфхак:</b> Простой способ запомнить или быстро решить (1-2 предложения)</p>",
  "example_solution_html": "<p><b>Пример:</b></p><p>Задача: ...</p><p>Решение:</p><ol><li>Шаг 1</li><li>Шаг 2</li></ol><p><b>Ответ:</b> ...</p>"
}}

ВАЖНО:
- Пиши для ученика, который хочет СДАТЬ экзамен, а не понять всю математику
- Используй простые слова
- Лайфхак должен быть реально полезным трюком
- HTML должен быть валидным

Ответь ТОЛЬКО валидным JSON."""

    try:
        response = client.messages.create(
            model="claude-sonnet-4-20250514",
            max_tokens=2000,
            messages=[{"role": "user", "content": prompt}]
        )
        
        response_text = response.content[0].text
        json_match = re.search(r'\{[\s\S]*\}', response_text)
        if json_match:
            return json.loads(json_match.group())
        return None
        
    except Exception as e:
        print(f"Ошибка API при генерации теории: {e}")
        return None


def main():
    print("=" * 50)
    print("ГЕНЕРАЦИЯ КОНТЕНТА ДЛЯ ТРЕНАЖЁРА ОГЭ")
    print("=" * 50)
    
    # Проверяем API ключ
    if not os.environ.get('ANTHROPIC_API_KEY'):
        print("\n❌ Не установлен ANTHROPIC_API_KEY!")
        print("Выполни: export ANTHROPIC_API_KEY='sk-ant-...'")
        return
    
    # Загружаем данные
    print("\n📂 Загрузка данных...")
    data = load_data()
    progress = load_progress()
    
    tasks = data['tasks']
    subtopics = data['subtopics']
    topics = data['topics']
    
    print(f"   Всего задач: {len(tasks)}")
    print(f"   Всего подтем: {len(subtopics)}")
    print(f"   Уже обработано задач: {len(progress['processed_task_ids'])}")
    print(f"   Уже обработано подтем: {len(progress['processed_subtopic_ids'])}")
    
    # Фильтруем необработанные
    unprocessed_tasks = [t for t in tasks if t['id'] not in progress['processed_task_ids']]
    unprocessed_subtopics = [s for s in subtopics if s['id'] not in progress['processed_subtopic_ids']]
    
    print(f"\n📝 К обработке: {len(unprocessed_tasks)} задач, {len(unprocessed_subtopics)} подтем")
    
    if not unprocessed_tasks and not unprocessed_subtopics:
        print("\n✅ Всё уже обработано!")
        return
    
    # Оценка стоимости
    estimated_requests = (len(unprocessed_tasks) // BATCH_SIZE) + len(unprocessed_subtopics)
    estimated_cost = estimated_requests * 0.015  # ~$0.015 за запрос
    print(f"\n💰 Примерная стоимость: ${estimated_cost:.2f}")
    
    input("\nНажми Enter для начала генерации (Ctrl+C для отмены)...")
    
    # === ГЕНЕРАЦИЯ КОНТЕНТА ДЛЯ ЗАДАЧ ===
    print("\n" + "=" * 50)
    print("ГЕНЕРАЦИЯ КОНТЕНТА ДЛЯ ЗАДАЧ")
    print("=" * 50)
    
    enriched_tasks = {t['id']: t for t in tasks}
    
    for i in range(0, len(unprocessed_tasks), BATCH_SIZE):
        batch = unprocessed_tasks[i:i+BATCH_SIZE]
        batch_ids = [t['id'] for t in batch]
        
        print(f"\n🔄 Обработка задач {i+1}-{i+len(batch)} из {len(unprocessed_tasks)}...")
        
        result = generate_task_content(batch)
        
        if result and 'tasks' in result:
            for task_result in result['tasks']:
                task_id = task_result.get('id')
                if task_id and task_id in enriched_tasks:
                    enriched_tasks[task_id]['correct_answer'] = task_result.get('answer')
                    enriched_tasks[task_id]['hints'] = task_result.get('hints', [])
                    enriched_tasks[task_id]['common_errors'] = task_result.get('common_errors', [])
                    enriched_tasks[task_id]['solution_steps'] = task_result.get('solution')
                    enriched_tasks[task_id]['ai_generated'] = True
            
            # Обновляем прогресс
            progress['processed_task_ids'].extend(batch_ids)
            save_progress(progress)
            
            # Сохраняем задачи
            data['tasks'] = list(enriched_tasks.values())
            save_enriched_tasks(data)
            
            print(f"   ✅ Обработано {len(batch)} задач")
        else:
            print(f"   ⚠️ Ошибка при обработке пачки, пропускаем")
        
        time.sleep(DELAY_BETWEEN_REQUESTS)
    
    # === ГЕНЕРАЦИЯ ТЕОРИИ ===
    print("\n" + "=" * 50)
    print("ГЕНЕРАЦИЯ ТЕОРЕТИЧЕСКИХ БЛОКОВ")
    print("=" * 50)
    
    theory_blocks = []
    if os.path.exists(THEORY_FILE):
        with open(THEORY_FILE, 'r', encoding='utf-8') as f:
            theory_blocks = json.load(f)
    
    for subtopic in unprocessed_subtopics:
        print(f"\n🔄 Генерация теории для: {subtopic['name']}...")
        
        # Находим тему
        topic = next((t for t in topics if t['id'] == subtopic['topic_id']), None)
        topic_name = topic['name'] if topic else "Математика"
        
        # Находим примеры задач
        sample_tasks = [t for t in tasks if t['subtopic_id'] == subtopic['id']][:5]
        
        if not sample_tasks:
            print(f"   ⚠️ Нет задач для подтемы, пропускаем")
            continue
        
        result = generate_theory_block(subtopic, topic_name, sample_tasks)
        
        if result:
            theory_blocks.append(result)
            progress['processed_subtopic_ids'].append(subtopic['id'])
            save_progress(progress)
            save_theory(theory_blocks)
            print(f"   ✅ Теория сгенерирована")
        else:
            print(f"   ⚠️ Ошибка генерации теории")
        
        time.sleep(DELAY_BETWEEN_REQUESTS)
    
    print("\n" + "=" * 50)
    print("✅ ГЕНЕРАЦИЯ ЗАВЕРШЕНА!")
    print("=" * 50)
    print(f"\nФайлы:")
    print(f"  - Задачи с контентом: {OUTPUT_FILE}")
    print(f"  - Блоки теории: {THEORY_FILE}")
    print(f"\nТеперь можно импортировать в Laravel!")


if __name__ == "__main__":
    main()
