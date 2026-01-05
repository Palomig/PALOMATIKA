@extends('layouts.app')

@section('title', 'Достижения')
@section('header', 'Достижения')

@section('content')
<div x-data="badgesPage()">
    <!-- Summary -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-amber-100 text-sm mb-1">Получено бейджей</div>
                <div class="text-4xl font-bold" x-text="earnedCount + ' / ' + totalCount"></div>
            </div>
            <div class="text-6xl">🏆</div>
        </div>
    </div>

    <!-- Showcased badges -->
    <div class="bg-dark-light rounded-2xl p-6 border border-gray-800 mb-6" x-show="showcasedBadges.length > 0">
        <h3 class="font-semibold text-white mb-4">Витрина (до 5 бейджей)</h3>
        <div class="flex flex-wrap gap-4">
            <template x-for="badge in showcasedBadges" :key="badge.id">
                <div class="relative group">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-xl flex items-center justify-center text-3xl cursor-pointer border border-amber-500/30"
                         @click="toggleShowcase(badge)">
                        <span x-text="badge.badge?.icon || '🏅'"></span>
                    </div>
                    <button @click="toggleShowcase(badge)"
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition">
                        ✕
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Earned badges by category -->
    <div class="space-y-6">
        <!-- Streak badges -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">🔥</span>
                <h3 class="font-semibold text-white">Стрики</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="badge in getBadgesByType('streak')" :key="badge.id">
                    <div class="text-center cursor-pointer" @click="showBadgeDetails(badge)">
                        <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center text-3xl mb-2 transition"
                             :class="badge.earned ? 'bg-gradient-to-br from-orange-500/20 to-red-500/20 border border-orange-500/30' : 'bg-gray-800 grayscale opacity-50'">
                            <span x-text="badge.icon"></span>
                        </div>
                        <div class="text-sm font-medium text-white" x-text="badge.name"></div>
                        <div class="text-xs text-gray-500" x-text="badge.description"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Task badges -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">📝</span>
                <h3 class="font-semibold text-white">Задачи</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="badge in getBadgesByType('tasks')" :key="badge.id">
                    <div class="text-center cursor-pointer" @click="showBadgeDetails(badge)">
                        <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center text-3xl mb-2 transition"
                             :class="badge.earned ? 'bg-gradient-to-br from-green-500/20 to-emerald-500/20 border border-green-500/30' : 'bg-gray-800 grayscale opacity-50'">
                            <span x-text="badge.icon"></span>
                        </div>
                        <div class="text-sm font-medium text-white" x-text="badge.name"></div>
                        <div class="text-xs text-gray-500" x-text="badge.description"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Duel badges -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">⚔️</span>
                <h3 class="font-semibold text-white">Дуэли</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="badge in getBadgesByType('duel')" :key="badge.id">
                    <div class="text-center cursor-pointer" @click="showBadgeDetails(badge)">
                        <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center text-3xl mb-2 transition"
                             :class="badge.earned ? 'bg-gradient-to-br from-purple-500/20 to-indigo-500/20 border border-purple-500/30' : 'bg-gray-800 grayscale opacity-50'">
                            <span x-text="badge.icon"></span>
                        </div>
                        <div class="text-sm font-medium text-white" x-text="badge.name"></div>
                        <div class="text-xs text-gray-500" x-text="badge.description"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- League badges -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">🏆</span>
                <h3 class="font-semibold text-white">Лиги</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="badge in getBadgesByType('league')" :key="badge.id">
                    <div class="text-center cursor-pointer" @click="showBadgeDetails(badge)">
                        <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center text-3xl mb-2 transition"
                             :class="badge.earned ? 'bg-gradient-to-br from-yellow-500/20 to-amber-500/20 border border-yellow-500/30' : 'bg-gray-800 grayscale opacity-50'">
                            <span x-text="badge.icon"></span>
                        </div>
                        <div class="text-sm font-medium text-white" x-text="badge.name"></div>
                        <div class="text-xs text-gray-500" x-text="badge.description"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Special badges -->
        <div class="bg-dark-light rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center mb-4">
                <span class="text-2xl mr-2">✨</span>
                <h3 class="font-semibold text-white">Особые</h3>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="badge in getBadgesByType('special')" :key="badge.id">
                    <div class="text-center cursor-pointer" @click="showBadgeDetails(badge)">
                        <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center text-3xl mb-2 transition"
                             :class="badge.earned ? 'bg-gradient-to-br from-pink-500/20 to-rose-500/20 border border-pink-500/30' : 'bg-gray-800 grayscale opacity-50'">
                            <span x-text="badge.icon"></span>
                        </div>
                        <div class="text-sm font-medium text-white" x-text="badge.name"></div>
                        <div class="text-xs text-gray-500" x-text="badge.description"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Badge detail modal -->
    <div x-show="selectedBadge" x-cloak
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
         @click.self="selectedBadge = null">
        <div class="bg-dark-light rounded-2xl p-6 max-w-sm w-full border border-gray-700">
            <div class="text-center">
                <div class="w-24 h-24 mx-auto rounded-2xl flex items-center justify-center text-5xl mb-4"
                     :class="selectedBadge?.earned ? 'bg-gradient-to-br from-amber-500/20 to-orange-500/20 border border-amber-500/30' : 'bg-gray-800'">
                    <span x-text="selectedBadge?.icon"></span>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2" x-text="selectedBadge?.name"></h3>
                <p class="text-gray-400 mb-4" x-text="selectedBadge?.description"></p>

                <div x-show="selectedBadge?.earned" class="mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                        ✓ Получено <span class="ml-1" x-text="formatDate(selectedBadge?.earned_at)"></span>
                    </span>
                </div>

                <div x-show="!selectedBadge?.earned" class="mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-700 text-gray-400">
                        Ещё не получено
                    </span>
                </div>

                <!-- Rarity -->
                <div class="text-sm text-gray-500 mb-4">
                    Редкость:
                    <span class="font-medium"
                          :class="{
                              'text-gray-400': selectedBadge?.rarity === 'common',
                              'text-blue-400': selectedBadge?.rarity === 'rare',
                              'text-purple-400': selectedBadge?.rarity === 'epic',
                              'text-amber-400': selectedBadge?.rarity === 'legendary'
                          }"
                          x-text="rarityNames[selectedBadge?.rarity] || 'Обычный'"></span>
                </div>

                <!-- Add to showcase button -->
                <button x-show="selectedBadge?.earned && !selectedBadge?.is_showcased && showcasedBadges.length < 5"
                        @click="toggleShowcase(selectedBadge)"
                        class="w-full bg-coral text-white py-2 rounded-lg font-medium hover:bg-coral-dark transition">
                    Добавить в витрину
                </button>

                <button @click="selectedBadge = null"
                        class="w-full mt-2 text-gray-400 py-2 hover:text-white transition">
                    Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function badgesPage() {
    return {
        badges: [],
        earnedBadges: [],
        showcasedBadges: [],
        selectedBadge: null,
        earnedCount: 0,
        totalCount: 0,
        rarityNames: {
            'common': 'Обычный',
            'rare': 'Редкий',
            'epic': 'Эпический',
            'legendary': 'Легендарный'
        },

        async init() {
            await this.loadBadges();
        },

        async loadBadges() {
            try {
                // Load all badges
                const allResponse = await fetch('/api/badges');
                if (allResponse.ok) {
                    const data = await allResponse.json();
                    const allBadges = [];
                    Object.values(data.badges || {}).forEach(group => {
                        group.forEach(b => allBadges.push({ ...b, earned: false }));
                    });
                    this.badges = allBadges;
                    this.totalCount = allBadges.length;
                }

                // Load user badges
                const userResponse = await fetch('/api/badges/user', {
                    headers: this.$root.getAuthHeaders()
                });
                if (userResponse.ok) {
                    const data = await userResponse.json();
                    this.earnedBadges = data.earned || [];
                    this.showcasedBadges = data.showcased || [];
                    this.earnedCount = data.total_earned || 0;

                    // Mark earned badges
                    this.earnedBadges.forEach(ub => {
                        const badge = this.badges.find(b => b.id === ub.badge_id);
                        if (badge) {
                            badge.earned = true;
                            badge.earned_at = ub.earned_at;
                            badge.user_badge_id = ub.id;
                            badge.is_showcased = ub.is_showcased;
                        }
                    });
                }
            } catch (e) {
                // Mock data
                this.badges = [
                    { id: 1, name: 'Начинающий', description: '3 дня подряд', condition_type: 'streak', icon: '🔥', rarity: 'common', earned: true, earned_at: '2024-01-15' },
                    { id: 2, name: 'Неделя в деле', description: '7 дней подряд', condition_type: 'streak', icon: '🔥', rarity: 'common', earned: true },
                    { id: 3, name: 'Месяц без перерыва', description: '30 дней подряд', condition_type: 'streak', icon: '🏆', rarity: 'rare', earned: false },
                    { id: 4, name: 'Первые шаги', description: '10 правильных задач', condition_type: 'tasks', icon: '📝', rarity: 'common', earned: true },
                    { id: 5, name: 'Полсотни', description: '50 правильных задач', condition_type: 'tasks', icon: '📚', rarity: 'common', earned: false },
                    { id: 6, name: 'Первая дуэль', description: 'Выиграй первую дуэль', condition_type: 'duel', icon: '⚔️', rarity: 'common', earned: false },
                    { id: 7, name: 'Серебряная лига', description: 'Достигни серебряной лиги', condition_type: 'league', icon: '🥈', rarity: 'common', earned: false },
                    { id: 8, name: 'Ранний пользователь', description: 'Зарегистрировался в первый месяц', condition_type: 'special', icon: '🚀', rarity: 'epic', earned: true }
                ];
                this.earnedCount = this.badges.filter(b => b.earned).length;
                this.totalCount = this.badges.length;
                this.showcasedBadges = this.badges.filter(b => b.earned).slice(0, 2);
            }
        },

        getBadgesByType(type) {
            return this.badges.filter(b => b.condition_type === type);
        },

        showBadgeDetails(badge) {
            this.selectedBadge = badge;
        },

        async toggleShowcase(badge) {
            if (!badge.earned) return;

            try {
                const response = await fetch('/api/badges/' + badge.user_badge_id + '/toggle-showcase', {
                    method: 'POST',
                    headers: this.$root.getAuthHeaders()
                });

                if (response.ok) {
                    badge.is_showcased = !badge.is_showcased;
                    if (badge.is_showcased) {
                        this.showcasedBadges.push(badge);
                    } else {
                        this.showcasedBadges = this.showcasedBadges.filter(b => b.id !== badge.id);
                    }
                }
            } catch (e) {
                // Toggle locally for demo
                badge.is_showcased = !badge.is_showcased;
                if (badge.is_showcased) {
                    this.showcasedBadges.push(badge);
                } else {
                    this.showcasedBadges = this.showcasedBadges.filter(b => b.id !== badge.id);
                }
            }

            this.selectedBadge = null;
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ru-RU');
        }
    }
}
</script>
@endpush
@endsection
