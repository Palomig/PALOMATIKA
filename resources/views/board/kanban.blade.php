<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban - PALOMATIKA</title>
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
        .kanban-column { min-height: calc(100vh - 200px); }
    </style>
</head>
<body class="bg-dark text-gray-100 min-h-screen font-sans">

<div x-data="kanbanBoard()" x-init="loadTasks()" class="min-h-screen">
    <!-- Header -->
    <header class="bg-dark-light border-b border-gray-700 sticky top-0 z-50">
        <div class="max-w-[1800px] mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/" class="text-2xl font-bold text-coral">PALOMATIKA</a>
                    <span class="text-gray-500">/</span>
                    <h1 class="text-xl font-semibold">Kanban Board</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/roadmap" class="text-gray-400 hover:text-white transition">Roadmap</a>
                    <a href="/forstas" class="text-gray-400 hover:text-white transition">Architecture</a>
                    <button @click="loadTasks()" class="bg-dark-lighter hover:bg-gray-600 px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Обновить
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-6 mt-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">Всего:</span>
                    <span class="font-semibold" x-text="stats.total">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    <span class="text-gray-400">В работе:</span>
                    <span class="font-semibold" x-text="stats.inProgress">0</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-gray-400">Готово:</span>
                    <span class="font-semibold" x-text="stats.done">0</span>
                </div>
                <div class="flex items-center gap-2 ml-auto">
                    <span class="text-gray-400">Прогресс:</span>
                    <div class="w-32 h-2 bg-dark rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-coral to-green-500 transition-all duration-500" :style="`width: ${stats.progress}%`"></div>
                    </div>
                    <span class="font-semibold" x-text="stats.progress + '%'">0%</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Kanban Columns -->
    <main class="max-w-[1800px] mx-auto p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <!-- Backlog -->
            <div class="bg-dark-light rounded-xl p-4 kanban-column">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-gray-500"></span>
                    <h2 class="font-semibold">Backlog</h2>
                    <span class="text-gray-500 text-sm" x-text="getTasksByStatus('backlog').length"></span>
                </div>
                <div class="space-y-3">
                    <template x-for="task in getTasksByStatus('backlog')" :key="task.id">
                        <div @click="openTaskModal(task)" class="bg-dark-lighter hover:bg-gray-700 rounded-lg p-3 cursor-pointer transition border border-transparent hover:border-gray-600">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="getPriorityColor(task.priority)"></span>
                                <h3 class="font-medium text-sm" x-text="task.title"></h3>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="tag in task.tags" :key="tag">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-dark text-gray-400" x-text="tag"></span>
                                </template>
                            </div>
                            <div x-show="task.subtasks && task.subtasks.length > 0" class="mt-2 text-xs text-gray-500">
                                <span x-text="task.subtasks.filter(s => s.done).length"></span>/<span x-text="task.subtasks.length"></span> subtasks
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Todo -->
            <div class="bg-dark-light rounded-xl p-4 kanban-column">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                    <h2 class="font-semibold">To Do</h2>
                    <span class="text-gray-500 text-sm" x-text="getTasksByStatus('todo').length"></span>
                </div>
                <div class="space-y-3">
                    <template x-for="task in getTasksByStatus('todo')" :key="task.id">
                        <div @click="openTaskModal(task)" class="bg-dark-lighter hover:bg-gray-700 rounded-lg p-3 cursor-pointer transition border border-transparent hover:border-gray-600">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="getPriorityColor(task.priority)"></span>
                                <h3 class="font-medium text-sm" x-text="task.title"></h3>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="tag in task.tags" :key="tag">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-dark text-gray-400" x-text="tag"></span>
                                </template>
                            </div>
                            <div x-show="task.subtasks && task.subtasks.length > 0" class="mt-2 text-xs text-gray-500">
                                <span x-text="task.subtasks.filter(s => s.done).length"></span>/<span x-text="task.subtasks.length"></span> subtasks
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- In Progress -->
            <div class="bg-dark-light rounded-xl p-4 kanban-column border-2 border-yellow-500/30">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                    <h2 class="font-semibold">In Progress</h2>
                    <span class="text-gray-500 text-sm" x-text="getTasksByStatus('in-progress').length"></span>
                </div>
                <div class="space-y-3">
                    <template x-for="task in getTasksByStatus('in-progress')" :key="task.id">
                        <div @click="openTaskModal(task)" class="bg-dark-lighter hover:bg-gray-700 rounded-lg p-3 cursor-pointer transition border border-yellow-500/50">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="getPriorityColor(task.priority)"></span>
                                <h3 class="font-medium text-sm" x-text="task.title"></h3>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="tag in task.tags" :key="tag">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-dark text-gray-400" x-text="tag"></span>
                                </template>
                            </div>
                            <div x-show="task.subtasks && task.subtasks.length > 0" class="mt-2">
                                <div class="w-full h-1 bg-dark rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500" :style="`width: ${(task.subtasks.filter(s => s.done).length / task.subtasks.length) * 100}%`"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span x-text="task.subtasks.filter(s => s.done).length"></span>/<span x-text="task.subtasks.length"></span> subtasks
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Review -->
            <div class="bg-dark-light rounded-xl p-4 kanban-column">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                    <h2 class="font-semibold">Review</h2>
                    <span class="text-gray-500 text-sm" x-text="getTasksByStatus('review').length"></span>
                </div>
                <div class="space-y-3">
                    <template x-for="task in getTasksByStatus('review')" :key="task.id">
                        <div @click="openTaskModal(task)" class="bg-dark-lighter hover:bg-gray-700 rounded-lg p-3 cursor-pointer transition border border-transparent hover:border-gray-600">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="getPriorityColor(task.priority)"></span>
                                <h3 class="font-medium text-sm" x-text="task.title"></h3>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="tag in task.tags" :key="tag">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-dark text-gray-400" x-text="tag"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Done -->
            <div class="bg-dark-light rounded-xl p-4 kanban-column">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    <h2 class="font-semibold">Done</h2>
                    <span class="text-gray-500 text-sm" x-text="getTasksByStatus('done').length"></span>
                </div>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                    <template x-for="task in getTasksByStatus('done')" :key="task.id">
                        <div @click="openTaskModal(task)" class="bg-dark-lighter/50 hover:bg-gray-700 rounded-lg p-3 cursor-pointer transition border border-transparent hover:border-gray-600 opacity-70 hover:opacity-100">
                            <div class="flex items-start gap-2 mb-2">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <h3 class="font-medium text-sm line-through text-gray-400" x-text="task.title"></h3>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="tag in task.tags" :key="tag">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-dark text-gray-500" x-text="tag"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Blocked tasks (if any) -->
        <div x-show="getTasksByStatus('blocked').length > 0" class="mt-6">
            <div class="bg-red-900/20 border border-red-500/30 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                    <h2 class="font-semibold text-red-400">Blocked</h2>
                    <span class="text-red-400/50 text-sm" x-text="getTasksByStatus('blocked').length"></span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <template x-for="task in getTasksByStatus('blocked')" :key="task.id">
                        <div @click="openTaskModal(task)" class="bg-dark-lighter hover:bg-gray-700 rounded-lg p-3 cursor-pointer transition border border-red-500/50">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 bg-red-500"></span>
                                <h3 class="font-medium text-sm" x-text="task.title"></h3>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </main>

    <!-- Task Detail Modal -->
    <div x-show="selectedTask" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="selectedTask = null">
        <div x-show="selectedTask" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="bg-dark-light rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <template x-if="selectedTask">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 rounded text-xs font-medium" :class="getStatusBadgeClass(selectedTask.status)" x-text="getStatusLabel(selectedTask.status)"></span>
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

                    <!-- Description -->
                    <p class="text-gray-300 mb-4" x-text="selectedTask.description"></p>

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <template x-for="tag in selectedTask.tags" :key="tag">
                            <span class="px-3 py-1 rounded-full bg-dark text-gray-300 text-sm" x-text="tag"></span>
                        </template>
                    </div>

                    <!-- Phase -->
                    <div class="mb-4">
                        <span class="text-gray-500 text-sm">Phase:</span>
                        <span class="ml-2 text-sm" x-text="getPhaseLabel(selectedTask.phase)"></span>
                    </div>

                    <!-- Subtasks -->
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

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-400 pt-4 border-t border-gray-700">
                        <div>
                            <span>Created:</span>
                            <span class="ml-2 text-gray-300" x-text="formatDate(selectedTask.createdAt)"></span>
                        </div>
                        <div>
                            <span>Updated:</span>
                            <span class="ml-2 text-gray-300" x-text="formatDate(selectedTask.updatedAt)"></span>
                        </div>
                        <div x-show="selectedTask.completedAt">
                            <span>Completed:</span>
                            <span class="ml-2 text-green-400" x-text="formatDate(selectedTask.completedAt)"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function kanbanBoard() {
    return {
        tasks: [],
        project: {},
        phases: [],
        stats: { total: 0, done: 0, inProgress: 0, todo: 0, backlog: 0, progress: 0 },
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
                this.stats = data.stats || { total: 0, done: 0, inProgress: 0, todo: 0, backlog: 0, progress: 0 };
            } catch (error) {
                console.error('Failed to load tasks:', error);
            }
            this.loading = false;
        },

        getTasksByStatus(status) {
            return this.tasks.filter(t => t.status === status);
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

        getStatusLabel(status) {
            const labels = {
                'backlog': 'Backlog',
                'todo': 'To Do',
                'in-progress': 'In Progress',
                'review': 'Review',
                'done': 'Done',
                'blocked': 'Blocked'
            };
            return labels[status] || status;
        },

        getPhaseLabel(phaseId) {
            const phase = this.phases.find(p => p.id === phaseId);
            return phase ? phase.name : phaseId;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        openTaskModal(task) {
            this.selectedTask = task;
        }
    };
}
</script>

</body>
</html>
