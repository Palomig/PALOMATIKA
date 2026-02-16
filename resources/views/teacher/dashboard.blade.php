@extends('layouts.teacher')

@section('title', 'Обзор')
@section('header', 'Обзор')

@section('content')
<div x-data="teacherDashboard()" class="space-y-6">
    {{-- Welcome hero --}}
    <section class="tsh-card p-6 sm:p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-blue-50 to-transparent pointer-events-none"></div>
        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="tsh-page-kicker">Teacher workspace</p>
                <h2 class="tsh-page-title">Пульс класса и ключевые действия</h2>
                <p class="tsh-page-subtitle">Сначала проверяйте активность и риски, затем переходите к ДЗ и ученикам.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/teacher/homework" class="tsh-action-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Назначить ДЗ
                </a>
                <a href="/teacher/students" class="tsh-action-secondary font-medium">
                    Перейти к ученикам
                </a>
            </div>
        </div>
    </section>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="tsh-card tsh-stat-blue p-5 group hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-blue-400 uppercase tracking-wider">Ученики</span>
            </div>
            <div class="text-3xl font-bold text-gray-900" x-text="stats.total_students || 0"></div>
        </div>

        <div class="tsh-card tsh-stat-green p-5 group hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Подписки</span>
            </div>
            <div class="text-3xl font-bold text-gray-900" x-text="stats.active_subscriptions || 0"></div>
        </div>

        <div class="tsh-card tsh-stat-amber p-5 group hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Ожидают ДЗ</span>
            </div>
            <div class="text-3xl font-bold text-gray-900" x-text="stats.pending_homework || 0"></div>
        </div>

        <div class="tsh-card tsh-stat-coral p-5 group hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-red-300 uppercase tracking-wider">Заработок</span>
            </div>
            <div class="text-3xl font-bold text-gray-900" x-text="formatMoney(stats.monthly_earnings || 0)"></div>
        </div>
    </div>

    {{-- Two columns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Recent activity --}}
        <div class="tsh-card">
            <div class="px-5 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--tsh-border)">
                <h3 class="font-semibold text-[15px]" style="color: var(--tsh-text)">Последняя активность</h3>
                <a href="/teacher/students" class="text-xs font-medium transition" style="color: var(--tsh-blue)">Все ученики</a>
            </div>
            <div>
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-center gap-3 px-5 py-3.5 transition-colors" style="border-bottom: 1px solid var(--tsh-border-soft)" onmouseover="this.style.background='var(--tsh-surface-soft)'" onmouseout="this.style.background='transparent'">
                        <div class="tsh-avatar-sm bg-blue-100 text-blue-600 rounded-full" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:13px;flex-shrink:0;">
                            <span x-text="activity.student?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate" style="color: var(--tsh-text)" x-text="activity.student?.name"></div>
                            <div class="text-xs truncate" style="color: var(--tsh-muted)" x-text="activity.description"></div>
                        </div>
                        <span class="text-[11px] flex-shrink-0" style="color: var(--tsh-subtle)" x-text="activity.time_ago"></span>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="px-5 py-10 text-center text-sm" style="color: var(--tsh-subtle)">
                    Нет активности
                </div>
            </div>
        </div>

        {{-- Needs attention --}}
        <div class="tsh-card">
            <div class="px-5 py-4" style="border-bottom: 1px solid var(--tsh-border)">
                <h3 class="font-semibold text-[15px]" style="color: var(--tsh-text)">Требуют внимания</h3>
            </div>
            <div>
                <template x-for="student in needsAttention" :key="student.id">
                    <div class="flex items-center gap-3 px-5 py-3.5 transition-colors" style="border-bottom: 1px solid var(--tsh-border-soft)" onmouseover="this.style.background='var(--tsh-surface-soft)'" onmouseout="this.style.background='transparent'">
                        <div class="rounded-full flex items-center justify-center flex-shrink-0" style="width:36px;height:36px;"
                             :class="student.issue === 'inactive' ? 'bg-red-100' : 'bg-amber-100'">
                            <span class="text-sm font-semibold"
                                  :class="student.issue === 'inactive' ? 'text-red-500' : 'text-amber-500'"
                                  x-text="student.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium" style="color: var(--tsh-text)" x-text="student.name"></div>
                            <div class="text-xs"
                                 :class="student.issue === 'inactive' ? 'text-red-400' : 'text-amber-500'"
                                 x-text="student.issue_text"></div>
                        </div>
                        <a :href="'/teacher/students/' + student.id"
                           class="text-xs font-medium transition" style="color: var(--tsh-blue)">Открыть</a>
                    </div>
                </template>
                <div x-show="needsAttention.length === 0" class="px-5 py-10 text-center text-sm">
                    <div class="text-emerald-500 mb-1">Все ученики активны</div>
                    <div class="text-xs" style="color: var(--tsh-subtle)">Ничего не требует внимания</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Homework overview --}}
    <div class="tsh-card">
        <div class="px-5 py-4 flex items-center justify-between" style="border-bottom: 1px solid var(--tsh-border)">
            <h3 class="font-semibold text-[15px]" style="color: var(--tsh-text)">Домашние задания</h3>
            <a href="/teacher/homework" class="text-xs font-medium transition" style="color: var(--tsh-blue)">Все задания</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">Задание</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">Ученики</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">Выполнено</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">Срок</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--tsh-subtle); background: var(--tsh-surface-soft)">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="hw in recentHomework" :key="hw.id">
                        <tr class="transition-colors" style="border-bottom: 1px solid var(--tsh-border-soft)" onmouseover="this.style.background='var(--tsh-surface-soft)'" onmouseout="this.style.background='transparent'">
                            <td class="px-5 py-3.5 text-sm font-medium" style="color: var(--tsh-text)" x-text="hw.title"></td>
                            <td class="px-5 py-3.5 text-sm" style="color: var(--tsh-muted)" x-text="hw.assigned_count"></td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="tsh-progress w-20">
                                        <div class="tsh-progress-bar bg-blue-400" :style="'width: ' + hw.completion_rate + '%'"></div>
                                    </div>
                                    <span class="text-xs tabular-nums" style="color: var(--tsh-muted)" x-text="hw.completion_rate + '%'"></span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-sm" style="color: var(--tsh-muted)" x-text="hw.due_date"></td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded-full"
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
