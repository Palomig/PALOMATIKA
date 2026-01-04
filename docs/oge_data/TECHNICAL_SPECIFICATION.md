# Техническое задание для Claude Code
## Платформа подготовки к ОГЭ по математике

---

## 1. ОБЗОР ПРОЕКТА

### Цель
Создать веб-платформу для подготовки школьников к ОГЭ по математике с адаптивным обучением, диагностикой уровня и персональным планом тренировок.

### Аналог
Duolingo (задания + теория + прогресс + геймификация)

### Целевая аудитория
Ученики 8-9 классов, которые хотят сдать ОГЭ на 3-4 (не углублённое изучение, а практическая подготовка к экзамену).

---

## 2. ТЕХНИЧЕСКИЙ СТЕК

### Обязательные требования (ограничения хостинга)
- **Backend:** PHP 8.1+ / Laravel 10 LTS
- **Frontend:** Blade + Alpine.js + Tailwind CSS (CDN)
- **База данных:** MySQL / MariaDB
- **Сервер:** Apache + .htaccess (shared-хостинг)
- **Деплой:** FTP / файловый менеджер (без Docker, без Node.js)

### Дополнительно
- KaTeX (CDN) — рендеринг математических формул
- HTTPS (Let's Encrypt)

---

## 3. СТРУКТУРА БАЗЫ ДАННЫХ

### 3.1 Основные таблицы

#### users
```sql
- id (PK)
- name
- email (unique)
- email_verified_at
- password
- role: ENUM('student', 'admin')
- diagnostic_level: ENUM('not_tested', 'basic', 'medium', 'advanced')
- diagnostic_completed_at
- current_module_id (FK -> modules)
- evrium_student_id (для интеграции с CRM)
- remember_token
- created_at, updated_at, last_active_at
```

#### subjects
```sql
- id (PK)
- name ('Математика')
- slug ('math')
- description
- icon
- is_active, sort_order
- created_at, updated_at
```

#### topics (номера ОГЭ: 01-05, 06, 07... 25)
```sql
- id (PK)
- subject_id (FK)
- oge_number ('01-05', '06', '15'...)
- name ('Треугольники')
- slug
- description
- is_active, sort_order
- created_at, updated_at
```

#### subtopics (типы задач внутри темы)
```sql
- id (PK)
- topic_id (FK)
- name ('Биссектриса', 'Теорема Пифагора')
- slug
- description
- tasks_to_master (сколько задач решить для освоения, default: 3)
- is_active, sort_order
- created_at, updated_at
```

#### tasks (задачи)
```sql
- id (PK)
- topic_id (FK)
- subtopic_id (FK, nullable)
- local_number (номер из PDF)
- source_file (исходный PDF)
- text (текст задачи)
- text_html (HTML с формулами)
- image_path (путь к картинке)
- block ('Блок 1. ФИПИ')
- context (для 01-05: 'Квартира', 'Участок')
- difficulty: 1-5
- answer_type: ENUM('number', 'text', 'sequence')
- correct_answer
- solution_steps (HTML пошагового решения)
- solution_video_url (опционально)
- tags (JSON)
- times_shown, times_correct (статистика)
- ai_generated_at
- manually_edited: BOOLEAN
- is_active
- created_at, updated_at
```

#### task_hints (подсказки к задачам)
```sql
- id (PK)
- task_id (FK)
- hint_order (1, 2, 3...)
- hint_text (HTML)
- ai_generated: BOOLEAN
- created_at, updated_at
UNIQUE(task_id, hint_order)
```

#### task_common_errors (типичные ошибки)
```sql
- id (PK)
- task_id (FK)
- wrong_answer
- explanation (почему ученик мог так ответить)
- indicates_subtopic_id (FK, nullable — какой пробел показывает)
- ai_generated: BOOLEAN
- created_at, updated_at
```

#### theory_blocks (теория)
```sql
- id (PK)
- subtopic_id (FK)
- title
- content_html (WYSIWYG контент)
- lifehack_html (лайфхак)
- images (JSON массив путей)
- video_url
- example_task_id (FK -> tasks, nullable)
- example_solution_html
- sort_order, is_active
- ai_generated: BOOLEAN
- created_at, updated_at
```

#### modules (модули обучения)
```sql
- id (PK)
- subject_id (FK)
- name ('Базовая геометрия')
- slug
- description
- sort_order
- unlock_after_module_id (FK -> modules, nullable)
- test_tasks_per_subtopic (сколько задач на подтему в тесте)
- test_pass_threshold (% для прохождения, default: 100)
- is_active
- created_at, updated_at
```

#### module_subtopics (связь модуль-подтемы)
```sql
- id (PK)
- module_id (FK)
- subtopic_id (FK)
- sort_order
UNIQUE(module_id, subtopic_id)
```

### 3.2 Прогресс пользователя

#### user_progress (прогресс по модулям)
```sql
- id (PK)
- user_id (FK)
- module_id (FK)
- status: ENUM('locked', 'in_progress', 'testing', 'completed')
- started_at
- completed_at
- test_attempts
- last_test_score
- created_at, updated_at
UNIQUE(user_id, module_id)
```

#### user_knowledge (знание подтем)
```sql
- id (PK)
- user_id (FK)
- subtopic_id (FK)
- status: ENUM('unknown', 'learning', 'weak', 'mastered')
- tasks_attempted
- tasks_correct
- current_streak
- last_practiced_at
- next_review_at
- created_at, updated_at
UNIQUE(user_id, subtopic_id)
```

#### user_errors (для внезапных повторений)
```sql
- id (PK)
- user_id (FK)
- task_id (FK)
- subtopic_id (FK, nullable)
- error_count
- last_error_at
- resolved: BOOLEAN
- resolved_at
- created_at, updated_at
UNIQUE(user_id, task_id)
INDEX(user_id, resolved)
```

#### attempts (все попытки)
```sql
- id (PK)
- user_id (FK)
- task_id (FK)
- context_type: ENUM('diagnostic', 'practice', 'module_test', 'simulation', 'review')
- context_id (ID сессии диагностики/симуляции)
- user_answer
- is_correct: BOOLEAN
- hints_used
- time_spent_seconds
- shown_explanation
- created_at
INDEX(user_id, created_at)
```

### 3.3 Настройки (админка)

#### diagnostic_configs
```sql
- id (PK)
- subject_id (FK)
- level: ENUM('easy', 'medium', 'hard')
- topic_ids (JSON массив)
- tasks_count
- max_errors (default: 2)
- sort_order, is_active
- created_at, updated_at
UNIQUE(subject_id, level)
```

#### simulation_configs
```sql
- id (PK)
- subject_id (FK)
- name ('Полный экзамен', 'Мини-проверка')
- slug
- tasks_config (JSON: {"15": 2, "16": 1})
- total_tasks
- time_limit_minutes (nullable = без лимита)
- unlock_after_modules
- sort_order, is_active
- created_at, updated_at
```

#### settings
```sql
- id (PK)
- key (unique)
- value
- type: ENUM('string', 'int', 'bool', 'json')
- description
- updated_at
```

**Начальные настройки:**
```
surprise_review_chance = 20 (int) — Шанс внезапного повторения %
diagnostic_max_errors_default = 2 (int)
hints_enabled = true (bool)
```

---

## 4. ФУНКЦИОНАЛЬНЫЕ МОДУЛИ

### 4.1 Аутентификация (Laravel Breeze)
- Регистрация
- Вход
- Выход
- Восстановление пароля
- Подтверждение email
- Защита от brute-force (throttle)

### 4.2 Диагностика (для новых пользователей)

**Алгоритм:**
```
1. Пользователь начинает диагностику
2. Система даёт задачи уровня "easy" (из diagnostic_configs)
3. Если ошибок >= max_errors → СТОП, уровень = 'basic'
4. Если все верно → переход на "medium"
5. Если ошибок >= max_errors → СТОП, уровень = 'medium'
6. Если все верно → переход на "hard"
7. После hard → уровень = 'advanced', фиксируем конкретные пробелы

Результат: 
- user.diagnostic_level заполняется
- user_knowledge заполняется для тем с ошибками
- Система генерирует персональный план (модули)
```

**Страницы:**
- `/diagnostic` — начало диагностики
- `/diagnostic/task` — страница задачи (AJAX проверка)
- `/diagnostic/result` — результат + план обучения

### 4.3 Модули обучения

**Логика:**
- Модули идут строго по порядку
- Следующий модуль открывается только после прохождения теста предыдущего
- Тест модуля: по 1 задаче на каждую подтему, 100% правильных = пройден

**Страницы:**
- `/modules` — список всех модулей (с замками)
- `/modules/{slug}` — страница модуля (подтемы, прогресс)
- `/modules/{slug}/practice` — практика по модулю
- `/modules/{slug}/test` — тест модуля

### 4.4 Практика (решение задач)

**Алгоритм выбора следующей задачи:**
```php
function getNextTask($user, $module) {
    $surpriseChance = Setting::get('surprise_review_chance'); // 20
    
    // 20% — внезапное повторение из прошлых ошибок
    if (rand(1, 100) <= $surpriseChance) {
        $errorTask = UserError::where('user_id', $user->id)
                              ->where('resolved', false)
                              ->inRandomOrder()
                              ->first();
        if ($errorTask) {
            return $errorTask->task;
        }
    }
    
    // 80% — задача из текущего модуля
    // Выбираем подтему с наименьшим прогрессом
    $weakestSubtopic = $this->getWeakestSubtopic($user, $module);
    
    // Берём задачу, которую пользователь ещё не решал
    return Task::where('subtopic_id', $weakestSubtopic->id)
               ->whereNotIn('id', $user->solvedTaskIds())
               ->inRandomOrder()
               ->first();
}
```

**Страница задачи:**
- Текст задачи + картинка (если есть)
- Поле ввода ответа
- Кнопки подсказок (раскрываются по очереди)
- После ответа:
  - Если верно: "Правильно!" + кнопка "Следующая"
  - Если неверно: объяснение ошибки + пошаговое решение + теория (если есть) + кнопка "Следующая"

### 4.5 Теория

**Показывается:**
- После ошибки (автоматически)
- По запросу из модуля
- Из страницы подтемы

**Содержимое:**
- Краткое объяснение
- Лайфхак
- Пример решения

### 4.6 Симуляции экзамена

**Типы (настраиваются в админке):**
- Мини-проверка (5-10 заданий)
- Первая часть (№1-19)
- Полный экзамен (25 заданий, 3ч 55мин)

**Логика:**
- Задания выбираются случайно из настроенных тем
- Таймер (если настроено время)
- После завершения: результат + работа над ошибками

**Страницы:**
- `/simulations` — список доступных симуляций
- `/simulations/{slug}` — описание + кнопка "Начать"
- `/simulations/{slug}/task` — задача
- `/simulations/{slug}/result` — результат

### 4.7 Прогресс пользователя

**Дашборд (`/dashboard`):**
- Текущий модуль и прогресс
- Статистика: решено задач, правильных %, стрик
- Слабые места (подтемы с низким %)
- Рекомендация что делать дальше

---

## 5. АДМИН-ПАНЕЛЬ (CMS)

### 5.1 Требования
- Полное управление всем контентом
- WYSIWYG редактор для теории и решений
- Загрузка картинок
- Поддержка LaTeX формул

### 5.2 Разделы

#### Задачи (`/admin/tasks`)
- Список с фильтрами (тема, подтема, сложность)
- Поиск по тексту
- Редактирование:
  - Текст задачи (WYSIWYG)
  - Картинка (загрузка)
  - Правильный ответ
  - Подсказки (добавить/удалить/редактировать)
  - Типичные ошибки
  - Пошаговое решение (WYSIWYG)
  - Привязка к теме/подтеме
  - Сложность
  - Активность

#### Темы и подтемы (`/admin/topics`)
- CRUD для тем
- CRUD для подтем внутри темы

#### Теория (`/admin/theory`)
- Список блоков теории по подтемам
- WYSIWYG редактор
- Загрузка картинок
- Поле для лайфхака
- Пример решения

#### Модули (`/admin/modules`)
- Создание/редактирование модулей
- Привязка подтем к модулю (drag-n-drop порядок)
- Настройки теста модуля

#### Диагностика (`/admin/diagnostic`)
- Настройка уровней (easy/medium/hard)
- Выбор тем для каждого уровня
- Количество задач
- Порог ошибок

#### Симуляции (`/admin/simulations`)
- Создание типов симуляций
- Настройка: какие темы, сколько задач, время

#### Пользователи (`/admin/users`)
- Список пользователей
- Просмотр прогресса
- Сброс диагностики
- Блокировка

#### Настройки (`/admin/settings`)
- Редактирование глобальных настроек
- Шанс внезапного повторения
- Включение/выключение функций

#### Статистика (`/admin/stats`)
- Общая статистика платформы
- Самые сложные задачи (низкий % правильных)
- Активность пользователей

---

## 6. БЕЗОПАСНОСТЬ

### Обязательно реализовать:
```
- APP_DEBUG=false на продакшене
- Все секреты в .env
- .env недоступен из веба
- CSRF токены во всех формах (@csrf)
- Хеширование паролей (bcrypt)
- Rate limiting на логин (throttle:5,1)
- Eloquent ORM (защита от SQL injection)
- Blade auto-escape (защита от XSS)
- HTTPS + HttpOnly cookies
- $fillable в моделях (защита от mass assignment)
```

### .htaccess:
```apache
Options -Indexes
ServerSignature Off
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "DENY"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

## 7. МАРШРУТЫ

### Публичные
```
GET  /                          — Главная
GET  /login                     — Вход
POST /login
GET  /register                  — Регистрация
POST /register
POST /logout
GET  /forgot-password           — Восстановление пароля
POST /forgot-password
GET  /reset-password/{token}
POST /reset-password
```

### Авторизованные (middleware: auth)
```
GET  /dashboard                 — Дашборд ученика
GET  /diagnostic                — Начало диагностики
POST /diagnostic/start
GET  /diagnostic/task           — Задача диагностики
POST /diagnostic/check          — Проверка ответа (AJAX)
GET  /diagnostic/result         — Результат

GET  /modules                   — Список модулей
GET  /modules/{slug}            — Страница модуля
GET  /modules/{slug}/practice   — Практика
POST /modules/{slug}/next-task  — Получить следующую задачу (AJAX)
POST /tasks/{id}/check          — Проверить ответ (AJAX)
POST /tasks/{id}/hint           — Получить подсказку (AJAX)
GET  /modules/{slug}/test       — Тест модуля
POST /modules/{slug}/test/submit

GET  /simulations               — Список симуляций
GET  /simulations/{slug}        — Описание
POST /simulations/{slug}/start
GET  /simulations/{slug}/task
POST /simulations/{slug}/check
GET  /simulations/{slug}/result

GET  /theory/{subtopic}         — Страница теории
GET  /progress                  — Детальный прогресс

GET  /profile                   — Профиль
PUT  /profile                   — Обновление профиля
```

### Админские (middleware: auth, admin)
```
GET    /admin                     — Дашборд админа
GET    /admin/tasks               — Список задач
GET    /admin/tasks/{id}/edit     — Редактирование задачи
PUT    /admin/tasks/{id}
DELETE /admin/tasks/{id}

# Аналогично для: topics, subtopics, theory, modules, simulations, users

GET  /admin/diagnostic            — Настройки диагностики
PUT  /admin/diagnostic

GET  /admin/settings              — Настройки
PUT  /admin/settings

GET  /admin/stats                 — Статистика
```

---

## 8. СТРУКТУРА ПРОЕКТА

```
oge-trainer/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    # Breeze
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── TaskController.php
│   │   │   │   ├── TopicController.php
│   │   │   │   ├── SubtopicController.php
│   │   │   │   ├── TheoryController.php
│   │   │   │   ├── ModuleController.php
│   │   │   │   ├── DiagnosticConfigController.php
│   │   │   │   ├── SimulationConfigController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── SettingController.php
│   │   │   │   └── StatsController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DiagnosticController.php
│   │   │   ├── ModuleController.php
│   │   │   ├── PracticeController.php
│   │   │   ├── SimulationController.php
│   │   │   ├── TheoryController.php
│   │   │   └── ProgressController.php
│   │   ├── Middleware/
│   │   │   └── AdminMiddleware.php
│   │   └── Requests/
│   │       └── ... (Form Requests)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Subject.php
│   │   ├── Topic.php
│   │   ├── Subtopic.php
│   │   ├── Task.php
│   │   ├── TaskHint.php
│   │   ├── TaskCommonError.php
│   │   ├── TheoryBlock.php
│   │   ├── Module.php
│   │   ├── ModuleSubtopic.php
│   │   ├── UserProgress.php
│   │   ├── UserKnowledge.php
│   │   ├── UserError.php
│   │   ├── Attempt.php
│   │   ├── DiagnosticConfig.php
│   │   ├── SimulationConfig.php
│   │   └── Setting.php
│   └── Services/
│       ├── DiagnosticService.php       # Логика диагностики
│       ├── TaskSelectorService.php     # Выбор следующей задачи
│       ├── AnswerCheckerService.php    # Проверка ответов
│       ├── ProgressService.php         # Обновление прогресса
│       └── ModuleService.php           # Логика модулей
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php           # Основной layout
│       │   └── admin.blade.php         # Layout админки
│       ├── components/                  # Blade компоненты
│       ├── auth/                        # Breeze views
│       ├── dashboard.blade.php
│       ├── diagnostic/
│       │   ├── index.blade.php
│       │   ├── task.blade.php
│       │   └── result.blade.php
│       ├── modules/
│       │   ├── index.blade.php
│       │   ├── show.blade.php
│       │   ├── practice.blade.php
│       │   └── test.blade.php
│       ├── simulations/
│       │   ├── index.blade.php
│       │   ├── show.blade.php
│       │   ├── task.blade.php
│       │   └── result.blade.php
│       ├── theory/
│       │   └── show.blade.php
│       ├── progress/
│       │   └── index.blade.php
│       └── admin/
│           ├── dashboard.blade.php
│           ├── tasks/
│           │   ├── index.blade.php
│           │   └── edit.blade.php
│           ├── topics/
│           ├── theory/
│           ├── modules/
│           ├── diagnostic/
│           ├── simulations/
│           ├── users/
│           ├── settings/
│           └── stats/
├── database/
│   ├── migrations/
│   │   └── ... (все миграции)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── SubjectSeeder.php
│       ├── TopicSeeder.php
│       ├── TaskSeeder.php              # Импорт из JSON
│       └── SettingSeeder.php
├── public/
│   ├── images/
│   │   └── tasks/                      # Картинки задач
│   ├── uploads/                        # Загруженные файлы
│   └── .htaccess
├── routes/
│   ├── web.php
│   └── admin.php
└── storage/
    └── app/
        └── imports/                    # JSON для импорта
```

---

## 9. ДАННЫЕ ДЛЯ ИМПОРТА

### Файлы (прилагаются):
1. `parsed_full.json` — 5660 задач с темами и подтемами
2. `enriched_tasks.json` — задачи с ответами, подсказками, решениями (после генерации)
3. `theory_blocks.json` — блоки теории
4. `images/` — 788 картинок к задачам

### Формат данных:
```json
{
  "subjects": [...],
  "topics": [...],
  "subtopics": [...],
  "tasks": [
    {
      "id": 1,
      "topic_id": 1,
      "subtopic_id": 1,
      "oge_number": "15",
      "text": "В треугольнике ABC...",
      "correct_answer": "34",
      "hints": ["Подсказка 1", "Подсказка 2"],
      "common_errors": [
        {"wrong_answer": "68", "explanation": "Забыл разделить на 2"}
      ],
      "solution_steps": "Шаг 1:...",
      "images": [{"filename": "oge15_p1_img1.png"}]
    }
  ]
}
```

### Seeder для импорта:
```php
// database/seeders/TaskSeeder.php
public function run()
{
    $data = json_decode(
        file_get_contents(storage_path('app/imports/enriched_tasks.json')), 
        true
    );
    
    foreach ($data['tasks'] as $taskData) {
        $task = Task::create([...]);
        
        foreach ($taskData['hints'] as $i => $hint) {
            TaskHint::create([
                'task_id' => $task->id,
                'hint_order' => $i + 1,
                'hint_text' => $hint,
            ]);
        }
        
        foreach ($taskData['common_errors'] as $error) {
            TaskCommonError::create([
                'task_id' => $task->id,
                'wrong_answer' => $error['wrong_answer'],
                'explanation' => $error['explanation'],
            ]);
        }
    }
}
```

---

## 10. ПОРЯДОК РАЗРАБОТКИ

### Фаза 1: Каркас (приоритет: ВЫСОКИЙ)
1. Установка Laravel 10 + Breeze
2. Создание всех миграций
3. Создание моделей со связями
4. Seeders для импорта данных
5. Middleware для админки

### Фаза 2: Публичная часть (приоритет: ВЫСОКИЙ)
1. Layout и базовые стили (Tailwind CDN)
2. Дашборд ученика
3. Диагностика (полный цикл)
4. Список модулей
5. Практика (решение задач с проверкой)
6. Теория (просмотр)

### Фаза 3: Модули и тесты (приоритет: ВЫСОКИЙ)
1. Логика разблокировки модулей
2. Тест модуля
3. Внезапные повторения (surprise review)
4. Прогресс пользователя

### Фаза 4: Симуляции (приоритет: СРЕДНИЙ)
1. Список симуляций
2. Прохождение симуляции
3. Результаты и работа над ошибками

### Фаза 5: Админка (приоритет: ВЫСОКИЙ)
1. CRUD задач с WYSIWYG
2. CRUD тем/подтем
3. Редактирование теории
4. Управление модулями
5. Настройки диагностики и симуляций
6. Пользователи и статистика

### Фаза 6: Полировка (приоритет: СРЕДНИЙ)
1. Геймификация (стрики, достижения)
2. Мобильная адаптация
3. Оптимизация производительности
4. Тестирование безопасности

---

## 11. ВАЖНЫЕ ДЕТАЛИ РЕАЛИЗАЦИИ

### Проверка ответа
```php
// app/Services/AnswerCheckerService.php
public function check(Task $task, string $userAnswer): array
{
    $correct = $task->correct_answer;
    $normalized = $this->normalize($userAnswer, $task->answer_type);
    $normalizedCorrect = $this->normalize($correct, $task->answer_type);
    
    $isCorrect = $normalized === $normalizedCorrect;
    
    $result = [
        'is_correct' => $isCorrect,
        'correct_answer' => $correct,
    ];
    
    if (!$isCorrect) {
        // Ищем объяснение типичной ошибки
        $commonError = $task->commonErrors()
            ->where('wrong_answer', $normalized)
            ->first();
        
        if ($commonError) {
            $result['error_explanation'] = $commonError->explanation;
            $result['indicates_subtopic'] = $commonError->indicates_subtopic_id;
        }
        
        $result['solution'] = $task->solution_steps;
    }
    
    return $result;
}

private function normalize(string $answer, string $type): string
{
    $answer = mb_strtolower(trim($answer));
    $answer = str_replace(',', '.', $answer);
    $answer = preg_replace('/\s+/', '', $answer);
    
    if ($type === 'number' && is_numeric($answer)) {
        $answer = (string) floatval($answer);
    }
    
    return $answer;
}
```

### Выбор следующей задачи
```php
// app/Services/TaskSelectorService.php
public function getNextTask(User $user, Module $module): ?Task
{
    $surpriseChance = Setting::getValue('surprise_review_chance', 20);
    
    // Внезапное повторение
    if (rand(1, 100) <= $surpriseChance) {
        $error = UserError::where('user_id', $user->id)
            ->where('resolved', false)
            ->inRandomOrder()
            ->first();
        
        if ($error) {
            return $error->task;
        }
    }
    
    // Задача из текущего модуля
    $subtopicIds = $module->subtopics->pluck('id');
    
    // Находим подтему с наименьшим прогрессом
    $weakestSubtopic = UserKnowledge::where('user_id', $user->id)
        ->whereIn('subtopic_id', $subtopicIds)
        ->orderByRaw('tasks_correct / GREATEST(tasks_attempted, 1) ASC')
        ->first();
    
    $targetSubtopicId = $weakestSubtopic?->subtopic_id 
        ?? $subtopicIds->first();
    
    // Задача, которую не решал
    $solvedIds = Attempt::where('user_id', $user->id)
        ->where('is_correct', true)
        ->pluck('task_id');
    
    return Task::where('subtopic_id', $targetSubtopicId)
        ->where('is_active', true)
        ->whereNotIn('id', $solvedIds)
        ->inRandomOrder()
        ->first();
}
```

### Alpine.js компонент для задачи
```html
<div x-data="taskComponent()" class="max-w-2xl mx-auto">
    <!-- Задача -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="prose">{!! $task->text_html !!}</div>
        @if($task->image_path)
            <img src="{{ asset($task->image_path) }}" class="mt-4">
        @endif
    </div>
    
    <!-- Подсказки -->
    <div x-show="hints.length > 0" class="mb-4">
        <template x-for="(hint, index) in shownHints" :key="index">
            <div class="bg-yellow-50 p-3 rounded mb-2" x-text="hint"></div>
        </template>
        <button x-show="shownHints.length < hints.length && !submitted"
                @click="showNextHint()"
                class="text-blue-600 text-sm">
            Показать подсказку (<span x-text="shownHints.length"></span>/<span x-text="hints.length"></span>)
        </button>
    </div>
    
    <!-- Форма -->
    <form @submit.prevent="submit()" class="bg-white rounded-lg shadow p-6">
        <input type="text" x-model="answer" 
               :disabled="submitted"
               class="w-full border rounded p-3 mb-4"
               placeholder="Ваш ответ">
        
        <button x-show="!submitted" type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded">
            Проверить
        </button>
        
        <!-- Результат -->
        <div x-show="submitted" x-cloak>
            <div :class="isCorrect ? 'bg-green-100' : 'bg-red-100'" 
                 class="p-4 rounded mb-4">
                <template x-if="isCorrect">
                    <p class="font-bold text-green-800">✓ Правильно!</p>
                </template>
                <template x-if="!isCorrect">
                    <div class="text-red-800">
                        <p class="font-bold">✗ Неправильно</p>
                        <p>Правильный ответ: <span x-text="correctAnswer"></span></p>
                        <div x-show="errorExplanation" class="mt-2">
                            <p x-text="errorExplanation"></p>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Решение -->
            <div x-show="solution && !isCorrect" class="bg-gray-50 p-4 rounded mb-4">
                <h4 class="font-bold mb-2">Решение:</h4>
                <div x-html="solution"></div>
            </div>
            
            <button @click="nextTask()" 
                    class="w-full bg-blue-600 text-white py-3 rounded">
                Следующая задача →
            </button>
        </div>
    </form>
</div>

<script>
function taskComponent() {
    return {
        answer: '',
        submitted: false,
        isCorrect: false,
        correctAnswer: '',
        errorExplanation: '',
        solution: '',
        hints: @json($task->hints->pluck('hint_text')),
        shownHints: [],
        
        showNextHint() {
            const next = this.hints[this.shownHints.length];
            if (next) this.shownHints.push(next);
        },
        
        async submit() {
            const res = await fetch('/tasks/{{ $task->id }}/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    answer: this.answer,
                    hints_used: this.shownHints.length
                })
            });
            const data = await res.json();
            
            this.isCorrect = data.is_correct;
            this.correctAnswer = data.correct_answer;
            this.errorExplanation = data.error_explanation || '';
            this.solution = data.solution || '';
            this.submitted = true;
        },
        
        nextTask() {
            window.location.href = '{{ route("modules.practice", $module) }}';
        }
    }
}
</script>
```

---

## 12. КОНТРОЛЬНЫЕ ТОЧКИ

После каждой фазы проверить:

### Фаза 1
- [ ] Все миграции выполняются без ошибок
- [ ] Модели имеют правильные связи
- [ ] Данные импортируются из JSON
- [ ] Админ-пользователь создаётся

### Фаза 2
- [ ] Регистрация и вход работают
- [ ] Диагностика проходит полный цикл
- [ ] Уровень сохраняется в user.diagnostic_level
- [ ] Задачи показываются с картинками

### Фаза 3
- [ ] Модули блокируются/разблокируются корректно
- [ ] Тест модуля работает
- [ ] Внезапные повторения появляются
- [ ] Прогресс обновляется после каждой задачи

### Фаза 4
- [ ] Симуляции доступны согласно настройкам
- [ ] Таймер работает (если настроен)
- [ ] Результаты сохраняются

### Фаза 5
- [ ] Все CRUD операции работают
- [ ] WYSIWYG редактор сохраняет HTML
- [ ] Картинки загружаются
- [ ] Настройки применяются

---

## 13. ОГРАНИЧЕНИЯ И ПРИМЕЧАНИЯ

1. **Не использовать npm/Node.js** — все JS через CDN
2. **Tailwind только через CDN** — без компиляции
3. **Картинки хранить в public/images/tasks/** — не в storage
4. **Сессии в базе данных** — не в файлах (для shared-хостинга)
5. **Очереди не использовать** — всё синхронно

---

## 14. ФАЙЛЫ ПРОЕКТА

К этому ТЗ прилагаются:
1. `parsed_full.json` — распарсенные задачи (5660 шт)
2. `images/` — картинки (788 шт)
3. `oge_db_schema_v3.md` — детальная схема БД
4. `generate_content.py` — скрипт генерации контента через Claude API

После запуска `generate_content.py` появятся:
5. `enriched_tasks.json` — задачи с ответами и подсказками
6. `theory_blocks.json` — блоки теории

---

**Готово к разработке!**
