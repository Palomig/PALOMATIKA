<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadmap - PALOMATIKA</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            DEFAULT: '#1a1a2e',
                            light: '#252542',
                            lighter: '#2d2d4a',
                        },
                        coral: {
                            DEFAULT: '#ff6b6b',
                            dark: '#e85555',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1a1a2e; }
        ::-webkit-scrollbar-thumb { background: #3d3d5c; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #4d4d6c; }
    </style>
</head>
<body class="bg-dark text-gray-100 min-h-screen font-sans">

<div x-data="roadmapBoard()" x-init="loadTasks()" class="min-h-screen">
    <!-- Header -->
    <header class="bg-dark-light border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/" class="text-2xl font-bold text-coral">PALOMATIKA</a>
                    <span class="text-gray-500">/</span>
                    <h1 class="text-xl font-semibold">Roadmap</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/kanban" class="text-gray-400 hover:text-white transition">Kanban</a>
                    <a href="/forstas" class="text-gray-400 hover:text-white transition">Architecture</a>
                    <button @click="loadTasks()" class="bg-dark-lighter hover:bg-gray-600 px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>
            </div>

            <!-- Project Info -->
            <div class="flex items-center gap-6 mt-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">Проект:</span>
                    <span class="font-semibold" x-text="project.name">-</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">Старт:</span>
                    <span class="font-semibold" x-text="formatShortDate(project.startDate)">-</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">Цель:</span>
                    <span class="font-semibold" x-text="formatShortDate(project.targetDate)">-</span>
                </div>
                <div class="flex items-center gap-2 ml-auto">
                    <span class="text-gray-400">Общий прогресс:</span>
                    <div class="w-40 h-3 bg-dark rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-coral to-green-500 transition-all duration-500" :style="`width: ${stats.progress}%`"></div>
                    </div>
                    <span class="font-bold text-lg" x-text="stats.progress + '%'">0%</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Roadmap Timeline -->
    <main class="max-w-[1600px] mx-auto p-4 mt-6">
        <!-- Timeline Header with Today marker -->
        <div class="relative mb-8">
            <div class="flex justify-between items-center mb-2">
                <template x-for="phase in phases" :key="phase.id">
                    <div class="flex-1 text-center">
                        <span class="text-sm text-gray-400" x-text="formatShortDate(phase.startDate)"></span>
                    </div>
                </template>
                <div class="text-center" style="width: 80px;">
                    <span class="text-sm text-gray-400" x-text="phases.length > 0 ? formatShortDate(phases[phases.length-1].endDate) : ''"></span>
                </div>
            </div>

            <!-- Timeline bar -->
            <div class="h-2 bg-dark-lighter rounded-full flex overflow-hidden">
                <template x-for="phase in phases" :key="phase.id">
                    <div class="h-full flex-1" :style="`background-color: ${phase.color}40`"></div>
                </template>
            </div>

            <!-- Today marker -->
            <div class="absolute top-8 transform -translate-x-1/2" :style="`left: ${getTodayPosition()}%`" x-show="getTodayPosition() >= 0 && getTodayPosition() <= 100">
                <div class="w-0.5 h-6 bg-coral"></div>
                <div class="text-xs text-coral font-medium mt-1 whitespace-nowrap">Сегодня</div>
            </div>
        </div>

        <!-- Phases -->
        <div class="space-y-6">
            <template x-for="phase in phases" :key="phase.id">
                <div class="bg-dark-light rounded-xl p-6 border-l-4" :style="`border-color: ${phase.color}`">
                    <!-- Phase Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full" :style="`background-color: ${phase.color}`"></div>
                            <h2 class="text-xl font-bold" x-text="phase.name"></h2>
                            <span class="text-gray-400 text-sm" x-text="phase.description"></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-gray-400">
                                <span x-text="formatShortDate(phase.startDate)"></span>
                                <span class="mx-2">→</span>
                                <span x-text="formatShortDate(phase.endDate)"></span>
                            </span>
                            <div class="flex items-center gap-2">
                                <div class="w-24 h-2 bg-dark rounded-full overflow-hidden">
                                    <div class="h-full transition-all duration-500" :style="`width: ${getPhaseProgress(phase.id)}%; background-color: ${phase.color}`"></div>
                                </div>
                                <span class="text-sm font-semibold" x-text="getPhaseProgress(phase.id) + '%'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks in Phase -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="task in getTasksByPhase(phase.id)" :key="task.id">
                            <div class="bg-dark-lighter rounded-lg p-3 border border-gray-700 hover:border-gray-500 transition cursor-pointer" @click="selectedTask = task">
                                <div class="flex items-start gap-2">
                                    <!-- Status icon -->
                                    <div class="mt-0.5">
                                        <template x-if="task.status === 'done'">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </template>
                                        <template x-if="task.status === 'in-progress'">
                                            <svg class="w-4 h-4 text-yellow-500 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                        </template>
                                        <template x-if="task.status === 'todo'">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                            </svg>
                                        </template>
                                        <template x-if="task.status === 'backlog'">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="4 4"/>
                                            </svg>
                                        </template>
                                        <template x-if="task.status === 'blocked'">
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-medium truncate" :class="task.status === 'done' ? 'text-gray-400 line-through' : ''" x-text="task.title"></h3>
                                        <!-- Priority dot -->
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="w-2 h-2 rounded-full" :class="getPriorityColor(task.priority)"></span>
                                            <span class="text-xs text-gray-500" x-text="task.priority"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Subtasks progress -->
                                <div x-show="task.subtasks && task.subtasks.length > 0" class="mt-2">
                                    <div class="w-full h-1 bg-dark rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500" :style="`width: ${(task.subtasks.filter(s => s.done).length / task.subtasks.length) * 100}%`"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span x-text="task.subtasks.filter(s => s.done).length"></span>/<span x-text="task.subtasks.length"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Legend -->
        <div class="mt-8 p-4 bg-dark-light rounded-xl">
            <h3 class="font-semibold mb-3">Легенда</h3>
            <div class="flex flex-wrap gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-gray-400">Done</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    <span class="text-gray-400">In Progress</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    </svg>
                    <span class="text-gray-400">To Do</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="4 4"/>
                    </svg>
                    <span class="text-gray-400">Backlog</span>
                </div>
                <div class="border-l border-gray-700 pl-6 flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="text-gray-400">Critical</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        <span class="text-gray-400">High</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                        <span class="text-gray-400">Medium</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                        <span class="text-gray-400">Low</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Task Detail Modal -->
    <div x-show="selectedTask" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="selectedTask = null">
        <div x-show="selectedTask" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="bg-dark-light rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <template x-if="selectedTask">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusBadgeClass(selectedTask.status)" x-text="selectedTask.status"></span>
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getPriorityBadgeClass(selectedTask.priority)" x-text="selectedTask.priority"></span>
                            </div>
                            <h2 class="text-xl font-bold" x-text="selectedTask.title"></h2>
                        </div>
                        <button @click="selectedTask = null" class="text-gray-400 hover:text-white p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-gray-300 mb-4" x-text="selectedTask.description"></p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <template x-for="tag in selectedTask.tags" :key="tag">
                            <span class="px-3 py-1 rounded-full bg-dark text-gray-300 text-sm" x-text="tag"></span>
                        </template>
                    </div>
                    <div x-show="selectedTask.subtasks && selectedTask.subtasks.length > 0" class="mb-4">
                        <h3 class="font-semibold mb-2">Subtasks</h3>
                        <div class="space-y-2">
                            <template x-for="(subtask, index) in selectedTask.subtasks" :key="index">
                                <div class="flex items-center gap-2 text-sm">
                                    <span x-show="subtask.done" class="text-green-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    <span x-show="!subtask.done" class="text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                        </svg>
                                    </span>
                                    <span :class="subtask.done ? 'text-gray-400 line-through' : 'text-gray-200'" x-text="subtask.title"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function roadmapBoard() {
    return {
        tasks: [],
        project: {},
        phases: [],
        stats: { total: 0, done: 0, progress: 0 },
        loading: false,
        selectedTask: null,

        async loadTasks() {
            this.loading = true;
            try {
                const response = await fetch('/api/board/tasks?' + Date.now());
                const data = await response.json();
                this.tasks = data.tasks || [];
                this.project = data.project || {};
                this.phases = data.phases || [];
                this.stats = data.stats || { total: 0, done: 0, progress: 0 };
            } catch (error) {
                console.error('Failed to load tasks:', error);
            }
            this.loading = false;
        },

        getTasksByPhase(phaseId) {
            return this.tasks.filter(t => t.phase === phaseId);
        },

        getPhaseProgress(phaseId) {
            const phaseTasks = this.getTasksByPhase(phaseId);
            if (phaseTasks.length === 0) return 0;
            const done = phaseTasks.filter(t => t.status === 'done').length;
            return Math.round((done / phaseTasks.length) * 100);
        },

        getTodayPosition() {
            if (this.phases.length === 0) return -1;
            const start = new Date(this.phases[0].startDate);
            const end = new Date(this.phases[this.phases.length - 1].endDate);
            const today = new Date();
            const total = end - start;
            const elapsed = today - start;
            return Math.round((elapsed / total) * 100);
        },

        getPriorityColor(priority) {
            const colors = {
                'critical': 'bg-red-500',
                'high': 'bg-orange-500',
                'medium': 'bg-yellow-500',
                'low': 'bg-gray-500'
            };
            return colors[priority] || 'bg-gray-500';
        },

        getPriorityBadgeClass(priority) {
            const classes = {
                'critical': 'bg-red-500/20 text-red-400',
                'high': 'bg-orange-500/20 text-orange-400',
                'medium': 'bg-yellow-500/20 text-yellow-400',
                'low': 'bg-gray-500/20 text-gray-400'
            };
            return classes[priority] || 'bg-gray-500/20 text-gray-400';
        },

        getStatusBadgeClass(status) {
            const classes = {
                'backlog': 'bg-gray-500/20 text-gray-400',
                'todo': 'bg-blue-500/20 text-blue-400',
                'in-progress': 'bg-yellow-500/20 text-yellow-400',
                'review': 'bg-purple-500/20 text-purple-400',
                'done': 'bg-green-500/20 text-green-400',
                'blocked': 'bg-red-500/20 text-red-400'
            };
            return classes[status] || 'bg-gray-500/20 text-gray-400';
        },

        formatShortDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short'
            });
        }
    };
}
</script>

</body>
</html>
