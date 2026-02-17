@extends('layouts.teacher')

@section('title', 'Аналитика')
@section('header', 'Аналитика')

@section('content')
<div x-data="analyticsPage()" class="space-y-5">
    <section class="relative overflow-hidden rounded-2xl border border-white/[0.08] bg-dark-light p-5 sm:p-6">
        <div class="absolute top-0 right-0 h-full w-1/2 bg-gradient-to-l from-emerald-500/10 to-transparent pointer-events-none"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-gray-500 font-semibold mb-2">Performance</p>
                <h2 class="text-2xl font-semibold text-white">Аналитика результативности</h2>
                <p class="text-sm text-gray-500 mt-1">Сравнивайте динамику точности, активности и прогресса по темам и ученикам.</p>
            </div>
            <a href="/teacher/homework" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-coral text-white text-xs font-semibold hover:bg-coral-dark transition">
                Перейти к заданиям
            </a>
        </div>
    </section>

    {{-- Period selector --}}
    <div class="flex items-center gap-1.5 bg-dark-light rounded-xl p-1 border border-white/[0.06] w-fit">
        <template x-for="p in [{key:'week',label:'Неделя'},{key:'month',label:'Месяц'},{key:'all',label:'Всё время'}]" :key="p.key">
            <button @click="period = p.key"
                    :class="period === p.key
                        ? 'bg-coral text-white shadow-lg shadow-coral/10'
                        : 'text-gray-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                    x-text="p.label"></button>
        </template>
    </div>

    {{-- Overview stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-dark-light rounded-2xl p-5 border border-white/[0.06]">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Задач решено</span>
                <span class="text-[11px] font-medium tabular-nums px-1.5 py-0.5 rounded"
                      :class="stats.tasks_change >= 0 ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10'"
                      x-text="(stats.tasks_change >= 0 ? '+' : '') + stats.tasks_change + '%'"></span>
            </div>
            <div class="text-2xl font-bold text-white tabular-nums" x-text="stats.total_tasks_solved"></div>
        </div>
        <div class="bg-dark-light rounded-2xl p-5 border border-white/[0.06]">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ср. точность</span>
                <span class="text-[11px] font-medium tabular-nums px-1.5 py-0.5 rounded"
                      :class="stats.accuracy_change >= 0 ? 'text-emerald-400 bg-emerald-500/10' : 'text-red-400 bg-red-500/10'"
                      x-text="(stats.accuracy_change >= 0 ? '+' : '') + stats.accuracy_change + '%'"></span>
            </div>
            <div class="text-2xl font-bold text-white tabular-nums" x-text="stats.avg_accuracy + '%'"></div>
        </div>
        <div class="bg-dark-light rounded-2xl p-5 border border-white/[0.06]">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Активных</span>
            <div class="text-2xl font-bold text-white mt-2 tabular-nums" x-text="stats.active_students"></div>
        </div>
        <div class="bg-dark-light rounded-2xl p-5 border border-white/[0.06]">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ср. стрик</span>
            <div class="text-2xl font-bold text-white mt-2 tabular-nums" x-text="stats.avg_streak"></div>
        </div>
    </div>

    {{-- Topic performance --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[0.06]">
            <h3 class="font-semibold text-white text-[15px]">Успеваемость по темам</h3>
        </div>
        <div class="p-5 space-y-5">
            <template x-for="topic in topicStats" :key="topic.id">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-300" x-text="'#' + topic.oge_number + ' ' + topic.name"></span>
                        <span class="text-sm font-semibold tabular-nums"
                              :class="topic.accuracy >= 70 ? 'text-emerald-400' : topic.accuracy >= 50 ? 'text-amber-400' : 'text-red-400'"
                              x-text="topic.accuracy + '%'"></span>
                    </div>
                    <div class="bg-white/[0.06] rounded-full h-2">
                        <div class="rounded-full h-2 transition-all"
                             :class="topic.accuracy >= 70 ? 'bg-emerald-500' : topic.accuracy >= 50 ? 'bg-amber-500' : 'bg-red-500'"
                             :style="'width: ' + topic.accuracy + '%'"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-600 mt-1.5">
                        <span x-text="topic.tasks_count + ' задач'"></span>
                        <span x-text="topic.students_count + ' учеников'"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Student leaderboard --}}
    <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[0.06]">
            <h3 class="font-semibold text-white text-[15px]">Рейтинг учеников</h3>
        </div>
        <div class="divide-y divide-white/[0.04]">
            <template x-for="(student, index) in studentRanking" :key="student.id">
                <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-white/[0.02] transition-colors">
                    <div class="w-7 text-center">
                        <span class="text-sm font-bold tabular-nums"
                              :class="index === 0 ? 'text-amber-400' : index === 1 ? 'text-gray-300' : index === 2 ? 'text-amber-600' : 'text-gray-600'"
                              x-text="index + 1"></span>
                    </div>
                    <div class="w-9 h-9 bg-coral/10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-semibold text-coral" x-text="student.name?.charAt(0)"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate" x-text="student.name"></div>
                        <div class="text-[11px] text-gray-500" x-text="student.tasks_solved + ' задач'"></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-semibold text-emerald-400 tabular-nums" x-text="student.accuracy + '%'"></div>
                        <div class="text-[11px] text-gray-600">точность</div>
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
