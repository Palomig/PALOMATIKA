@extends('layouts.teacher')

@section('title', 'Домашние задания')
@section('header', 'Домашние задания')

@section('content')
<div x-data="homeworkPage()">
    <!-- Actions -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <select x-model="statusFilter" class="bg-dark border border-gray-700 rounded-lg px-4 py-2 text-white">
                <option value="">Все</option>
                <option value="active">Активные</option>
                <option value="completed">Завершённые</option>
                <option value="overdue">Просроченные</option>
            </select>
        </div>
        <button @click="showCreateModal = true"
                class="bg-coral text-white px-4 py-2 rounded-lg font-medium hover:bg-coral-dark transition">
            + Создать задание
        </button>
    </div>

    <!-- Homework list -->
    <div class="space-y-4">
        <template x-for="hw in filteredHomework" :key="hw.id">
            <div class="bg-dark-light rounded-xl border border-gray-800 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-white text-lg" x-text="hw.title"></h3>
                        <p class="text-gray-500 text-sm" x-text="hw.description"></p>
                    </div>
                    <span class="px-3 py-1 text-sm rounded-full"
                          :class="{
                              'bg-green-500/20 text-green-400': hw.status === 'active',
                              'bg-gray-700 text-gray-400': hw.status === 'completed',
                              'bg-red-500/20 text-red-400': hw.status === 'overdue'
                          }"
                          x-text="statusLabels[hw.status]"></span>
                </div>

                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <div class="text-sm text-gray-500">Назначено</div>
                        <div class="font-medium text-white" x-text="hw.assigned_count + ' ученикам'"></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Выполнено</div>
                        <div class="font-medium text-white" x-text="hw.completed_count + ' (' + hw.completion_rate + '%)'"></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Средний балл</div>
                        <div class="font-medium text-white" x-text="hw.avg_score + '%'"></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Дедлайн</div>
                        <div class="font-medium text-white" x-text="hw.due_date"></div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                        <span>Прогресс выполнения</span>
                        <span x-text="hw.completion_rate + '%'"></span>
                    </div>
                    <div class="bg-gray-700 rounded-full h-2">
                        <div class="bg-coral rounded-full h-2 transition-all"
                             :style="'width: ' + hw.completion_rate + '%'"></div>
                    </div>
                </div>

                <!-- Student results -->
                <div class="border-t border-gray-700 pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-400">Результаты учеников</span>
                        <button @click="hw.showDetails = !hw.showDetails"
                                class="text-coral text-sm hover:text-coral-light transition"
                                x-text="hw.showDetails ? 'Скрыть' : 'Показать'"></button>
                    </div>
                    <div x-show="hw.showDetails" x-collapse>
                        <div class="space-y-2 mt-3">
                            <template x-for="result in hw.results" :key="result.student_id">
                                <div class="flex items-center justify-between p-3 bg-dark rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-coral/20 rounded-full flex items-center justify-center">
                                            <span class="text-coral text-sm font-medium"
                                                  x-text="result.student_name?.charAt(0)"></span>
                                        </div>
                                        <span class="ml-3 text-white" x-text="result.student_name"></span>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm"
                                              :class="result.completed ? 'text-green-400' : 'text-gray-500'"
                                              x-text="result.completed ? result.score + '%' : 'Не выполнено'"></span>
                                        <span class="text-xs text-gray-500" x-text="result.completed_at || ''"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="filteredHomework.length === 0" class="bg-dark-light rounded-xl border border-gray-800 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500">Нет домашних заданий</p>
            <button @click="showCreateModal = true"
                    class="mt-4 text-coral font-medium hover:text-coral-light transition">
                Создать первое задание
            </button>
        </div>
    </div>

    <!-- Create modal -->
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
         @click.self="showCreateModal = false">
        <div class="bg-dark-light rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto border border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">Создать домашнее задание</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Название</label>
                    <input type="text" x-model="newHomework.title"
                           class="w-full px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-coral focus:border-transparent"
                           placeholder="Например: Квадратные уравнения">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Описание</label>
                    <textarea x-model="newHomework.description"
                              class="w-full px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-coral focus:border-transparent"
                              rows="2" placeholder="Краткое описание задания"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Тема</label>
                    <select x-model="newHomework.topic_id"
                            class="w-full px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white">
                        <option value="">Выберите тему</option>
                        <template x-for="topic in topics" :key="topic.id">
                            <option :value="topic.id" x-text="'№' + topic.oge_number + ' ' + topic.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Количество задач</label>
                    <input type="number" x-model="newHomework.tasks_count"
                           class="w-full px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white"
                           min="1" max="20" value="5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Дедлайн</label>
                    <input type="date" x-model="newHomework.due_date"
                           class="w-full px-4 py-2 bg-dark border border-gray-700 rounded-lg text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Назначить ученикам</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <label class="flex items-center p-2 hover:bg-gray-800/50 rounded cursor-pointer">
                            <input type="checkbox" @change="toggleAllStudents" class="mr-3 text-coral">
                            <span class="font-medium text-white">Выбрать всех</span>
                        </label>
                        <template x-for="student in students" :key="student.id">
                            <label class="flex items-center p-2 hover:bg-gray-800/50 rounded cursor-pointer">
                                <input type="checkbox" :value="student.id"
                                       x-model="newHomework.student_ids" class="mr-3 text-coral">
                                <span class="text-gray-300" x-text="student.name"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-700 flex space-x-4">
                <button @click="showCreateModal = false"
                        class="flex-1 py-2 border border-gray-700 rounded-lg text-gray-400 hover:text-white hover:border-gray-600 transition">
                    Отмена
                </button>
                <button @click="createHomework"
                        class="flex-1 py-2 bg-coral text-white rounded-lg font-medium hover:bg-coral-dark transition">
                    Создать
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function homeworkPage() {
    return {
        homework: [],
        students: [],
        topics: [],
        statusFilter: '',
        showCreateModal: false,
        statusLabels: {
            'active': 'Активно',
            'completed': 'Завершено',
            'overdue': 'Просрочено'
        },
        newHomework: {
            title: '',
            description: '',
            topic_id: '',
            tasks_count: 5,
            due_date: '',
            student_ids: []
        },

        async init() {
            await Promise.all([
                this.loadHomework(),
                this.loadStudents(),
                this.loadTopics()
            ]);
        },

        async loadHomework() {
            // Mock data
            this.homework = [
                {
                    id: 1,
                    title: 'Квадратные уравнения',
                    description: 'Решение уравнений через дискриминант',
                    status: 'active',
                    assigned_count: 12,
                    completed_count: 9,
                    completion_rate: 75,
                    avg_score: 82,
                    due_date: '15 января 2025',
                    showDetails: false,
                    results: [
                        { student_id: 1, student_name: 'Александр', completed: true, score: 90, completed_at: '10 янв' },
                        { student_id: 2, student_name: 'Мария', completed: true, score: 85, completed_at: '11 янв' },
                        { student_id: 3, student_name: 'Дмитрий', completed: false }
                    ]
                },
                {
                    id: 2,
                    title: 'Теорема Пифагора',
                    description: 'Применение теоремы в задачах',
                    status: 'overdue',
                    assigned_count: 10,
                    completed_count: 4,
                    completion_rate: 40,
                    avg_score: 65,
                    due_date: '10 января 2025',
                    showDetails: false,
                    results: []
                }
            ];
        },

        async loadStudents() {
            this.students = [
                { id: 1, name: 'Александр Иванов' },
                { id: 2, name: 'Мария Петрова' },
                { id: 3, name: 'Дмитрий Сидоров' },
                { id: 4, name: 'Елена Козлова' }
            ];
        },

        async loadTopics() {
            try {
                const response = await fetch('/api/topics');
                if (response.ok) {
                    const data = await response.json();
                    this.topics = data.topics || [];
                }
            } catch (e) {
                this.topics = [
                    { id: 1, oge_number: '08', name: 'Уравнения' },
                    { id: 2, oge_number: '15', name: 'Геометрия: треугольники' }
                ];
            }
        },

        get filteredHomework() {
            if (!this.statusFilter) return this.homework;
            return this.homework.filter(h => h.status === this.statusFilter);
        },

        toggleAllStudents(e) {
            if (e.target.checked) {
                this.newHomework.student_ids = this.students.map(s => s.id);
            } else {
                this.newHomework.student_ids = [];
            }
        },

        async createHomework() {
            // API call would go here
            alert('Домашнее задание создано!');
            this.showCreateModal = false;
            this.newHomework = {
                title: '',
                description: '',
                topic_id: '',
                tasks_count: 5,
                due_date: '',
                student_ids: []
            };
        }
    }
}
</script>
@endpush
@endsection
