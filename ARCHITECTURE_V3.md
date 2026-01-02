# PALOMATIKA — Архитектура v3

## Обзор проекта

**PALOMATIKA** — EdTech SaaS платформа для подготовки к ОГЭ по математике.

### Ключевые отличия от конкурентов

| Аспект | Конкуренты | PALOMATIKA |
|--------|------------|------------|
| Формат | Тесты "введи ответ" | **Пазлы** — собери решение |
| Диагностика | "Ты ошибся" | "Ты ошибся, потому что не понимаешь степени" |
| Адаптивность | Слабая | **Глубокая** — на уровне базовых навыков |
| Партнёрка | Нет | **30%** репетиторам с подписок |
| Цена | 990–22000 ₽/мес | **399–1199 ₽/мес** |

### Целевая аудитория

1. **Ученики 8-9 классов** — самоподготовка к ОГЭ
2. **Репетиторы/учителя** — ДЗ для учеников + партнёрка
3. **Родители** — контроль прогресса ребёнка

---

## Бизнес-модель

### Монетизация

```
┌─────────────────────────────────────────────────────────────┐
│  ТАРИФЫ (с эффектом приманки)                              │
├─────────────────┬───────────────────────┬───────────────────┤
│     СТАРТ       │  ⭐ СТАНДАРТ (цель)   │     ПРЕМИУМ       │
│    399 ₽/мес    │      799 ₽/мес        │    1 199 ₽/мес    │
├─────────────────┼───────────────────────┼───────────────────┤
│ ✓ Диагностика   │ ✓ Диагностика         │ ✓ Диагностика     │
│ ✓ 10 задач/день │ ✓ Безлимит            │ ✓ Безлимит        │
│ ✗ Статистика    │ ✓ Полная статистика   │ ✓ Полная стат.    │
│ ✗ Лиги          │ ✓ Лиги и дуэли        │ ✓ Лиги и дуэли    │
│ ✗ AI-помощник   │ ✗ AI-помощник         │ ✓ AI-помощник     │
└─────────────────┴───────────────────────┴───────────────────┘

Пакеты "До ОГЭ" (единоразово):
├── СТАРТ до ОГЭ:    1 590 ₽ (экономия 20%)
├── СТАНДАРТ до ОГЭ: 2 990 ₽ (экономия 25%)
└── ПРЕМИУМ до ОГЭ:  4 490 ₽ (экономия 25%)

Дополнительно:
└── AI-помощник: +299 ₽/мес к любому тарифу
```

### Партнёрская программа для репетиторов

```
Репетитор регистрируется (через заявку + одобрение)
       ↓
Получает реферальную ссылку
       ↓
Приглашает учеников
       ↓
Ученик оформляет подписку
       ↓
Репетитор получает 30% (настраиваемо)
       ↓
Вывод на карту через Robokassa
```

### Платёжная система

- **Robokassa** (для самозанятых)
- Автоматические чеки в ФНС
- Рекуррентные платежи (подписки)
- Комиссия: ~3.5-5%

---

## Технологический стек

### Backend
- **PHP 8.1+**
- **Laravel 10 LTS**
- **Eloquent ORM**
- **Laravel Breeze** (базовая аутентификация)
- **Laravel Socialite** (OAuth: Telegram, VK)

### Frontend
- **Blade Templates**
- **Alpine.js** (интерактивность пазлов)
- **Tailwind CSS** (через CDN)
- **KaTeX** (формулы)

### База данных
- **MySQL / MariaDB**

### Хостинг
- **Timeweb** (shared hosting)
- **Apache + .htaccess**
- **HTTPS** (Let's Encrypt)
- Автодеплой через GitHub Actions + FTP

---

## Ключевые концепции

### 1. Система весов навыков

Каждый ученик имеет "профиль знаний" — веса по базовым навыкам.

```
Ученик: Петя
├── Дроби
│   ├── Сложение дробей:        0.8 ████████░░
│   ├── Общий знаменатель:      0.6 ██████░░░░
│   ├── Умножение дробей:       0.9 █████████░
│   └── Смешанные → неправильные: 0.3 ███░░░░░░░  ← слабое место!
├── Степени
│   ├── Возведение в степень:   0.4 ████░░░░░░  ← слабое место!
│   └── Свойства степеней:      0.5 █████░░░░░
└── Геометрия
    ├── Теорема Пифагора:       0.7 ███████░░░
    └── ...
```

**Формула обновления веса (ELO-подобная):**
```
новый_вес = старый_вес + K × (результат - ожидание)

где:
- результат = 1 (правильно) или 0 (неправильно)
- ожидание = текущий_вес
- K = 0.1-0.3 (выше для сложных задач)
```

**Затухание:**
```
вес = вес × 0.995 (ежедневно, если не практиковался)
```

### 2. Пазлы вместо тестов

Задача разбита на **шаги**, каждый шаг — **пазл с блоками**.

```
ЗАДАЧА: В прямоугольном треугольнике катеты 3 и 4. Найди гипотенузу.

ШАГ 1: Запиши теорему Пифагора
┌─────────────────────────────────────────┐
│  c² = [____] + [____]                   │
└─────────────────────────────────────────┘
Блоки: [a²] [b²] [a] [b] [a+b] [2ab]
        ✓    ✓    ✗   ✗    ✗     ✗
                 └── ловушки ──┘

Выбрал [a+b]? → Минус к навыку "Теорема Пифагора"

ШАГ 2: Подставь числа (видно решение шага 1)
┌─────────────────────────────────────────┐
│  c² = 3² + 4² = [____]                  │
│  ─────────────────────                  │
│  Решение: c² = a² + b²                  │
└─────────────────────────────────────────┘
Блоки: [25] [9+16] [7] [12] [49]

Выбрал [7]? → Минус к навыку "Возведение в степень"
```

### 3. Геймификация

#### Стрики (серии)
- +14% retention (по данным Duolingo)
- Пуш-уведомления при угрозе потери

#### Бейджи
- +116% рефералов (по данным Duolingo)
- Шаринг в соцсетях

#### Лиги
- Еженедельные соревнования
- Топ-3 повышаются, последние 3 понижаются
- Железо → Бронза → Серебро → Золото → Платина → Алмаз → Рубин → Легенда

#### Дуэли
- "Вызов другу" на конкретную тему
- 5 задач, кто быстрее и точнее
- Шаринг результата

#### Командные челленджи
- Класс vs класс
- Школа vs школа

---

## Схема базы данных

### Пользователи и роли

```sql
-- Пользователи
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Аутентификация
    email VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NULL, -- NULL если OAuth
    remember_token VARCHAR(100) NULL,

    -- OAuth
    oauth_provider ENUM('telegram', 'vk', 'yandex') NULL,
    oauth_id VARCHAR(255) NULL,

    -- Профиль
    name VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,

    -- Роль
    role ENUM('student', 'teacher', 'admin') DEFAULT 'student',

    -- Для учеников
    grade TINYINT NULL, -- 8, 9
    school VARCHAR(255) NULL,

    -- Для учителей (партнёров)
    referral_code VARCHAR(50) UNIQUE NULL,
    referred_by_user_id BIGINT UNSIGNED NULL,
    partner_commission_percent TINYINT DEFAULT 30,
    partner_status ENUM('pending', 'approved', 'rejected') NULL,
    partner_approved_at TIMESTAMP NULL,

    -- Подписка
    subscription_plan ENUM('free', 'start', 'standard', 'premium') DEFAULT 'free',
    subscription_ends_at TIMESTAMP NULL,
    has_ai_addon BOOLEAN DEFAULT FALSE,
    trial_ends_at TIMESTAMP NULL,

    -- Активность
    last_active_at TIMESTAMP NULL,
    timezone VARCHAR(50) DEFAULT 'Europe/Moscow',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_referral_code (referral_code),
    INDEX idx_referred_by (referred_by_user_id),
    INDEX idx_role (role),
    FOREIGN KEY (referred_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### Навыки и веса

```sql
-- Базовые навыки (иерархия)
CREATE TABLE skills (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id BIGINT UNSIGNED NULL,

    name VARCHAR(255) NOT NULL, -- "Сложение дробей"
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,

    -- Категория для группировки
    category VARCHAR(100) NULL, -- "Алгебра", "Геометрия"

    -- Связь с номерами ОГЭ
    oge_numbers JSON NULL, -- ["06", "07", "12"]

    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_parent (parent_id),
    INDEX idx_category (category),
    FOREIGN KEY (parent_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Зависимости между навыками (пререквизиты)
CREATE TABLE skill_dependencies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    skill_id BIGINT UNSIGNED NOT NULL,
    requires_skill_id BIGINT UNSIGNED NOT NULL,

    -- Минимальный вес пререквизита для изучения
    min_weight DECIMAL(3,2) DEFAULT 0.5,

    UNIQUE KEY unique_dependency (skill_id, requires_skill_id),
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    FOREIGN KEY (requires_skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Веса навыков пользователя
CREATE TABLE user_skills (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,

    weight DECIMAL(4,3) DEFAULT 0.000, -- 0.000 - 1.000

    -- Статистика
    attempts_count INT UNSIGNED DEFAULT 0,
    correct_count INT UNSIGNED DEFAULT 0,

    last_practiced_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_skill (user_id, skill_id),
    INDEX idx_weight (user_id, weight),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);
```

### Задачи и пазлы

```sql
-- Темы (номера ОГЭ)
CREATE TABLE topics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    oge_number VARCHAR(10) NOT NULL, -- '01-05', '06'...'25'
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,

    -- Теория (опционально)
    theory_content LONGTEXT NULL,

    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Задачи
CREATE TABLE tasks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    topic_id BIGINT UNSIGNED NOT NULL,

    -- Идентификация
    external_id VARCHAR(100) NULL, -- ID из исходного JSON

    -- Контент
    text TEXT NOT NULL,
    text_html TEXT NULL, -- с KaTeX формулами
    image_path VARCHAR(255) NULL,

    -- Классификация
    subtopic VARCHAR(255) NULL,
    difficulty TINYINT DEFAULT 1, -- 1-5

    -- Правильный ответ (для проверки)
    correct_answer VARCHAR(255) NULL,
    answer_type ENUM('number', 'text', 'sequence') DEFAULT 'number',

    -- Шаблон пазла
    puzzle_template_id BIGINT UNSIGNED NULL,

    -- Статистика
    times_shown INT UNSIGNED DEFAULT 0,
    times_correct INT UNSIGNED DEFAULT 0,
    avg_time_seconds INT UNSIGNED NULL,

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_topic (topic_id),
    INDEX idx_difficulty (difficulty),
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);

-- Связь задач с навыками
CREATE TABLE task_skills (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,

    -- Вес влияния (насколько задача проверяет этот навык)
    relevance DECIMAL(3,2) DEFAULT 1.00,

    UNIQUE KEY unique_task_skill (task_id, skill_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Шаблоны пазлов (для типовых задач)
CREATE TABLE puzzle_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    -- JSON структура шагов
    steps_json JSON NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Шаги пазла для конкретной задачи
CREATE TABLE task_steps (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,

    step_number TINYINT NOT NULL,
    instruction TEXT NOT NULL, -- "Запиши теорему Пифагора"

    -- Формат: c² = [___] + [___]
    template TEXT NOT NULL,

    -- Правильные ответы для пропусков
    correct_answers JSON NOT NULL, -- ["a²", "b²"]

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_task_step (task_id, step_number),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Блоки пазла (варианты ответов)
CREATE TABLE step_blocks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_step_id BIGINT UNSIGNED NOT NULL,

    content VARCHAR(255) NOT NULL, -- "a²", "b²", "a+b"
    content_html VARCHAR(255) NULL, -- с KaTeX

    is_correct BOOLEAN NOT NULL,
    is_trap BOOLEAN DEFAULT FALSE, -- ловушка (типичная ошибка)

    -- Какой навык тестирует этот блок
    skill_id BIGINT UNSIGNED NULL,

    -- Объяснение почему неправильно (для ловушек)
    trap_explanation TEXT NULL,

    sort_order INT DEFAULT 0,

    FOREIGN KEY (task_step_id) REFERENCES task_steps(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE SET NULL
);
```

### Попытки и аналитика

```sql
-- Попытки решения задачи
CREATE TABLE attempts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NOT NULL,

    -- Контекст
    session_id CHAR(36) NULL, -- UUID сессии
    source ENUM('practice', 'diagnostic', 'homework', 'duel', 'challenge') DEFAULT 'practice',
    homework_id BIGINT UNSIGNED NULL,
    duel_id BIGINT UNSIGNED NULL,

    -- Результат
    is_completed BOOLEAN DEFAULT FALSE,
    is_correct BOOLEAN NULL,

    -- Время
    started_at TIMESTAMP NOT NULL,
    finished_at TIMESTAMP NULL,
    time_spent_seconds INT UNSIGNED NULL,

    -- XP заработано
    xp_earned INT UNSIGNED DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_date (user_id, created_at),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Шаги попытки (детальная аналитика)
CREATE TABLE attempt_steps (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attempt_id BIGINT UNSIGNED NOT NULL,
    task_step_id BIGINT UNSIGNED NOT NULL,

    step_number TINYINT NOT NULL,
    is_correct BOOLEAN NOT NULL,

    started_at TIMESTAMP NOT NULL,
    finished_at TIMESTAMP NULL,
    time_spent_seconds INT UNSIGNED NULL,

    -- Количество попыток на этом шаге
    attempts_count TINYINT DEFAULT 1,

    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (task_step_id) REFERENCES task_steps(id) ON DELETE CASCADE
);

-- Выбранные блоки (максимальная детализация)
CREATE TABLE step_block_selections (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attempt_step_id BIGINT UNSIGNED NOT NULL,
    step_block_id BIGINT UNSIGNED NOT NULL,

    position TINYINT NOT NULL, -- позиция в шаблоне
    is_correct BOOLEAN NOT NULL,
    selected_at TIMESTAMP NOT NULL,

    -- Какой навык был затронут
    skill_id BIGINT UNSIGNED NULL,

    FOREIGN KEY (attempt_step_id) REFERENCES attempt_steps(id) ON DELETE CASCADE,
    FOREIGN KEY (step_block_id) REFERENCES step_blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE SET NULL
);
```

### Геймификация

```sql
-- Стрики
CREATE TABLE user_streaks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,

    current_streak INT UNSIGNED DEFAULT 0,
    longest_streak INT UNSIGNED DEFAULT 0,

    last_activity_date DATE NULL,
    streak_protected_until DATE NULL, -- "заморозка" стрика

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Бейджи (справочник)
CREATE TABLE badges (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(255) NULL, -- путь к иконке или эмодзи

    -- Условие получения
    condition_type ENUM('streak', 'tasks', 'skill', 'league', 'duel', 'referral', 'special') NOT NULL,
    condition_value INT NULL, -- например: 7 дней стрика
    condition_json JSON NULL, -- сложные условия

    -- Редкость
    rarity ENUM('common', 'rare', 'epic', 'legendary') DEFAULT 'common',

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Полученные бейджи
CREATE TABLE user_badges (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,

    earned_at TIMESTAMP NOT NULL,
    is_showcased BOOLEAN DEFAULT FALSE, -- показывать в профиле

    UNIQUE KEY unique_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

-- Лиги
CREATE TABLE leagues (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    slug VARCHAR(50) UNIQUE NOT NULL, -- 'bronze', 'silver', etc.
    name VARCHAR(100) NOT NULL,
    level TINYINT NOT NULL, -- 1-8
    icon VARCHAR(255) NULL,
    color VARCHAR(20) NULL, -- hex цвет

    -- Правила
    promote_top INT DEFAULT 3, -- топ N повышаются
    demote_bottom INT DEFAULT 3, -- последние N понижаются

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Участие в лигах (еженедельно)
CREATE TABLE league_participants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    league_id BIGINT UNSIGNED NOT NULL,

    -- Период (понедельник-воскресенье)
    week_start DATE NOT NULL,

    xp_earned INT UNSIGNED DEFAULT 0,
    rank_position INT UNSIGNED NULL, -- место по итогам недели

    -- Результат
    result ENUM('promoted', 'stayed', 'demoted') NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_week (user_id, week_start),
    INDEX idx_league_week (league_id, week_start, xp_earned DESC),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (league_id) REFERENCES leagues(id) ON DELETE CASCADE
);

-- Дуэли
CREATE TABLE duels (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    challenger_id BIGINT UNSIGNED NOT NULL,
    opponent_id BIGINT UNSIGNED NULL, -- NULL если ещё не принято

    -- Настройки
    topic_id BIGINT UNSIGNED NULL, -- конкретная тема или NULL = случайная
    tasks_count TINYINT DEFAULT 5,

    -- Статус
    status ENUM('pending', 'active', 'completed', 'cancelled', 'expired') DEFAULT 'pending',

    -- Результаты
    challenger_correct INT UNSIGNED NULL,
    challenger_time_seconds INT UNSIGNED NULL,
    opponent_correct INT UNSIGNED NULL,
    opponent_time_seconds INT UNSIGNED NULL,

    winner_id BIGINT UNSIGNED NULL,

    -- Даты
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL, -- когда приглашение истечёт

    INDEX idx_challenger (challenger_id),
    INDEX idx_opponent (opponent_id),
    INDEX idx_status (status),
    FOREIGN KEY (challenger_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opponent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL,
    FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Задачи дуэли
CREATE TABLE duel_tasks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    duel_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NOT NULL,
    task_order TINYINT NOT NULL,

    FOREIGN KEY (duel_id) REFERENCES duels(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Командные челленджи
CREATE TABLE challenges (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    -- Тип
    challenge_type ENUM('class_vs_class', 'school_vs_school', 'marathon') NOT NULL,

    -- Период
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,

    -- Настройки
    topic_id BIGINT UNSIGNED NULL, -- ограничение по теме
    min_participants INT DEFAULT 3,

    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',

    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Команды челленджа
CREATE TABLE challenge_teams (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    challenge_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,

    total_xp INT UNSIGNED DEFAULT 0,
    rank_position INT UNSIGNED NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
);

-- Участники команды
CREATE TABLE challenge_team_members (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,

    xp_contributed INT UNSIGNED DEFAULT 0,

    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_team_user (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES challenge_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Домашние задания

```sql
-- Домашние задания
CREATE TABLE homeworks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    teacher_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,

    -- Тип
    homework_type ENUM('specific_tasks', 'topic_random', 'weak_skills') NOT NULL,

    -- Настройки
    topic_id BIGINT UNSIGNED NULL,
    tasks_count INT NULL, -- для random

    -- Сроки
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL
);

-- Конкретные задачи ДЗ (если specific_tasks)
CREATE TABLE homework_tasks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    homework_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NOT NULL,
    task_order INT DEFAULT 0,

    FOREIGN KEY (homework_id) REFERENCES homeworks(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Назначение ДЗ ученикам
CREATE TABLE homework_assignments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    homework_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,

    -- Прогресс
    status ENUM('assigned', 'started', 'completed') DEFAULT 'assigned',
    tasks_total INT UNSIGNED DEFAULT 0,
    tasks_completed INT UNSIGNED DEFAULT 0,
    tasks_correct INT UNSIGNED DEFAULT 0,

    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_hw_student (homework_id, student_id),
    FOREIGN KEY (homework_id) REFERENCES homeworks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Подписки и платежи

```sql
-- История подписок
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,

    plan ENUM('start', 'standard', 'premium') NOT NULL,
    plan_period ENUM('monthly', 'until_oge') NOT NULL,
    has_ai_addon BOOLEAN DEFAULT FALSE,

    -- Суммы
    amount INT NOT NULL, -- в копейках
    teacher_commission INT DEFAULT 0, -- комиссия учителю

    -- Реферал
    referred_by_user_id BIGINT UNSIGNED NULL,

    -- Даты
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,

    -- Статус
    status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
    cancelled_at TIMESTAMP NULL,

    -- Платёжка
    payment_provider VARCHAR(50) DEFAULT 'robokassa',
    payment_id VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_status (user_id, status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Выплаты учителям
CREATE TABLE teacher_payouts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,

    amount INT NOT NULL, -- в копейках

    -- Статус
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',

    -- Детали
    payment_method VARCHAR(50) NULL,
    payment_details JSON NULL,

    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,

    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Детализация выплаты (какие подписки включены)
CREATE TABLE payout_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payout_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NOT NULL,

    amount INT NOT NULL, -- комиссия с этой подписки

    FOREIGN KEY (payout_id) REFERENCES teacher_payouts(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);
```

### Логирование активности

```sql
-- Полный лог действий пользователя
CREATE TABLE activity_log (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL, -- NULL для анонимных

    event_type ENUM(
        'login', 'logout',
        'page_view',
        'task_started', 'task_completed',
        'step_started', 'block_selected',
        'duel_created', 'duel_accepted', 'duel_completed',
        'homework_started', 'homework_completed',
        'subscription_created', 'subscription_cancelled',
        'idle_detected', 'tab_hidden', 'tab_visible'
    ) NOT NULL,

    -- Контекст
    page_url VARCHAR(500) NULL,
    task_id BIGINT UNSIGNED NULL,
    metadata JSON NULL, -- дополнительные данные

    -- Устройство
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    device_type ENUM('desktop', 'mobile', 'tablet') NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_date (user_id, created_at),
    INDEX idx_event_type (event_type, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Ежедневная статистика пользователя (агрегация)
CREATE TABLE user_daily_stats (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,

    -- Время
    online_seconds INT UNSIGNED DEFAULT 0,
    active_seconds INT UNSIGNED DEFAULT 0, -- исключая idle

    -- Задачи
    tasks_started INT UNSIGNED DEFAULT 0,
    tasks_completed INT UNSIGNED DEFAULT 0,
    tasks_correct INT UNSIGNED DEFAULT 0,

    -- XP
    xp_earned INT UNSIGNED DEFAULT 0,

    -- Сессии
    sessions_count INT UNSIGNED DEFAULT 0,
    first_activity_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,

    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Рефералы и партнёрка

```sql
-- Клики по реферальным ссылкам
CREATE TABLE referral_clicks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    referral_code VARCHAR(50) NOT NULL,

    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,

    -- Конверсия
    registered_user_id BIGINT UNSIGNED NULL,

    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_referral_code (referral_code),
    INDEX idx_date (clicked_at),
    FOREIGN KEY (registered_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Связь учитель-ученик
CREATE TABLE teacher_students (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,

    -- Как попал
    source ENUM('referral', 'manual', 'homework_invite') DEFAULT 'referral',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_pair (teacher_id, student_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Структура Laravel проекта

```
palomatika/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   └── SocialAuthController.php (OAuth)
│   │   │   ├── PracticeController.php
│   │   │   ├── DiagnosticController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── LeaderboardController.php
│   │   │   ├── DuelController.php
│   │   │   ├── SubscriptionController.php
│   │   │   ├── Teacher/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── StudentsController.php
│   │   │   │   ├── HomeworkController.php
│   │   │   │   └── PayoutController.php
│   │   │   ├── Admin/
│   │   │   │   ├── TasksController.php
│   │   │   │   ├── UsersController.php
│   │   │   │   ├── PartnersController.php
│   │   │   │   └── AnalyticsController.php
│   │   │   └── Api/
│   │   │       ├── PuzzleController.php (AJAX)
│   │   │       └── ActivityController.php (логирование)
│   │   └── Middleware/
│   │       ├── CheckSubscription.php
│   │       ├── TeacherMiddleware.php
│   │       ├── AdminMiddleware.php
│   │       └── LogActivity.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Skill.php
│   │   ├── UserSkill.php
│   │   ├── Topic.php
│   │   ├── Task.php
│   │   ├── TaskStep.php
│   │   ├── StepBlock.php
│   │   ├── Attempt.php
│   │   ├── AttemptStep.php
│   │   ├── Badge.php
│   │   ├── UserBadge.php
│   │   ├── League.php
│   │   ├── LeagueParticipant.php
│   │   ├── Duel.php
│   │   ├── Homework.php
│   │   ├── Subscription.php
│   │   └── ActivityLog.php
│   ├── Services/
│   │   ├── SkillWeightService.php      # Обновление весов
│   │   ├── TaskSelectorService.php     # Подбор задач
│   │   ├── DiagnosticService.php       # Диагностика
│   │   ├── PuzzleService.php           # Логика пазлов
│   │   ├── GamificationService.php     # Стрики, бейджи, XP
│   │   ├── LeagueService.php           # Лиги
│   │   ├── DuelService.php             # Дуэли
│   │   ├── SubscriptionService.php     # Подписки
│   │   ├── PayoutService.php           # Выплаты учителям
│   │   └── ActivityLogService.php      # Логирование
│   ├── Jobs/
│   │   ├── ProcessWeeklyLeagues.php
│   │   ├── DecaySkillWeights.php
│   │   └── SendStreakReminder.php
│   └── Notifications/
│       ├── StreakAtRisk.php
│       ├── HomeworkAssigned.php
│       └── DuelChallenge.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php
│       │   └── teacher.blade.php
│       ├── auth/
│       ├── landing.blade.php
│       ├── practice/
│       │   ├── index.blade.php
│       │   └── puzzle.blade.php
│       ├── profile/
│       ├── leaderboard/
│       ├── duel/
│       ├── teacher/
│       └── admin/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── channels.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── SkillSeeder.php
│       ├── TopicSeeder.php
│       ├── TaskSeeder.php
│       ├── BadgeSeeder.php
│       └── LeagueSeeder.php
└── public/
    ├── images/
    │   └── tasks/
    └── .htaccess
```

---

## API маршруты

```php
// routes/web.php

// Публичные
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/ref/{code}', [ReferralController::class, 'track'])->name('referral.track');

// OAuth
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback']);

// Авторизованные
Route::middleware(['auth'])->group(function () {

    // Дашборд
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Практика
    Route::prefix('practice')->name('practice.')->group(function () {
        Route::get('/', [PracticeController::class, 'index'])->name('index');
        Route::get('/topic/{topic}', [PracticeController::class, 'topic'])->name('topic');
        Route::get('/task/{task}', [PracticeController::class, 'show'])->name('show');
    });

    // Диагностика
    Route::get('/diagnostic', [DiagnosticController::class, 'start'])->name('diagnostic.start');
    Route::get('/diagnostic/task', [DiagnosticController::class, 'task'])->name('diagnostic.task');

    // Профиль
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/skills', [ProfileController::class, 'skills'])->name('profile.skills');
    Route::get('/profile/badges', [ProfileController::class, 'badges'])->name('profile.badges');

    // Лидерборд
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

    // Дуэли
    Route::prefix('duel')->name('duel.')->group(function () {
        Route::get('/', [DuelController::class, 'index'])->name('index');
        Route::post('/create', [DuelController::class, 'create'])->name('create');
        Route::get('/{duel}', [DuelController::class, 'show'])->name('show');
        Route::post('/{duel}/accept', [DuelController::class, 'accept'])->name('accept');
    });

    // Подписка
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::post('/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::get('/success', [SubscriptionController::class, 'success'])->name('success');
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    });
});

// Учитель
Route::middleware(['auth', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [Teacher\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/students', [Teacher\StudentsController::class, 'index'])->name('students');
    Route::get('/students/{student}', [Teacher\StudentsController::class, 'show'])->name('students.show');
    Route::resource('homework', Teacher\HomeworkController::class);
    Route::get('/earnings', [Teacher\PayoutController::class, 'index'])->name('earnings');
    Route::post('/payout', [Teacher\PayoutController::class, 'request'])->name('payout.request');
});

// Админ
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('tasks', Admin\TasksController::class);
    Route::resource('users', Admin\UsersController::class);
    Route::get('/partners', [Admin\PartnersController::class, 'index'])->name('partners');
    Route::post('/partners/{user}/approve', [Admin\PartnersController::class, 'approve'])->name('partners.approve');
    Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
});

// routes/api.php

// Пазлы (AJAX)
Route::middleware(['auth'])->prefix('puzzle')->group(function () {
    Route::post('/submit-step', [Api\PuzzleController::class, 'submitStep']);
    Route::post('/complete', [Api\PuzzleController::class, 'complete']);
});

// Логирование активности
Route::middleware(['auth'])->prefix('activity')->group(function () {
    Route::post('/log', [Api\ActivityController::class, 'log']);
    Route::post('/heartbeat', [Api\ActivityController::class, 'heartbeat']);
});

// Webhook Robokassa
Route::post('/webhook/robokassa', [WebhookController::class, 'robokassa']);
```

---

## Ключевые сервисы

### SkillWeightService.php

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use App\Models\UserSkill;
use App\Models\StepBlockSelection;

class SkillWeightService
{
    // Коэффициент обучения
    private const K_BASE = 0.15;
    private const K_DIFFICULT = 0.25;

    // Затухание (ежедневно)
    private const DECAY_FACTOR = 0.995;

    /**
     * Обновить вес навыка после выбора блока
     */
    public function updateFromBlockSelection(
        User $user,
        StepBlockSelection $selection
    ): void {
        if (!$selection->skill_id) {
            return;
        }

        $userSkill = UserSkill::firstOrCreate([
            'user_id' => $user->id,
            'skill_id' => $selection->skill_id,
        ]);

        $result = $selection->is_correct ? 1 : 0;
        $expected = $userSkill->weight;
        $k = $this->getK($selection->stepBlock->taskStep->task);

        // ELO-подобная формула
        $newWeight = $userSkill->weight + $k * ($result - $expected);

        // Ограничиваем диапазон [0, 1]
        $newWeight = max(0, min(1, $newWeight));

        $userSkill->update([
            'weight' => $newWeight,
            'attempts_count' => $userSkill->attempts_count + 1,
            'correct_count' => $userSkill->correct_count + ($result ? 1 : 0),
            'last_practiced_at' => now(),
        ]);
    }

    /**
     * Применить затухание к неиспользуемым навыкам
     */
    public function applyDecay(User $user): void
    {
        UserSkill::where('user_id', $user->id)
            ->where('last_practiced_at', '<', now()->subDay())
            ->update([
                'weight' => DB::raw('weight * ' . self::DECAY_FACTOR),
            ]);
    }

    /**
     * Получить слабые навыки пользователя
     */
    public function getWeakSkills(User $user, int $limit = 5): Collection
    {
        return UserSkill::where('user_id', $user->id)
            ->where('attempts_count', '>', 0)
            ->orderBy('weight')
            ->limit($limit)
            ->with('skill')
            ->get();
    }

    private function getK(Task $task): float
    {
        return $task->difficulty >= 4
            ? self::K_DIFFICULT
            : self::K_BASE;
    }
}
```

### GamificationService.php

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserStreak;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\LeagueParticipant;

class GamificationService
{
    /**
     * Начислить XP и обновить стрик
     */
    public function recordActivity(User $user, int $xp): void
    {
        // Начислить XP
        $this->addXp($user, $xp);

        // Обновить стрик
        $this->updateStreak($user);

        // Проверить бейджи
        $this->checkBadges($user);

        // Обновить лигу
        $this->updateLeagueXp($user, $xp);
    }

    public function updateStreak(User $user): void
    {
        $streak = UserStreak::firstOrCreate(['user_id' => $user->id]);
        $today = now()->toDateString();

        if ($streak->last_activity_date === $today) {
            return; // Уже обновлён сегодня
        }

        $yesterday = now()->subDay()->toDateString();

        if ($streak->last_activity_date === $yesterday) {
            // Продолжаем стрик
            $streak->current_streak++;
            $streak->longest_streak = max(
                $streak->longest_streak,
                $streak->current_streak
            );
        } else {
            // Стрик прервался (если нет заморозки)
            if (!$streak->streak_protected_until ||
                $streak->streak_protected_until < $today) {
                $streak->current_streak = 1;
            }
        }

        $streak->last_activity_date = $today;
        $streak->save();
    }

    public function checkBadges(User $user): array
    {
        $earnedBadges = [];

        $badges = Badge::where('is_active', true)
            ->whereNotIn('id', function($q) use ($user) {
                $q->select('badge_id')
                    ->from('user_badges')
                    ->where('user_id', $user->id);
            })
            ->get();

        foreach ($badges as $badge) {
            if ($this->checkBadgeCondition($user, $badge)) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);
                $earnedBadges[] = $badge;
            }
        }

        return $earnedBadges;
    }

    private function checkBadgeCondition(User $user, Badge $badge): bool
    {
        return match($badge->condition_type) {
            'streak' => $user->streak?->current_streak >= $badge->condition_value,
            'tasks' => $user->attempts()->where('is_correct', true)->count() >= $badge->condition_value,
            'duel' => $user->duelsWon()->count() >= $badge->condition_value,
            'referral' => $user->referrals()->count() >= $badge->condition_value,
            default => false,
        };
    }

    private function updateLeagueXp(User $user, int $xp): void
    {
        $weekStart = now()->startOfWeek()->toDateString();

        LeagueParticipant::where('user_id', $user->id)
            ->where('week_start', $weekStart)
            ->increment('xp_earned', $xp);
    }
}
```

---

## План разработки по фазам

### Фаза 0: Инфраструктура (1-2 дня)
- [ ] Создать Laravel 10 проект
- [ ] Настроить GitHub репозиторий
- [ ] Настроить автодеплой на Timeweb
- [ ] Базовая конфигурация (.env, база данных)

### Фаза 1: Аутентификация (2-3 дня)
- [ ] Миграции: users
- [ ] Laravel Breeze (email/password)
- [ ] OAuth: Telegram, VK (Laravel Socialite)
- [ ] Регистрация с реферальным кодом
- [ ] Пробный период 7 дней

### Фаза 2: Контент и данные (2-3 дня)
- [ ] Миграции: topics, tasks, skills
- [ ] Импорт задач из JSON
- [ ] Импорт изображений
- [ ] Сидеры для навыков
- [ ] Админка: просмотр задач

### Фаза 3: Базовая практика (3-4 дня)
- [ ] Список тем
- [ ] Страница задачи (без пазлов — простой ввод)
- [ ] Проверка ответа
- [ ] Запись попыток
- [ ] Базовый прогресс

### Фаза 4: Система пазлов (5-7 дней)
- [ ] Миграции: task_steps, step_blocks
- [ ] Создание шаблонов пазлов (для тестовых задач)
- [ ] UI пазла на Alpine.js
- [ ] Пошаговое решение
- [ ] Связь блоков с навыками

### Фаза 5: Система весов (3-4 дня)
- [ ] Миграции: user_skills, skill_dependencies
- [ ] SkillWeightService
- [ ] Обновление весов при решении
- [ ] Страница профиля с весами
- [ ] Затухание весов (cron job)

### Фаза 6: Диагностика (2-3 дня)
- [ ] Адаптивный тест
- [ ] Определение слабых навыков
- [ ] Рекомендации по темам

### Фаза 7: Геймификация — базовая (3-4 дня)
- [ ] Миграции: user_streaks, badges, user_badges
- [ ] Стрики
- [ ] XP система
- [ ] Бейджи (10-15 базовых)
- [ ] Push-уведомления (стрик под угрозой)

### Фаза 8: Лиги (2-3 дня)
- [ ] Миграции: leagues, league_participants
- [ ] Еженедельный цикл лиг
- [ ] Лидерборд
- [ ] Повышение/понижение

### Фаза 9: Дуэли (3-4 дня)
- [ ] Миграции: duels, duel_tasks
- [ ] Создание/принятие дуэли
- [ ] Игровой процесс
- [ ] Результаты и шаринг

### Фаза 10: Подписки и платежи (3-4 дня)
- [ ] Миграции: subscriptions
- [ ] Интеграция Robokassa
- [ ] Тарифы и ограничения
- [ ] Страница выбора тарифа

### Фаза 11: Партнёрка для учителей (3-4 дня)
- [ ] Заявка на партнёрство
- [ ] Админка: одобрение партнёров
- [ ] Реферальные ссылки
- [ ] Комиссии с подписок
- [ ] Панель учителя: статистика

### Фаза 12: Домашние задания (2-3 дня)
- [ ] Миграции: homeworks, homework_assignments
- [ ] Создание ДЗ учителем
- [ ] Назначение ученикам
- [ ] Выполнение и отчёты

### Фаза 13: Логирование и аналитика (2-3 дня)
- [ ] Миграции: activity_log, user_daily_stats
- [ ] Логирование всех действий
- [ ] Панель учителя: детальная история ученика
- [ ] Админка: общая аналитика

### Фаза 14: Командные челленджи (2-3 дня)
- [ ] Миграции: challenges, challenge_teams
- [ ] Создание командного челленджа
- [ ] Класс vs класс

### Фаза 15: Лендинг и маркетинг (2-3 дня)
- [ ] Лендинг по ТЗ
- [ ] SEO оптимизация
- [ ] Telegram-канал
- [ ] Шаринг достижений

### Фаза 16: Полировка и запуск (3-5 дней)
- [ ] Тестирование всех сценариев
- [ ] Исправление багов
- [ ] Оптимизация производительности
- [ ] Мониторинг и алерты
- [ ] Запуск!

---

## Деплой

### GitHub Actions (.github/workflows/deploy-timeweb.yml)

```yaml
name: Deploy to Timeweb

on:
  push:
    branches:
      - main
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout Repository
        uses: actions/checkout@v4

      - name: Deploy to Hosting
        uses: SamKirkland/FTP-Deploy-Action@4.0.0
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USERNAME }}
          password: ${{ secrets.FTP_PASSWORD }}
          server-dir: /home/c/cw95865/OGE/public_html/
```

### Структура на хостинге

```
/home/c/cw95865/OGE/public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/           ← DocumentRoot
│   ├── index.php
│   ├── .htaccess
│   └── images/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
└── artisan
```

### .htaccess (корень)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## Дизайн

### Цветовая схема

```
Основной фон:     #1A1A2E (тёмно-синий)
Вторичный фон:    #16213E (чуть светлее)
Карточки:         #0F3460 (синий)
Акцент:           #E94560 (красный/коралловый)
Текст основной:   #FFFFFF
Текст вторичный:  #A0AEC0
Успех:            #48BB78 (зелёный)
Ошибка:           #F56565 (красный)
```

### Референс UI

Тёмная тема, минималистичный дизайн с красными акцентами.
Вдохновение: приложенный UI-kit (Pomo app style).

---

## Следующие шаги

1. Создать Laravel проект
2. Настроить автодеплой
3. Начать с Фазы 1 (аутентификация)

---

*Документ обновлён: 2 января 2026*
*Версия: 3.0*
