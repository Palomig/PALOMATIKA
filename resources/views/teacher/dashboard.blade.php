@extends('layouts.teacher')

@section('title', 'Обзор')
@section('header', 'Обзор')

@section('content')
<div x-data="teacherDashboard()" class="space-y-4">
    {{-- Welcome hero --}}
    <section class="tsh-card p-5 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="tsh-page-kicker">Teacher workspace</p>
                <h2 class="tsh-page-title">Пульс класса и ключевые действия</h2>
                <p class="tsh-page-subtitle">Проверяйте активность, отслеживайте риски, управляйте ДЗ.</p>
            </div>
            <div class="flex flex-wrap gap-2 flex-shrink-0">
                <a href="/teacher/homework" class="tsh-action-primary">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Назначить ДЗ
                </a>
                <a href="/teacher/students" class="tsh-action-secondary">
                    Ученики
                </a>
            </div>
        </div>
    </section>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="tsh-card p-4 transition-shadow hover:shadow-sm">
            <div class="flex items-center gap-2 mb-2.5">
                <div class="w-7 h-7 bg-blue-50 rounded-md flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-[10.5px] font-medium text-[#8c95a6] uppercase tracking-wider">Ученики</span>
            </div>
            <div class="text-xl font-bold text-[#222630] tabular-nums" x-text="stats.total_students || 0"></div>
        </div>

        <div class="tsh-card p-4 transition-shadow hover:shadow-sm">
            <div class="flex items-center gap-2 mb-2.5">
                <div class="w-7 h-7 bg-emerald-50 rounded-md flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10.5px] font-medium text-[#8c95a6] uppercase tracking-wider">Подписки</span>
            </div>
            <div class="text-xl font-bold text-[#222630] tabular-nums" x-text="stats.active_subscriptions || 0"></div>
        </div>

        <div class="tsh-card p-4 transition-shadow hover:shadow-sm">
            <div class="flex items-center gap-2 mb-2.5">
                <div class="w-7 h-7 bg-amber-50 rounded-md flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10.5px] font-medium text-[#8c95a6] uppercase tracking-wider">Ожидают ДЗ</span>
            </div>
            <div class="text-xl font-bold text-[#222630] tabular-nums" x-text="stats.pending_homework || 0"></div>
        </div>

        <div class="tsh-card p-4 transition-shadow hover:shadow-sm">
            <div class="flex items-center gap-2 mb-2.5">
                <div class="w-7 h-7 bg-rose-50 rounded-md flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[10.5px] font-medium text-[#8c95a6] uppercase tracking-wider">Заработок</span>
            </div>
            <div class="text-xl font-bold text-[#222630] tabular-nums" x-text="formatMoney(stats.monthly_earnings || 0)"></div>
        </div>
    </div>

    {{-- Two columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Recent activity --}}
        <div class="tsh-card">
            <div class="px-4 py-3 flex items-center justify-between" style="border-bottom: 1px solid rgba(0,0,0,0.04)">
                <h3 class="font-semibold text-[13px] text-[#222630]">Последняя активность</h3>
                <a href="/teacher/students" class="text-[12px] font-medium text-[#4a8af5] hover:text-[#3a7ae5] transition-colors">Все</a>
            </div>
            <div>
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-[#fafbfc] transition-colors" style="border-bottom: 1px solid rgba(0,0,0,0.03)">
                        <div class="w-7 h-7 bg-blue-50 text-blue-600 rounded-md flex items-center justify-center font-semibold text-[11px] flex-shrink-0">
                            <span x-text="activity.student?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-medium text-[#222630] truncate" x-text="activity.student?.name"></div>
                            <div class="text-[11px] text-[#8c95a6] truncate" x-text="activity.description"></div>
                        </div>
                        <span class="text-[10px] text-[#a0a8b8] flex-shrink-0" x-text="activity.time_ago"></span>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="px-4 py-8 text-center text-[13px] text-[#8c95a6]">
                    Нет активности
                </div>
            </div>
        </div>

        {{-- Needs attention --}}
        <div class="tsh-card">
            <div class="px-4 py-3" style="border-bottom: 1px solid rgba(0,0,0,0.04)">
                <h3 class="font-semibold text-[13px] text-[#222630]">Требуют внимания</h3>
            </div>
            <div>
                <template x-for="student in needsAttention" :key="student.id">
                    <div class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-[#fafbfc] transition-colors" style="border-bottom: 1px solid rgba(0,0,0,0.03)">
                        <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0"
                             :class="student.issue === 'inactive' ? 'bg-red-50' : 'bg-amber-50'">
                            <span class="text-[11px] font-semibold"
                                  :class="student.issue === 'inactive' ? 'text-red-500' : 'text-amber-500'"
                                  x-text="student.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-medium text-[#222630]" x-text="student.name"></div>
                            <div class="text-[11px]"
                                 :class="student.issue === 'inactive' ? 'text-red-400' : 'text-amber-500'"
                                 x-text="student.issue_text"></div>
                        </div>
                        <a :href="'/teacher/students/' + student.id"
                           class="text-[12px] font-medium text-[#4a8af5] hover:text-[#3a7ae5] transition-colors">Открыть</a>
                    </div>
                </template>
                <div x-show="needsAttention.length === 0" class="px-4 py-8 text-center">
                    <div class="text-emerald-500 text-[13px] mb-0.5">Все ученики активны</div>
                    <div class="text-[11px] text-[#a0a8b8]">Ничего не требует внимания</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Homework overview --}}
    <div class="tsh-card">
        <div class="px-4 py-3 flex items-center justify-between" style="border-bottom: 1px solid rgba(0,0,0,0.04)">
            <h3 class="font-semibold text-[13px] text-[#222630]">Домашние задания</h3>
            <a href="/teacher/homework" class="text-[12px] font-medium text-[#4a8af5] hover:text-[#3a7ae5] transition-colors">Все задания</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-[10.5px] font-semibold uppercase tracking-wider text-[#8c95a6] bg-[#fafbfc]">Задание</th>
                        <th class="px-4 py-2 text-left text-[10.5px] font-semibold uppercase tracking-wider text-[#8c95a6] bg-[#fafbfc]">Учеников</th>
                        <th class="px-4 py-2 text-left text-[10.5px] font-semibold uppercase tracking-wider text-[#8c95a6] bg-[#fafbfc]">Выполнено</th>
                        <th class="px-4 py-2 text-left text-[10.5px] font-semibold uppercase tracking-wider text-[#8c95a6] bg-[#fafbfc]">Срок</th>
                        <th class="px-4 py-2 text-left text-[10.5px] font-semibold uppercase tracking-wider text-[#8c95a6] bg-[#fafbfc]">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="hw in recentHomework" :key="hw.id">
                        <tr class="hover:bg-[#fafbfc] transition-colors" style="border-bottom: 1px solid rgba(0,0,0,0.03)">
                            <td class="px-4 py-2.5 text-[13px] font-medium text-[#222630]" x-text="hw.title"></td>
                            <td class="px-4 py-2.5 text-[13px] text-[#5f6775] tabular-nums" x-text="hw.assigned_count"></td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="tsh-progress w-16">
                                        <div class="tsh-progress-bar bg-blue-400" :style="'width: ' + hw.completion_rate + '%'"></div>
                                    </div>
                                    <span class="text-[11px] tabular-nums text-[#8c95a6]" x-text="hw.completion_rate + '%'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-[13px] text-[#5f6775]" x-text="hw.due_date"></td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center px-2 py-0.5 text-[10.5px] font-medium rounded-md"
                                      :class="hw.is_overdue ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-500'"
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
