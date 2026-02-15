@extends('layouts.teacher')

@section('title', 'Домашние задания')
@section('header', 'Домашние задания')

@section('content')
<div x-data="homeworkPage()" class="space-y-5">
    <section class="relative overflow-hidden rounded-2xl border border-white/[0.08] bg-dark-light p-5 sm:p-6">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-amber-500/10 to-transparent pointer-events-none"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-gray-500 font-semibold mb-2">Homework flow</p>
                <h2 class="text-2xl font-semibold text-white">Домашние задания и контроль выполнения</h2>
                <p class="text-sm text-gray-500 mt-1">Отслеживайте прогресс по каждому заданию и быстро открывайте результаты по ученикам.</p>
            </div>
            <a href="/teacher/analytics" class="inline-flex items-center px-3 py-2 rounded-xl border border-white/[0.1] text-xs font-semibold text-gray-300 hover:text-white hover:bg-white/[0.03] transition">
                Смотреть аналитику
            </a>
        </div>
    </section>

    {{-- Actions bar --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <select x-model="statusFilter" class="bg-dark-light border border-white/[0.06] rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition">
            <option value="">Все</option>
            <option value="active">Активные</option>
            <option value="completed">Завершённые</option>
            <option value="overdue">Просроченные</option>
        </select>
        <button @click="showCreateModal = true"
                class="inline-flex items-center gap-2 bg-coral text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-coral-dark transition shadow-lg shadow-coral/10 hover:shadow-coral/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Создать задание
        </button>
    </div>

    {{-- Homework list --}}
    <div class="space-y-4">
        <template x-for="hw in filteredHomework" :key="hw.id">
            <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden hover:border-white/[0.1] transition-colors">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-white" x-text="hw.title"></h3>
                            <p class="text-sm text-gray-500 mt-0.5" x-text="hw.description"></p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg flex-shrink-0"
                              :class="{
                                  'bg-emerald-500/10 text-emerald-400': hw.status === 'active',
                                  'bg-white/[0.04] text-gray-500': hw.status === 'completed',
                                  'bg-red-500/10 text-red-400': hw.status === 'overdue'
                              }"
                              x-text="statusLabels[hw.status]"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                        <div class="bg-dark/50 rounded-xl p-3">
                            <div class="text-[11px] text-gray-500 mb-0.5">Назначено</div>
                            <div class="text-sm font-medium text-white" x-text="hw.assigned_count + ' уч.'"></div>
                        </div>
                        <div class="bg-dark/50 rounded-xl p-3">
                            <div class="text-[11px] text-gray-500 mb-0.5">Выполнено</div>
                            <div class="text-sm font-medium text-white" x-text="hw.completed_count + ' (' + hw.completion_rate + '%)'"></div>
                        </div>
                        <div class="bg-dark/50 rounded-xl p-3">
                            <div class="text-[11px] text-gray-500 mb-0.5">Средний балл</div>
                            <div class="text-sm font-medium text-white" x-text="hw.avg_score + '%'"></div>
                        </div>
                        <div class="bg-dark/50 rounded-xl p-3">
                            <div class="text-[11px] text-gray-500 mb-0.5">Дедлайн</div>
                            <div class="text-sm font-medium text-white" x-text="hw.due_date"></div>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div class="mb-4">
                        <div class="flex justify-between text-[11px] text-gray-500 mb-1.5">
                            <span>Прогресс выполнения</span>
                            <span class="tabular-nums" x-text="hw.completion_rate + '%'"></span>
                        </div>
                        <div class="bg-white/[0.06] rounded-full h-1.5">
                            <div class="bg-coral rounded-full h-1.5 transition-all"
                                 :style="'width: ' + hw.completion_rate + '%'"></div>
                        </div>
                    </div>

                    {{-- Student results toggle --}}
                    <div class="border-t border-white/[0.04] pt-3">
                        <button @click="hw.showDetails = !hw.showDetails"
                                class="flex items-center gap-2 text-xs font-medium text-gray-400 hover:text-white transition">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="hw.showDetails ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span x-text="hw.showDetails ? 'Скрыть результаты' : 'Показать результаты'"></span>
                        </button>
                        <div x-show="hw.showDetails" x-collapse class="mt-3">
                            <div class="space-y-1.5">
                                <template x-for="result in hw.results" :key="result.student_id">
                                    <div class="flex items-center justify-between px-3 py-2.5 bg-dark/50 rounded-xl">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 bg-coral/10 rounded-lg flex items-center justify-center">
                                                <span class="text-xs font-semibold text-coral" x-text="result.student_name?.charAt(0)"></span>
                                            </div>
                                            <span class="text-sm text-white" x-text="result.student_name"></span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium tabular-nums"
                                                  :class="result.completed ? 'text-emerald-400' : 'text-gray-600'"
                                                  x-text="result.completed ? result.score + '%' : 'Не выполнено'"></span>
                                            <span class="text-[11px] text-gray-600" x-text="result.completed_at || ''"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="filteredHomework.length === 0" class="bg-dark-light rounded-2xl border border-white/[0.06] p-12 text-center">
            <div class="w-14 h-14 bg-white/[0.04] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-gray-500 text-sm mb-3">Нет домашних заданий</p>
            <button @click="showCreateModal = true"
                    class="text-sm font-medium text-coral hover:text-coral-light transition">
                Создать первое задание
            </button>
        </div>
    </div>

    {{-- Create modal --}}
    <div x-show="showCreateModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click.self="showCreateModal = false">
        <div class="bg-dark-light rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto border border-white/[0.08] shadow-2xl">
            <div class="px-6 py-4 border-b border-white/[0.06]">
                <h2 class="text-lg font-semibold text-white">Создать домашнее задание</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Название</label>
                    <input type="text" x-model="newHomework.title"
                           class="w-full px-4 py-2.5 bg-dark border border-white/[0.08] rounded-xl text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition"
                           placeholder="Например: Квадратные уравнения">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Описание</label>
                    <textarea x-model="newHomework.description"
                              class="w-full px-4 py-2.5 bg-dark border border-white/[0.08] rounded-xl text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition"
                              rows="2" placeholder="Краткое описание задания"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Тема</label>
                    <select x-model="newHomework.topic_id"
                            class="w-full px-4 py-2.5 bg-dark border border-white/[0.08] rounded-xl text-sm text-white focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition">
                        <option value="">Выберите тему</option>
                        <template x-for="topic in topics" :key="topic.id">
                            <option :value="topic.id" x-text="'#' + topic.oge_number + ' ' + topic.name"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Количество задач</label>
                        <input type="number" x-model="newHomework.tasks_count"
                               class="w-full px-4 py-2.5 bg-dark border border-white/[0.08] rounded-xl text-sm text-white focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition"
                               min="1" max="20" value="5">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Дедлайн</label>
                        <input type="date" x-model="newHomework.due_date"
                               class="w-full px-4 py-2.5 bg-dark border border-white/[0.08] rounded-xl text-sm text-white focus:ring-2 focus:ring-coral/40 focus:border-coral/30 transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Назначить ученикам</label>
                    <div class="space-y-1 max-h-40 overflow-y-auto bg-dark/50 rounded-xl p-2">
                        <label class="flex items-center p-2 hover:bg-white/[0.03] rounded-lg cursor-pointer transition">
                            <input type="checkbox" @change="toggleAllStudents" class="mr-3 rounded border-gray-600 text-coral focus:ring-coral/40 bg-dark">
                            <span class="text-sm font-medium text-white">Выбрать всех</span>
                        </label>
                        <template x-for="student in students" :key="student.id">
                            <label class="flex items-center p-2 hover:bg-white/[0.03] rounded-lg cursor-pointer transition">
                                <input type="checkbox" :value="student.id"
                                       x-model="newHomework.student_ids" class="mr-3 rounded border-gray-600 text-coral focus:ring-coral/40 bg-dark">
                                <span class="text-sm text-gray-300" x-text="student.name"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/[0.06] flex gap-3">
                <button @click="showCreateModal = false"
                        class="flex-1 py-2.5 border border-white/[0.08] rounded-xl text-sm text-gray-400 hover:text-white hover:bg-white/[0.04] transition">
                    Отмена
                </button>
                <button @click="createHomework"
                        class="flex-1 py-2.5 bg-coral text-white rounded-xl text-sm font-medium hover:bg-coral-dark transition shadow-lg shadow-coral/10">
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
