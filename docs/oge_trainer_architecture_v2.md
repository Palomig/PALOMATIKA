# Тренажёр ОГЭ по математике — Архитектура v2

## Обзор проекта

Платформа для подготовки школьников к ОГЭ по математике (аналог Duolingo):
- База реальных заданий из ФИПИ (3490+ задач)
- Теория + задания + прогресс
- Анализ ошибок и рекомендации
- Возможность ручного редактирования ответов

---

## Ключевые ограничения

| Ограничение | Решение |
|-------------|---------|
| Только PHP | Laravel 10 LTS |
| Shared-хостинг | Без Docker, деплой через FTP |
| Нет Node.js | Tailwind через CDN, Alpine.js |
| Нет root-доступа | Стандартная конфигурация Apache |

---

## Технологический стек

### Backend
- **PHP 8.1+**
- **Laravel 10 LTS** (MVC)
- **Eloquent ORM** (защита от SQL injection)
- **Laravel Breeze** (аутентификация)

### Frontend
- **Blade Templates** (авто-экранирование = защита от XSS)
- **Alpine.js** (минимальный интерактив)
- **Tailwind CSS** (через CDN)
- **KaTeX** (рендеринг формул, CDN)

### База данных
- **MySQL / MariaDB**
- Отдельный пользователь с минимальными правами

### Сервер
- **Apache + .htaccess**
- **HTTPS** (Let's Encrypt)
- PHP extensions: pdo_mysql, openssl, mbstring, tokenizer, xml

---

## Структура базы заданий

### Статистика (из распарсенных PDF)

| Номер ОГЭ | Тема | Задач |
|-----------|------|-------|
| 01-05 | Практические задачи | 648 |
| 06 | Дроби и степени | 31 |
| 07 | Числа, координатная прямая | 76 |
| 08 | Квадратные корни | 10 |
| 09 | Уравнения | 11 |
| 10 | Теория вероятностей | 163 |
| 11 | Графики функций | 18 |
| 12 | Расчёты по формулам | 128 |
| 13 | Неравенства | 115 |
| 14 | Прогрессии | 165 |
| 15 | Треугольники | 306 |
| 16 | Окружность, четырёхугольники | 283 |
| 17 | Площади фигур | 259 |
| 18 | Координатная плоскость | 8 |
| 19 | Теоремы и доказательства | 421 |
| 20 | Задачи на доказательство | 12 |
| 21 | Текстовые задачи | 242 |
| 22 | Графики и параметры | 182 |
| 23 | Геометрические построения | 175 |
| 24 | Подобие, пропорции | 78 |
| 25 | Геометрия (сложные) | 159 |
| **ИТОГО** | | **3490** |

---

## Схема базы данных (Laravel Migrations)

### users
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['student', 'admin'])->default('student');
    
    // Профиль ученика
    $table->tinyInteger('grade')->nullable(); // 8, 9, 10, 11
    $table->string('school')->nullable();
    $table->integer('target_score')->nullable(); // Желаемый балл
    $table->integer('daily_goal')->default(10); // Задач в день
    
    // Связь с Эвриум
    $table->unsignedBigInteger('evrium_student_id')->nullable();
    
    $table->rememberToken();
    $table->timestamps();
    $table->timestamp('last_active_at')->nullable();
});
```

### subjects (Предметы)
```php
Schema::create('subjects', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // 'Математика'
    $table->string('slug')->unique(); // 'math'
    $table->text('description')->nullable();
    $table->string('icon')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### topics (Темы/Номера ОГЭ)
```php
Schema::create('topics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subject_id')->constrained()->onDelete('cascade');
    
    $table->string('oge_number', 10); // '01-05', '06'...'25'
    $table->string('name'); // 'Треугольники'
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    
    // Теоретический материал
    $table->longText('theory_content')->nullable(); // HTML/Markdown
    $table->json('theory_images')->nullable(); // Массив путей к картинкам
    
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index('oge_number');
});
```

### tasks (Задания)
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('topic_id')->constrained()->onDelete('cascade');
    
    // Идентификация
    $table->integer('local_number'); // Номер внутри файла
    $table->string('source_file')->nullable(); // Исходный PDF
    
    // Контент
    $table->text('text'); // Текст задачи
    $table->text('text_html')->nullable(); // HTML с формулами
    $table->text('image_path')->nullable(); // Путь к картинке
    
    // Классификация
    $table->string('subtopic')->nullable(); // 'Биссектриса, медиана'
    $table->string('block')->nullable(); // 'Блок 1. ФИПИ'
    $table->string('context')->nullable(); // Для 01-05: 'Квартира'
    
    // Ответы
    $table->enum('answer_type', ['number', 'text', 'multiple', 'sequence'])
          ->default('number');
    $table->string('answer_generated')->nullable();
    $table->string('answer_verified')->nullable();
    $table->timestamp('answer_verified_at')->nullable();
    $table->string('answer_verified_by')->nullable();
    
    // Дополнительно
    $table->tinyInteger('difficulty')->default(1); // 1-5
    $table->json('tags')->nullable();
    $table->json('hints')->nullable(); // Подсказки
    $table->text('solution')->nullable(); // Подробное решение
    
    // Статистика
    $table->unsignedInteger('times_shown')->default(0);
    $table->unsignedInteger('times_correct')->default(0);
    $table->unsignedInteger('avg_time_seconds')->nullable();
    
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index(['topic_id', 'difficulty']);
});
```

### attempts (Попытки решения)
```php
Schema::create('attempts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('task_id')->constrained()->onDelete('cascade');
    $table->uuid('session_id')->nullable(); // Группировка в сессию
    
    // Ответ
    $table->string('user_answer');
    $table->boolean('is_correct');
    
    // Метрики
    $table->unsignedInteger('time_spent_seconds')->nullable();
    $table->tinyInteger('hints_used')->default(0);
    
    // Анализ ошибки
    $table->string('error_type', 50)->nullable();
    $table->json('error_details')->nullable();
    
    $table->timestamps();
    
    $table->index(['user_id', 'created_at']);
    $table->index('session_id');
});
```

### user_progress (Прогресс по темам)
```php
Schema::create('user_progress', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('topic_id')->constrained()->onDelete('cascade');
    
    // Статистика
    $table->unsignedInteger('tasks_attempted')->default(0);
    $table->unsignedInteger('tasks_correct')->default(0);
    $table->unsignedInteger('current_streak')->default(0);
    $table->unsignedInteger('best_streak')->default(0);
    
    // Уровень освоения (0-100)
    $table->tinyInteger('mastery_level')->default(0);
    
    // Слабые подтемы
    $table->json('weak_subtopics')->nullable();
    
    $table->timestamp('last_practice_at')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'topic_id']);
});
```

### daily_goals (Ежедневные цели)
```php
Schema::create('daily_goals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->date('date');
    
    $table->unsignedInteger('goal')->default(10);
    $table->unsignedInteger('completed')->default(0);
    $table->unsignedInteger('correct')->default(0);
    
    $table->boolean('goal_reached')->default(false);
    
    $table->timestamps();
    
    $table->unique(['user_id', 'date']);
});
```

---

## Структура Laravel проекта

```
oge-trainer/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # Breeze контроллеры
│   │   │   ├── Admin/
│   │   │   │   ├── TaskController.php
│   │   │   │   ├── TopicController.php
│   │   │   │   └── UserController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── TaskController.php
│   │   │   ├── ProgressController.php
│   │   │   └── PracticeController.php
│   │   └── Middleware/
│   │       └── AdminMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Subject.php
│   │   ├── Topic.php
│   │   ├── Task.php
│   │   ├── Attempt.php
│   │   ├── UserProgress.php
│   │   └── DailyGoal.php
│   └── Services/
│       ├── AnswerCheckerService.php
│       ├── ProgressService.php
│       ├── RecommendationService.php
│       └── ErrorAnalyzerService.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       ├── auth/                # Breeze views
│       ├── dashboard.blade.php
│       ├── practice/
│       │   ├── index.blade.php  # Выбор темы
│       │   ├── task.blade.php   # Решение задачи
│       │   └── result.blade.php # Результат
│       ├── progress/
│       │   └── index.blade.php
│       ├── theory/
│       │   └── show.blade.php
│       └── admin/
│           ├── tasks/
│           └── users/
├── routes/
│   ├── web.php
│   └── admin.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── TaskSeeder.php      # Импорт из JSON
└── public/
    ├── images/
    │   └── tasks/              # Картинки заданий
    └── .htaccess
```

---

## Маршруты (routes/web.php)

```php
<?php

use Illuminate\Support\Facades\Route;

// Публичные
Route::get('/', function () {
    return view('welcome');
});

// Требуют авторизации
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Дашборд
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    
    // Практика
    Route::prefix('practice')->name('practice.')->group(function () {
        Route::get('/', [PracticeController::class, 'index'])->name('index');
        Route::get('/topic/{topic}', [PracticeController::class, 'topic'])->name('topic');
        Route::get('/task/{task}', [PracticeController::class, 'show'])->name('task');
        Route::post('/task/{task}/check', [PracticeController::class, 'check'])->name('check');
        Route::get('/random', [PracticeController::class, 'random'])->name('random');
    });
    
    // Теория
    Route::get('/theory/{topic}', [TheoryController::class, 'show'])->name('theory.show');
    
    // Прогресс
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('/progress/{topic}', [ProgressController::class, 'topic'])->name('progress.topic');
    
    // Профиль
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Админка
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('tasks', Admin\TaskController::class);
    Route::post('tasks/{task}/verify', [Admin\TaskController::class, 'verify'])->name('tasks.verify');
    
    Route::resource('topics', Admin\TopicController::class);
    Route::resource('users', Admin\UserController::class)->only(['index', 'show', 'destroy']);
});
```

---

## Ключевые сервисы

### AnswerCheckerService.php
```php
<?php

namespace App\Services;

use App\Models\Task;

class AnswerCheckerService
{
    public function check(Task $task, string $userAnswer): array
    {
        $correctAnswer = $task->answer_verified ?? $task->answer_generated;
        
        // Нормализация ответов
        $normalized_user = $this->normalize($userAnswer, $task->answer_type);
        $normalized_correct = $this->normalize($correctAnswer, $task->answer_type);
        
        $isCorrect = $normalized_user === $normalized_correct;
        
        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'user_answer' => $userAnswer,
        ];
    }
    
    private function normalize(string $answer, string $type): string
    {
        // Убираем пробелы, приводим к нижнему регистру
        $answer = mb_strtolower(trim($answer));
        
        // Заменяем запятую на точку для чисел
        if ($type === 'number') {
            $answer = str_replace(',', '.', $answer);
            // Убираем лишние нули
            if (is_numeric($answer)) {
                $answer = (string) floatval($answer);
            }
        }
        
        return $answer;
    }
}
```

### ProgressService.php
```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Topic;
use App\Models\UserProgress;
use App\Models\DailyGoal;

class ProgressService
{
    public function recordAttempt(User $user, int $taskId, bool $isCorrect): void
    {
        $task = Task::find($taskId);
        
        // Обновляем прогресс по теме
        $progress = UserProgress::firstOrCreate([
            'user_id' => $user->id,
            'topic_id' => $task->topic_id,
        ]);
        
        $progress->tasks_attempted++;
        if ($isCorrect) {
            $progress->tasks_correct++;
            $progress->current_streak++;
            $progress->best_streak = max($progress->best_streak, $progress->current_streak);
        } else {
            $progress->current_streak = 0;
        }
        
        // Пересчитываем уровень освоения
        $progress->mastery_level = $this->calculateMastery($progress);
        $progress->last_practice_at = now();
        $progress->save();
        
        // Обновляем ежедневную цель
        $this->updateDailyGoal($user, $isCorrect);
    }
    
    private function calculateMastery(UserProgress $progress): int
    {
        if ($progress->tasks_attempted === 0) return 0;
        
        $accuracy = ($progress->tasks_correct / $progress->tasks_attempted) * 100;
        $volume_bonus = min(20, $progress->tasks_attempted / 5);
        $streak_bonus = min(10, $progress->best_streak);
        
        return min(100, (int) ($accuracy * 0.7 + $volume_bonus + $streak_bonus));
    }
    
    private function updateDailyGoal(User $user, bool $isCorrect): void
    {
        $goal = DailyGoal::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()],
            ['goal' => $user->daily_goal]
        );
        
        $goal->completed++;
        if ($isCorrect) {
            $goal->correct++;
        }
        $goal->goal_reached = $goal->completed >= $goal->goal;
        $goal->save();
    }
}
```

---

## Безопасность

### .htaccess (корень проекта)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### public/.htaccess
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Безопасность
Options -Indexes
ServerSignature Off

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Запрет доступа к .env и другим скрытым файлам
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### .env (продакшен)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://oge-trainer.ru

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### Защита от уязвимостей

| Угроза | Реализация |
|--------|------------|
| SQL Injection | Eloquent ORM, prepared statements |
| XSS | Blade auto-escape `{{ }}` |
| CSRF | `@csrf` в формах, middleware |
| Brute-force | `throttle:5,1` на login |
| Session hijacking | HTTPS + HttpOnly + SameSite cookies |
| Mass Assignment | `$fillable` в моделях |

---

## План разработки (обновлённый)

### Фаза 0: Подготовка (1 день)
- [ ] Улучшить парсер PDF — извлечь картинки
- [ ] Сгенерировать ответы через Claude API (локально)
- [ ] Подготовить финальный JSON для импорта

### Фаза 1: Каркас Laravel (3-4 дня)
- [ ] Установка Laravel 10 + Breeze
- [ ] Миграции базы данных
- [ ] Модели + связи
- [ ] Seeder для импорта задач из JSON
- [ ] Базовая авторизация (регистрация, вход, email)

### Фаза 2: Основной функционал (5-7 дней)
- [ ] Список тем/предметов
- [ ] Страница практики (выбор задачи)
- [ ] Интерфейс решения задачи
- [ ] Проверка ответа
- [ ] Прогресс пользователя
- [ ] Дашборд со статистикой

### Фаза 3: Теория + улучшения (3-4 дня)
- [ ] Страницы теории по темам
- [ ] Подсказки к задачам
- [ ] Подробные решения
- [ ] Рендеринг формул (KaTeX)

### Фаза 4: Админка (2-3 дня)
- [ ] CRUD задач
- [ ] Верификация ответов
- [ ] Управление пользователями
- [ ] Статистика платформы

### Фаза 5: Геймификация (2-3 дня)
- [ ] Ежедневные цели
- [ ] Стрики (серии правильных ответов)
- [ ] Достижения/бейджи
- [ ] Лидерборд (опционально)

### Фаза 6: Деплой + интеграция (2-3 дня)
- [ ] Настройка shared-хостинга
- [ ] HTTPS сертификат
- [ ] Тестирование безопасности
- [ ] Интеграция с Эвриум CRM (API)

---

## Интеграция с Эвриум CRM

### Варианты связи

**1. Через общую БД (если на одном хостинге)**
```php
// config/database.php
'evrium' => [
    'driver' => 'mysql',
    'host' => env('EVRIUM_DB_HOST'),
    'database' => env('EVRIUM_DB_DATABASE'),
    // ...
],

// Модель
class EvriumStudent extends Model
{
    protected $connection = 'evrium';
    protected $table = 'students';
}
```

**2. Через API (если разные серверы)**
```php
// app/Services/EvriumService.php
class EvriumService
{
    public function syncStudent(int $evriumId): ?array
    {
        $response = Http::withToken(config('services.evrium.token'))
            ->get(config('services.evrium.url') . "/api/students/{$evriumId}");
        
        return $response->json();
    }
    
    public function sendProgress(int $evriumId, array $progress): void
    {
        Http::withToken(config('services.evrium.token'))
            ->post(config('services.evrium.url') . '/api/students/progress', [
                'student_id' => $evriumId,
                'oge_progress' => $progress,
            ]);
    }
}
```

---

## Примерный UI (Blade + Alpine.js)

### Страница задачи (practice/task.blade.php)
```html
<div class="max-w-2xl mx-auto p-6" x-data="taskForm()">
    <!-- Прогресс -->
    <div class="mb-4 flex justify-between text-sm text-gray-500">
        <span>Задание {{ $task->topic->oge_number }}</span>
        <span>{{ $task->topic->name }}</span>
    </div>
    
    <!-- Задача -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="prose max-w-none">
            {!! $task->text_html ?? e($task->text) !!}
        </div>
        
        @if($task->image_path)
            <img src="{{ asset($task->image_path) }}" 
                 alt="Иллюстрация к задаче" 
                 class="mt-4 max-w-full">
        @endif
    </div>
    
    <!-- Форма ответа -->
    <form @submit.prevent="submitAnswer" class="bg-white rounded-lg shadow p-6">
        @csrf
        
        <label class="block mb-4">
            <span class="text-gray-700 font-medium">Ваш ответ:</span>
            <input type="text" 
                   x-model="answer"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                   placeholder="Введите ответ"
                   :disabled="submitted">
        </label>
        
        <div x-show="!submitted">
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
                Проверить
            </button>
        </div>
        
        <!-- Результат -->
        <div x-show="submitted" x-cloak>
            <div :class="isCorrect ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                 class="p-4 rounded-lg mb-4">
                <template x-if="isCorrect">
                    <p class="font-bold">✓ Правильно!</p>
                </template>
                <template x-if="!isCorrect">
                    <div>
                        <p class="font-bold">✗ Неправильно</p>
                        <p>Правильный ответ: <span x-text="correctAnswer"></span></p>
                    </div>
                </template>
            </div>
            
            <a href="{{ route('practice.random', ['topic' => $task->topic_id]) }}"
               class="block w-full text-center bg-blue-600 text-white py-3 rounded-lg">
                Следующая задача →
            </a>
        </div>
    </form>
</div>

<script>
function taskForm() {
    return {
        answer: '',
        submitted: false,
        isCorrect: false,
        correctAnswer: '',
        
        async submitAnswer() {
            const response = await fetch('{{ route("practice.check", $task) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ answer: this.answer })
            });
            
            const result = await response.json();
            this.isCorrect = result.is_correct;
            this.correctAnswer = result.correct_answer;
            this.submitted = true;
        }
    }
}
</script>
```

---

## Следующий шаг

Готов начать с **Фазы 0** — улучшить парсер и сгенерировать ответы. Что делаем первым?

1. **Извлечь картинки из PDF** — важно для геометрии
2. **Сгенерировать ответы** — прогнать 3490 задач через Claude
3. **Начать Laravel проект** — создать структуру
