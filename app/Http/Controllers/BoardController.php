<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    /**
     * Path to tasks.json file
     */
    protected string $tasksPath;
    protected string $metaPath;

    public function __construct()
    {
        $this->tasksPath = base_path('.claude/tasks.json');
        $this->metaPath = base_path('.claude/project-meta.json');
    }

    /**
     * API: Get all board data (tasks + project info)
     */
    public function apiGetTasks(): JsonResponse
    {
        if (!file_exists($this->tasksPath)) {
            return response()->json(['error' => 'Tasks file not found'], 404);
        }

        $data = json_decode(file_get_contents($this->tasksPath), true);

        // Calculate statistics
        $tasks = $data['tasks'] ?? [];
        $stats = [
            'total' => count($tasks),
            'done' => count(array_filter($tasks, fn($t) => $t['status'] === 'done')),
            'inProgress' => count(array_filter($tasks, fn($t) => $t['status'] === 'in-progress')),
            'todo' => count(array_filter($tasks, fn($t) => $t['status'] === 'todo')),
            'backlog' => count(array_filter($tasks, fn($t) => $t['status'] === 'backlog')),
            'blocked' => count(array_filter($tasks, fn($t) => $t['status'] === 'blocked')),
            'review' => count(array_filter($tasks, fn($t) => $t['status'] === 'review')),
        ];
        $stats['progress'] = $stats['total'] > 0
            ? round(($stats['done'] / $stats['total']) * 100)
            : 0;

        return response()->json([
            'project' => $data['project'] ?? [],
            'phases' => $data['phases'] ?? [],
            'tasks' => $tasks,
            'changelog' => $data['changelog'] ?? [],
            'stats' => $stats,
        ]);
    }

    /**
     * API: Get project metadata
     */
    public function apiGetMeta(): JsonResponse
    {
        if (!file_exists($this->metaPath)) {
            return response()->json(['error' => 'Meta file not found'], 404);
        }

        $data = json_decode(file_get_contents($this->metaPath), true);
        return response()->json($data);
    }

    /**
     * Show Kanban board page
     */
    public function kanban()
    {
        return view('board.kanban');
    }

    /**
     * Show Roadmap page
     */
    public function roadmap()
    {
        return view('board.roadmap');
    }

    /**
     * Show Architecture page (for Forstas)
     */
    public function architecture()
    {
        // Get project structure
        $structure = $this->getProjectStructure();

        return view('board.architecture', [
            'structure' => $structure,
        ]);
    }

    /**
     * Get simplified project structure for architecture page
     */
    protected function getProjectStructure(): array
    {
        return [
            'overview' => [
                'name' => 'PALOMATIKA',
                'description' => 'Платформа для подготовки к ОГЭ и ЕГЭ по математике',
                'stack' => 'Laravel 10 + Tailwind CSS + Alpine.js',
            ],
            'layers' => [
                [
                    'name' => 'Пользователи',
                    'icon' => 'users',
                    'description' => 'Ученики, учителя и админы заходят на сайт',
                    'color' => 'blue',
                ],
                [
                    'name' => 'Веб-страницы (Views)',
                    'icon' => 'layout',
                    'description' => 'HTML страницы, которые видит пользователь',
                    'color' => 'green',
                    'files' => [
                        'welcome.blade.php' => 'Главная страница (landing)',
                        'dashboard.blade.php' => 'Личный кабинет ученика',
                        'topics/index.blade.php' => 'Список всех тем ОГЭ',
                        'topics/show.blade.php' => 'Страница с заданиями темы',
                        'ege/index.blade.php' => 'Список заданий ЕГЭ',
                        'auth/login.blade.php' => 'Страница входа',
                    ],
                ],
                [
                    'name' => 'Маршруты (Routes)',
                    'icon' => 'signpost',
                    'description' => 'Определяют какая страница открывается по какому адресу',
                    'color' => 'yellow',
                    'files' => [
                        'routes/web.php' => 'Все адреса сайта (/topics, /ege, /login и т.д.)',
                        'routes/api.php' => 'API адреса для мобильного приложения',
                    ],
                ],
                [
                    'name' => 'Контроллеры',
                    'icon' => 'cpu',
                    'description' => 'Логика: что делать когда пользователь открыл страницу',
                    'color' => 'purple',
                    'files' => [
                        'TopicController.php' => 'Управление темами ОГЭ',
                        'EgeController.php' => 'Управление заданиями ЕГЭ',
                        'AuthController.php' => 'Вход/регистрация',
                        'BoardController.php' => 'Канбан-доска и роадмап',
                    ],
                ],
                [
                    'name' => 'Сервисы',
                    'icon' => 'cog',
                    'description' => 'Бизнес-логика: работа с данными',
                    'color' => 'orange',
                    'files' => [
                        'TaskDataService.php' => 'Загружает задания ОГЭ из JSON файлов',
                        'EgeTaskDataService.php' => 'Загружает задания ЕГЭ из JSON файлов',
                        'GeometrySvgRenderer.php' => 'Создаёт картинки геометрии (SVG)',
                    ],
                ],
                [
                    'name' => 'Данные (JSON)',
                    'icon' => 'database',
                    'description' => 'Все задания хранятся в файлах формата JSON',
                    'color' => 'red',
                    'files' => [
                        'storage/app/tasks/topic_06.json' => 'Задания темы 6 (дроби)',
                        'storage/app/tasks/topic_15.json' => 'Задания темы 15 (треугольники)',
                        'storage/app/tasks/ege/topic_01.json' => 'Задания ЕГЭ №1',
                        '.claude/tasks.json' => 'Задачи проекта (канбан)',
                    ],
                ],
                [
                    'name' => 'База данных (MySQL)',
                    'icon' => 'server',
                    'description' => 'Хранит пользователей, прогресс, достижения',
                    'color' => 'gray',
                    'tables' => [
                        'users' => 'Пользователи (ученики, учителя)',
                        'attempts' => 'Попытки решения задач',
                        'badges' => 'Достижения',
                        'leagues' => 'Лиги (Bronze, Silver, Gold...)',
                    ],
                ],
            ],
            'flows' => [
                [
                    'name' => 'Ученик решает задачу',
                    'steps' => [
                        'Заходит на /topics/15',
                        'TopicController получает запрос',
                        'TaskDataService загружает topic_15.json',
                        'Blade шаблон показывает задания',
                        'Ученик выбирает ответ',
                        'JavaScript проверяет правильность',
                        'API сохраняет результат в базу',
                    ],
                ],
                [
                    'name' => 'Генерация варианта ОГЭ',
                    'steps' => [
                        'Заходит на /oge',
                        'Нажимает "Сгенерировать"',
                        'Создаётся уникальный hash',
                        'TaskDataService выбирает случайные задания из каждой темы',
                        'Redirect на /oge/{hash}',
                        'Этот hash можно отправить другу — вариант будет такой же',
                    ],
                ],
            ],
        ];
    }
}
