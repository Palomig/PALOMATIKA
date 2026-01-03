@extends('layouts.teacher')

@section('title', 'Обзор')
@section('header', 'Обзор')

@section('content')
<div x-data="teacherDashboard()">
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="stats.total_students || 0"></div>
                    <div class="text-gray-500 text-sm">учеников</div>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-bold text-green-600" x-text="stats.active_subscriptions || 0"></div>
                    <div class="text-gray-500 text-sm">активных подписок</div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-bold text-orange-500" x-text="stats.pending_homework || 0"></div>
                    <div class="text-gray-500 text-sm">невыполненных ДЗ</div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-bold text-purple-600" x-text="formatMoney(stats.monthly_earnings || 0)"></div>
                    <div class="text-gray-500 text-sm">заработок за месяц</div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Two columns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent activity -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Последняя активность учеников</h3>
                <a href="/teacher/students" class="text-indigo-600 text-sm hover:underline">Все</a>
            </div>
            <div class="divide-y divide-gray-100">
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-center p-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                            <span class="text-gray-600 font-medium" x-text="activity.student?.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900" x-text="activity.student?.name"></div>
                            <div class="text-xs text-gray-500" x-text="activity.description"></div>
                        </div>
                        <div class="text-xs text-gray-400" x-text="activity.time_ago"></div>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="p-8 text-center text-gray-500">
                    Нет активности
                </div>
            </div>
        </div>

        <!-- Students needing attention -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900">Требуют внимания</h3>
            </div>
            <div class="divide-y divide-gray-100">
                <template x-for="student in needsAttention" :key="student.id">
                    <div class="flex items-center p-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center"
                             :class="student.issue === 'inactive' ? 'bg-red-100' : 'bg-yellow-100'">
                            <span :class="student.issue === 'inactive' ? 'text-red-600' : 'text-yellow-600'"
                                  x-text="student.name?.charAt(0) || '?'"></span>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900" x-text="student.name"></div>
                            <div class="text-xs"
                                 :class="student.issue === 'inactive' ? 'text-red-500' : 'text-yellow-500'"
                                 x-text="student.issue_text"></div>
                        </div>
                        <a :href="'/teacher/students/' + student.id"
                           class="text-indigo-600 text-sm hover:underline">Подробнее</a>
                    </div>
                </template>
                <div x-show="needsAttention.length === 0" class="p-8 text-center text-gray-500">
                    Все ученики активны!
                </div>
            </div>
        </div>
    </div>

    <!-- Homework overview -->
    <div class="mt-6 bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Домашние задания</h3>
            <a href="/teacher/homework" class="text-indigo-600 text-sm hover:underline">Все задания</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Задание</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ученики</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Выполнено</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Срок</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="hw in recentHomework" :key="hw.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900" x-text="hw.title"></td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="hw.assigned_count"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-green-500 rounded-full h-2"
                                             :style="'width: ' + hw.completion_rate + '%'"></div>
                                    </div>
                                    <span class="text-sm text-gray-500" x-text="hw.completion_rate + '%'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="hw.due_date"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full"
                                      :class="hw.is_overdue ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
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
            // Mock data for demo
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
