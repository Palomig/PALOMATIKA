# Схема базы данных — Тренажёр ОГЭ v3

## Общая структура

```
users                 # Пользователи
subjects              # Предметы (математика)
topics                # Темы (№15 Треугольники)
subtopics             # Подтемы (биссектриса, медиана)
tasks                 # Задачи
task_hints            # Подсказки к задачам
task_common_errors    # Типичные ошибки
theory_blocks         # Блоки теории
modules               # Модули обучения
module_subtopics      # Связь модуль-подтемы

user_progress         # Прогресс по модулям
user_knowledge        # Знание подтем
user_errors           # История ошибок (для внезапных повторений)
attempts              # Все попытки решения

diagnostic_configs    # Настройки диагностики
simulation_configs    # Настройки симуляций
settings              # Общие настройки
```

---

## Таблицы

### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    
    role ENUM('student', 'admin') DEFAULT 'student',
    
    -- После диагностики
    diagnostic_level ENUM('not_tested', 'basic', 'medium', 'advanced') DEFAULT 'not_tested',
    diagnostic_completed_at TIMESTAMP NULL,
    
    -- Текущий прогресс
    current_module_id BIGINT UNSIGNED NULL,
    
    -- Связь с Эвриум
    evrium_student_id BIGINT UNSIGNED NULL,
    
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_active_at TIMESTAMP NULL
);
```

### subjects
```sql
CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    name VARCHAR(255) NOT NULL,           -- 'Математика'
    slug VARCHAR(255) UNIQUE NOT NULL,    -- 'math'
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### topics (номера ОГЭ)
```sql
CREATE TABLE topics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    
    oge_number VARCHAR(10) NOT NULL,      -- '01-05', '06', '15'
    name VARCHAR(255) NOT NULL,           -- 'Треугольники'
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_oge_number (oge_number)
);
```

### subtopics (типы задач внутри темы)
```sql
CREATE TABLE subtopics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(255) NOT NULL,           -- 'Биссектриса'
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Количество задач для освоения
    tasks_to_master INT DEFAULT 3,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_topic_slug (topic_id, slug)
);
```

### tasks (задачи)
```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NOT NULL,
    subtopic_id BIGINT UNSIGNED NULL,
    
    -- Идентификация
    local_number INT NULL,                -- Номер из PDF
    source_file VARCHAR(255) NULL,        -- Исходный файл
    
    -- Контент (редактируемый в CMS)
    text TEXT NOT NULL,                   -- Текст задачи
    text_html TEXT NULL,                  -- HTML с формулами
    image_path VARCHAR(255) NULL,         -- Путь к картинке
    
    -- Классификация
    block VARCHAR(100) NULL,              -- 'Блок 1. ФИПИ'
    context VARCHAR(100) NULL,            -- Для 01-05: 'Квартира'
    difficulty TINYINT DEFAULT 1,         -- 1-5
    
    -- Ответ
    answer_type ENUM('number', 'text', 'sequence') DEFAULT 'number',
    correct_answer VARCHAR(255) NOT NULL,
    
    -- Решение (редактируемое)
    solution_steps TEXT NULL,             -- Пошаговое решение (HTML)
    solution_video_url VARCHAR(255) NULL, -- Ссылка на видео (опционально)
    
    -- Метаданные
    tags JSON NULL,
    
    -- Статистика
    times_shown INT UNSIGNED DEFAULT 0,
    times_correct INT UNSIGNED DEFAULT 0,
    
    -- Сгенерировано ли через AI
    ai_generated_at TIMESTAMP NULL,
    manually_edited BOOLEAN DEFAULT FALSE,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE SET NULL,
    INDEX idx_topic_difficulty (topic_id, difficulty),
    INDEX idx_subtopic (subtopic_id)
);
```

### task_hints (подсказки)
```sql
CREATE TABLE task_hints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    
    hint_order TINYINT NOT NULL,          -- 1, 2, 3...
    hint_text TEXT NOT NULL,              -- Текст подсказки (HTML)
    
    -- Сгенерировано AI или вручную
    ai_generated BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task_order (task_id, hint_order)
);
```

### task_common_errors (типичные ошибки)
```sql
CREATE TABLE task_common_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    
    wrong_answer VARCHAR(255) NOT NULL,   -- '68'
    explanation TEXT NOT NULL,            -- 'Забыл разделить на 2'
    
    -- Какой пробел в знаниях это показывает
    indicates_subtopic_id BIGINT UNSIGNED NULL,
    
    ai_generated BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (indicates_subtopic_id) REFERENCES subtopics(id) ON DELETE SET NULL,
    INDEX idx_task_answer (task_id, wrong_answer)
);
```

### theory_blocks (теория)
```sql
CREATE TABLE theory_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subtopic_id BIGINT UNSIGNED NOT NULL,
    
    title VARCHAR(255) NOT NULL,          -- 'Что такое биссектриса'
    
    -- Контент (WYSIWYG)
    content_html LONGTEXT NOT NULL,       -- Основной текст
    lifehack_html TEXT NULL,              -- Лайфхак
    
    -- Медиа
    images JSON NULL,                     -- Массив путей к картинкам
    video_url VARCHAR(255) NULL,
    
    -- Пример решения
    example_task_id BIGINT UNSIGNED NULL, -- Ссылка на задачу-пример
    example_solution_html TEXT NULL,      -- Или своё решение
    
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    ai_generated BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE CASCADE,
    FOREIGN KEY (example_task_id) REFERENCES tasks(id) ON DELETE SET NULL
);
```

### modules (модули обучения)
```sql
CREATE TABLE modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(255) NOT NULL,           -- 'Базовая геометрия'
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    
    -- Порядок и доступность
    sort_order INT NOT NULL,
    unlock_after_module_id BIGINT UNSIGNED NULL, -- Предыдущий модуль
    
    -- Настройки финального теста модуля
    test_tasks_per_subtopic TINYINT DEFAULT 1,
    test_pass_threshold TINYINT DEFAULT 100,  -- % правильных для прохождения
    
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (unlock_after_module_id) REFERENCES modules(id) ON DELETE SET NULL
);
```

### module_subtopics (связь модуль-подтемы)
```sql
CREATE TABLE module_subtopics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id BIGINT UNSIGNED NOT NULL,
    subtopic_id BIGINT UNSIGNED NOT NULL,
    
    sort_order INT DEFAULT 0,
    
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_module_subtopic (module_id, subtopic_id)
);
```

---

## Прогресс и статистика

### user_progress (прогресс по модулям)
```sql
CREATE TABLE user_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    
    status ENUM('locked', 'in_progress', 'testing', 'completed') DEFAULT 'locked',
    
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Результаты теста модуля
    test_attempts INT DEFAULT 0,
    last_test_score TINYINT NULL,         -- % правильных
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module_id)
);
```

### user_knowledge (знание подтем)
```sql
CREATE TABLE user_knowledge (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    subtopic_id BIGINT UNSIGNED NOT NULL,
    
    status ENUM('unknown', 'learning', 'weak', 'mastered') DEFAULT 'unknown',
    
    -- Статистика
    tasks_attempted INT DEFAULT 0,
    tasks_correct INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    
    -- Для интервального повторения
    last_practiced_at TIMESTAMP NULL,
    next_review_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_subtopic (user_id, subtopic_id)
);
```

### user_errors (для внезапных повторений)
```sql
CREATE TABLE user_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NOT NULL,
    subtopic_id BIGINT UNSIGNED NULL,
    
    error_count INT DEFAULT 1,            -- Сколько раз ошибся в этой задаче
    last_error_at TIMESTAMP NOT NULL,
    
    -- Была ли задача успешно решена после ошибки
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (subtopic_id) REFERENCES subtopics(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_task (user_id, task_id),
    INDEX idx_user_unresolved (user_id, resolved)
);
```

### attempts (все попытки)
```sql
CREATE TABLE attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NOT NULL,
    
    -- Контекст
    context_type ENUM('diagnostic', 'practice', 'module_test', 'simulation', 'review') NOT NULL,
    context_id BIGINT UNSIGNED NULL,      -- ID диагностики/симуляции если есть
    
    -- Ответ
    user_answer VARCHAR(255) NOT NULL,
    is_correct BOOLEAN NOT NULL,
    
    -- Подсказки
    hints_used TINYINT DEFAULT 0,
    
    -- Время
    time_spent_seconds INT UNSIGNED NULL,
    
    -- Если ошибка — что показали
    shown_explanation TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_context (context_type, context_id)
);
```

---

## Настройки (админка)

### diagnostic_configs
```sql
CREATE TABLE diagnostic_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    
    level ENUM('easy', 'medium', 'hard') NOT NULL,
    
    -- Какие темы включать
    topic_ids JSON NOT NULL,              -- [1, 2, 3]
    
    -- Сколько задач
    tasks_count TINYINT NOT NULL,
    
    -- При скольких ошибках стоп
    max_errors TINYINT DEFAULT 2,
    
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subject_level (subject_id, level)
);
```

### simulation_configs
```sql
CREATE TABLE simulation_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    
    name VARCHAR(255) NOT NULL,           -- 'Полный экзамен'
    slug VARCHAR(255) NOT NULL,
    
    -- Какие темы и сколько задач
    tasks_config JSON NOT NULL,           -- {"15": 2, "16": 1, ...}
    total_tasks INT NOT NULL,
    
    -- Время (в минутах, NULL = без ограничения)
    time_limit_minutes INT NULL,
    
    -- Когда доступна
    unlock_after_modules INT DEFAULT 0,   -- После скольких модулей
    
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);
```

### settings (общие настройки)
```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    `key` VARCHAR(255) UNIQUE NOT NULL,
    `value` TEXT NOT NULL,
    `type` ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    
    description VARCHAR(255) NULL,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Примеры настроек
INSERT INTO settings (`key`, `value`, `type`, description) VALUES
('surprise_review_chance', '20', 'int', 'Шанс внезапного повторения (%)'),
('diagnostic_max_errors_default', '2', 'int', 'Порог ошибок в диагностике по умолчанию'),
('hints_enabled', 'true', 'bool', 'Включены ли подсказки'),
('ai_generation_enabled', 'false', 'bool', 'Генерация контента через AI');
```

---

## Индексы для производительности

```sql
-- Быстрый поиск нерешённых ошибок для повторения
CREATE INDEX idx_user_errors_review ON user_errors(user_id, resolved, last_error_at);

-- Быстрый поиск задач по теме и сложности
CREATE INDEX idx_tasks_selection ON tasks(topic_id, subtopic_id, difficulty, is_active);

-- Статистика по задачам
CREATE INDEX idx_attempts_stats ON attempts(task_id, is_correct);
```

---

## ER-диаграмма (упрощённая)

```
subjects
    └── topics (oge_number)
            └── subtopics
                    ├── tasks
                    │       ├── task_hints
                    │       └── task_common_errors
                    └── theory_blocks

modules
    └── module_subtopics ──→ subtopics

users
    ├── user_progress ──→ modules
    ├── user_knowledge ──→ subtopics  
    ├── user_errors ──→ tasks
    └── attempts ──→ tasks

diagnostic_configs ──→ subjects
simulation_configs ──→ subjects
```
