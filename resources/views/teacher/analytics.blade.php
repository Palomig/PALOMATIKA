@extends('layouts.teacher')

@section('title', 'Аналитика')
@section('header', 'Аналитика')

@section('content')
<div x-data="analyticsPage()">
    <!-- Period selector -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex space-x-2">
            <button @click="period = 'week'"
                    :class="period === 'week' ? 'bg-coral text-white' : 'bg-dark-light text-gray-400 border border-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                Неделя
            </button>
            <button @click="period = 'month'"
                    :class="period === 'month' ? 'bg-coral text-white' : 'bg-dark-light text-gray-400 border border-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                Месяц
            </button>
            <button @click="period = 'all'"
                    :class="period === 'all' ? 'bg-coral text-white' : 'bg-dark-light text-gray-400 border border-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition">
                Всё время
            </button>
        </div>
    </div>

    <!-- Overview stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-coral" x-text="stats.total_tasks_solved"></div>
            <div class="text-gray-400 text-sm">задач решено</div>
            <div class="text-xs mt-1" :class="stats.tasks_change >= 0 ? 'text-green-400' : 'text-red-400'"
                 x-text="(stats.tasks_change >= 0 ? '+' : '') + stats.tasks_change + '%'"></div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-green-400" x-text="stats.avg_accuracy + '%'"></div>
            <div class="text-gray-400 text-sm">средняя точность</div>
            <div class="text-xs mt-1" :class="stats.accuracy_change >= 0 ? 'text-green-400' : 'text-red-400'"
                 x-text="(stats.accuracy_change >= 0 ? '+' : '') + stats.accuracy_change + '%'"></div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-amber-400" x-text="stats.active_students"></div>
            <div class="text-gray-400 text-sm">активных учеников</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-blue-400" x-text="stats.avg_streak"></div>
            <div class="text-gray-400 text-sm">средний стрик</div>
        </div>
    </div>

    <!-- Topic performance -->
    <div class="bg-dark-light rounded-xl border border-gray-800 mb-6">
        <div class="p-4 border-b border-gray-700">
            <h3 class="font-semibold text-white">Успеваемость по темам</h3>
        </div>
        <div class="p-4">
            <div class="space-y-4">
                <template x-for="topic in topicStats" :key="topic.id">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-300" x-text="'№' + topic.oge_number + ' ' + topic.name"></span>
                            <span class="text-sm font-medium"
                                  :class="topic.accuracy >= 70 ? 'text-green-400' : topic.accuracy >= 50 ? 'text-amber-400' : 'text-red-400'"
                                  x-text="topic.accuracy + '%'"></span>
                        </div>
                        <div class="bg-gray-700 rounded-full h-2">
                            <div class="rounded-full h-2 transition-all"
                                 :class="topic.accuracy >= 70 ? 'bg-green-500' : topic.accuracy >= 50 ? 'bg-amber-500' : 'bg-red-500'"
                                 :style="'width: ' + topic.accuracy + '%'"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span x-text="topic.tasks_count + ' задач'"></span>
                            <span x-text="topic.students_count + ' учеников'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Student leaderboard -->
    <div class="bg-dark-light rounded-xl border border-gray-800">
        <div class="p-4 border-b border-gray-700">
            <h3 class="font-semibold text-white">Рейтинг учеников</h3>
        </div>
        <div class="divide-y divide-gray-800">
            <template x-for="(student, index) in studentRanking" :key="student.id">
                <div class="flex items-center p-4">
                    <div class="w-8 text-center font-medium"
                         :class="index < 3 ? 'text-amber-400' : 'text-gray-500'"
                         x-text="index + 1"></div>
                    <div class="w-10 h-10 bg-coral/20 rounded-full flex items-center justify-center ml-2">
                        <span class="text-coral font-medium" x-text="student.name?.charAt(0)"></span>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="font-medium text-white" x-text="student.name"></div>
                        <div class="text-xs text-gray-500" x-text="student.tasks_solved + ' задач'"></div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium text-green-400" x-text="student.accuracy + '%'"></div>
                        <div class="text-xs text-gray-500">точность</div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function analyticsPage() {
    return {
        period: 'week',
        stats: {
            total_tasks_solved: 450,
            tasks_change: 12,
            avg_accuracy: 74,
            accuracy_change: 3,
            active_students: 18,
            avg_streak: 8
        },
        topicStats: [
            { id: 1, oge_number: '06', name: 'Числа и вычисления', accuracy: 82, tasks_count: 120, students_count: 15 },
            { id: 2, oge_number: '08', name: 'Уравнения', accuracy: 68, tasks_count: 95, students_count: 18 },
            { id: 3, oge_number: '15', name: 'Треугольники', accuracy: 55, tasks_count: 78, students_count: 12 },
            { id: 4, oge_number: '17', name: 'Окружность', accuracy: 45, tasks_count: 45, students_count: 8 }
        ],
        studentRanking: [
            { id: 1, name: 'Елена Козлова', tasks_solved: 230, accuracy: 91 },
            { id: 2, name: 'Александр Иванов', tasks_solved: 150, accuracy: 82 },
            { id: 3, name: 'Мария Петрова', tasks_solved: 89, accuracy: 68 },
            { id: 4, name: 'Дмитрий Сидоров', tasks_solved: 45, accuracy: 55 }
        ]
    }
}
</script>
@endpush
@endsection
