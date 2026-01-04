@extends('layouts.app')

@section('title', 'Главная')
@section('header', 'Главная')

@section('content')
<div x-data="dashboardPage()">
    <!-- Welcome & Daily Goal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Welcome card -->
        <div class="lg:col-span-2 bg-gradient-to-r from-coral to-coral-dark rounded-2xl p-6 text-white">
            <h2 class="text-2xl font-bold mb-2">Привет, <span x-text="$root.user?.name || 'ученик'"></span>!</h2>
            <p class="text-white/80 mb-4">Продолжай готовиться к ОГЭ. Сегодня отличный день для новых знаний!</p>
            <a href="/practice" class="inline-flex items-center bg-white text-coral px-5 py-2.5 rounded-xl font-medium hover:bg-white/90 transition">
                Начать тренировку
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>

        <!-- Daily goal -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-white">Цель на сегодня</h3>
                <span class="text-2xl">🎯</span>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-coral" x-text="stats.today_tasks + '/' + stats.daily_goal"></div>
                <div class="text-gray-400 text-sm mt-1">задач решено</div>
                <div class="mt-4 bg-dark rounded-full h-3">
                    <div class="bg-gradient-to-r from-coral to-coral-light rounded-full h-3 transition-all"
                         :style="'width: ' + Math.min(100, (stats.today_tasks / stats.daily_goal) * 100) + '%'"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-green-400" x-text="stats.total_correct || 0"></div>
            <div class="text-gray-400 text-sm">верных ответов</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-blue-400" x-text="(stats.accuracy || 0) + '%'"></div>
            <div class="text-gray-400 text-sm">точность</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-coral" x-text="stats.total_xp || 0"></div>
            <div class="text-gray-400 text-sm">опыта</div>
        </div>
        <div class="bg-dark-light rounded-xl p-4 border border-gray-800">
            <div class="text-2xl font-bold text-amber-400" x-text="stats.badges_count || 0"></div>
            <div class="text-gray-400 text-sm">бейджей</div>
        </div>
    </div>

    <!-- Two columns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Topics progress -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-white">Прогресс по темам</h3>
                <a href="/topics" class="text-coral text-sm hover:text-coral-light transition">Все темы</a>
            </div>
            <div class="space-y-4">
                <template x-for="topic in topics.slice(0, 5)" :key="topic.id">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-300" x-text="topic.name"></span>
                            <span class="text-gray-500" x-text="topic.progress + '%'"></span>
                        </div>
                        <div class="bg-dark rounded-full h-2">
                            <div class="bg-coral rounded-full h-2 transition-all"
                                 :style="'width: ' + topic.progress + '%'"></div>
                        </div>
                    </div>
                </template>
                <div x-show="topics.length === 0" class="text-gray-500 text-center py-4">
                    Загрузка...
                </div>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <h3 class="font-semibold text-white mb-4">Последние действия</h3>
            <div class="space-y-4">
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center"
                             :class="activity.is_correct ? 'bg-green-500/20' : 'bg-red-500/20'">
                            <span x-text="activity.is_correct ? '✓' : '✗'"
                                  :class="activity.is_correct ? 'text-green-400' : 'text-red-400'"></span>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="text-sm text-gray-200" x-text="activity.topic_name"></div>
                            <div class="text-xs text-gray-500" x-text="activity.time_ago"></div>
                        </div>
                        <div class="text-sm font-medium" :class="activity.is_correct ? 'text-green-400' : 'text-red-400'"
                             x-text="(activity.is_correct ? '+' : '') + activity.xp + ' XP'"></div>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="text-gray-500 text-center py-4">
                    Пока нет активности. Начни решать задачи!
                </div>
            </div>
        </div>
    </div>

    <!-- Weak skills -->
    <div class="mt-6 bg-dark-light rounded-2xl p-6 border border-gray-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-white">Слабые навыки</h3>
            <span class="text-sm text-gray-500">Рекомендуем потренировать</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <template x-for="skill in weakSkills" :key="skill.id">
                <div class="bg-dark rounded-xl p-4 border border-gray-700 hover:border-coral/50 transition cursor-pointer">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-gray-200" x-text="skill.name"></span>
                        <span class="text-sm text-red-400" x-text="skill.accuracy + '%'"></span>
                    </div>
                    <div class="bg-dark-light rounded-full h-2">
                        <div class="bg-red-400 rounded-full h-2"
                             :style="'width: ' + skill.accuracy + '%'"></div>
                    </div>
                </div>
            </template>
            <div x-show="weakSkills.length === 0" class="col-span-3 text-gray-500 text-center py-4">
                Отлично! Все навыки в хорошем состоянии.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardPage() {
    return {
        stats: {
            today_tasks: 0,
            daily_goal: 10,
            total_correct: 0,
            accuracy: 0,
            total_xp: 0,
            badges_count: 0
        },
        topics: [],
        recentActivity: [],
        weakSkills: [],

        async init() {
            await Promise.all([
                this.loadStats(),
                this.loadTopics(),
                this.loadActivity()
            ]);
        },

        async loadStats() {
            try {
                const response = await fetch('/api/progress/dashboard', {
                    headers: this.$root.getAuthHeaders()
                });
                if (response.ok) {
                    const data = await response.json();
                    this.stats = { ...this.stats, ...data };
                }
            } catch (e) {
                console.error('Failed to load stats', e);
            }
        },

        async loadTopics() {
            try {
                const response = await fetch('/api/topics');
                if (response.ok) {
                    const data = await response.json();
                    this.topics = (data.topics || []).map(t => ({
                        ...t,
                        progress: Math.floor(Math.random() * 100)
                    }));
                }
            } catch (e) {
                console.error('Failed to load topics', e);
            }
        },

        async loadActivity() {
            try {
                const response = await fetch('/api/progress/history?limit=5', {
                    headers: this.$root.getAuthHeaders()
                });
                if (response.ok) {
                    const data = await response.json();
                    this.recentActivity = data.history || [];
                }
            } catch (e) {
                // Mock data for demo
                this.recentActivity = [];
            }

            // Mock weak skills
            this.weakSkills = [
                { id: 1, name: 'Дискриминант', accuracy: 45 },
                { id: 2, name: 'Теорема Пифагора', accuracy: 52 },
                { id: 3, name: 'Системы уравнений', accuracy: 38 }
            ];
        }
    }
}
</script>
@endpush
@endsection
