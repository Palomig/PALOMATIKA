@extends('layouts.teacher')

@section('title', 'Обзор')
@section('header', 'Обзор')

@section('content')
<div x-data="teacherDashboard()">
    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="relative overflow-hidden bg-dark-light rounded-2xl p-5 border border-white/[0.06] group hover:border-blue-500/20 transition-colors">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-blue-500/[0.07] rounded-full blur-sm group-hover:bg-blue-500/[0.12] transition"></div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-500/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ученики</span>
            </div>
            <div class="text-3xl font-bold text-white" x-text="stats.total_students || 0"></div>
        </div>

        <div class="relative overflow-hidden bg-dark-light rounded-2xl p-5 border border-white/[0.06] group hover:border-emerald-500/20 transition-colors">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-emerald-500/[0.07] rounded-full blur-sm group-hover:bg-emerald-500/[0.12] transition"></div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-emerald-500/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Подписки</span>
            </div>
            <div class="text-3xl font-bold text-white" x-text="stats.active_subscriptions || 0"></div>
        </div>

        <div class="relative overflow-hidden bg-dark-light rounded-2xl p-5 border border-white/[0.06] group hover:border-amber-500/20 transition-colors">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-amber-500/[0.07] rounded-full blur-sm group-hover:bg-amber-500/[0.12] transition"></div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-amber-500/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ожидают ДЗ</span>
            </div>
            <div class="text-3xl font-bold text-white" x-text="stats.pending_homework || 0"></div>
        </div>

        <div class="relative overflow-hidden bg-dark-light rounded-2xl p-5 border border-white/[0.06] group hover:border-coral/20 transition-colors">
            <div class="absolute -right-3 -top-3 w-16 h-16 bg-coral/[0.07] rounded-full blur-sm group-hover:bg-coral/[0.12] transition"></div>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-coral/15 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-coral" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Заработок</span>
            </div>
            <div class="text-3xl font-bold text-white" x-text="formatMoney(stats.monthly_earnings || 0)"></div>
        </div>
    </div>

    {{-- Two columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Recent activity --}}
        <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
            <div class="px-5 py-4 border-b border-white/[0.06] flex items-center justify-between">
                <h3 class="font-semibold text-white text-[15px]">Последняя активность</h3>
                <a href="/teacher/students" class="text-xs font-medium text-coral hover:text-coral-light transition">Все ученики</a>
            </div>
            <div class="divide-y divide-white/[0.04]">
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-white/[0.02] transition-colors">
                        <div class="w-9 h-9 rounded-xl bg-coral/10 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold text-coral" x-text="activity.student?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate" x-text="activity.student?.name"></div>
                            <div class="text-xs text-gray-500 truncate" x-text="activity.description"></div>
                        </div>
                        <span class="text-[11px] text-gray-600 flex-shrink-0" x-text="activity.time_ago"></span>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="px-5 py-10 text-center text-gray-600 text-sm">
                    Нет активности
                </div>
            </div>
        </div>

        {{-- Needs attention --}}
        <div class="bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
            <div class="px-5 py-4 border-b border-white/[0.06]">
                <h3 class="font-semibold text-white text-[15px]">Требуют внимания</h3>
            </div>
            <div class="divide-y divide-white/[0.04]">
                <template x-for="student in needsAttention" :key="student.id">
                    <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-white/[0.02] transition-colors">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="student.issue === 'inactive' ? 'bg-red-500/10' : 'bg-amber-500/10'">
                            <span class="text-sm font-semibold"
                                  :class="student.issue === 'inactive' ? 'text-red-400' : 'text-amber-400'"
                                  x-text="student.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white" x-text="student.name"></div>
                            <div class="text-xs"
                                 :class="student.issue === 'inactive' ? 'text-red-400/80' : 'text-amber-400/80'"
                                 x-text="student.issue_text"></div>
                        </div>
                        <a :href="'/teacher/students/' + student.id"
                           class="text-xs font-medium text-coral hover:text-coral-light transition flex-shrink-0">Открыть</a>
                    </div>
                </template>
                <div x-show="needsAttention.length === 0" class="px-5 py-10 text-center text-sm">
                    <div class="text-emerald-400/80 mb-1">Все ученики активны</div>
                    <div class="text-gray-600 text-xs">Ничего не требует внимания</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Homework overview --}}
    <div class="mt-5 bg-dark-light rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[0.06] flex items-center justify-between">
            <h3 class="font-semibold text-white text-[15px]">Домашние задания</h3>
            <a href="/teacher/homework" class="text-xs font-medium text-coral hover:text-coral-light transition">Все задания</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/[0.06]">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Задание</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Ученики</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Выполнено</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Срок</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <template x-for="hw in recentHomework" :key="hw.id">
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-3.5 text-sm font-medium text-white" x-text="hw.title"></td>
                            <td class="px-5 py-3.5 text-sm text-gray-400" x-text="hw.assigned_count"></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-20 bg-white/[0.06] rounded-full h-1.5">
                                        <div class="bg-coral rounded-full h-1.5 transition-all"
                                             :style="'width: ' + hw.completion_rate + '%'"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 tabular-nums" x-text="hw.completion_rate + '%'"></span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500" x-text="hw.due_date"></td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-lg"
                                      :class="hw.is_overdue ? 'bg-red-500/10 text-red-400' : 'bg-emerald-500/10 text-emerald-400'"
                                      x-text="hw.is_overdue ? 'Просрочено' : 'Активно'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function teacherDashboard() {
    return {
        stats: {},
        recentActivity: [],
        needsAttention: [],
        recentHomework: [],

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.stats = {
                total_students: 24,
                active_subscriptions: 18,
                pending_homework: 5,
                monthly_earnings: 14500
            };

            this.recentActivity = [
                { id: 1, student: { name: 'Александр' }, description: 'Решил 15 задач', time_ago: '5 мин назад' },
                { id: 2, student: { name: 'Мария' }, description: 'Выполнила ДЗ "Квадратные уравнения"', time_ago: '15 мин назад' },
                { id: 3, student: { name: 'Дмитрий' }, description: 'Достиг серебряной лиги', time_ago: '1 час назад' }
            ];

            this.needsAttention = [
                { id: 1, name: 'Иван', issue: 'inactive', issue_text: 'Не заходил 5 дней' },
                { id: 2, name: 'Елена', issue: 'struggling', issue_text: 'Низкая точность (42%)' }
            ];

            this.recentHomework = [
                { id: 1, title: 'Квадратные уравнения', assigned_count: 12, completion_rate: 75, due_date: 'Завтра', is_overdue: false },
                { id: 2, title: 'Теорема Пифагора', assigned_count: 10, completion_rate: 40, due_date: 'Вчера', is_overdue: true }
            ];
        },

        formatMoney(amount) {
            return amount.toLocaleString('ru-RU') + ' ₽';
        }
    }
}
</script>
@endpush
@endsection
